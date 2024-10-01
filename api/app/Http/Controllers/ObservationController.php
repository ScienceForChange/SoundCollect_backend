<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreObservationRequest;
use App\Models\Observation;
use Illuminate\Http\Request;
use App\Http\Resources\ObservationResource;
use App\Http\Resources\UserObservationsResource;
use App\Traits\ApiResponses;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use App\Services\PointInPolygonService;
use App\Enums\Observation\PolygonQuery;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Http;
use Throwable;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Auth;

class ObservationController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->success(
            ObservationResource::collection(Observation::all()),
            Response::HTTP_OK
        );
    }

    public function userObservations(Request $request)
    {
        return $this->success(
            UserObservationsResource::collection($request->user()->observations),
            Response::HTTP_OK
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreObservationRequest $request)
    {
        try {

            // validate request
            $validated = $request->validated();

            // check if observations contain images
            if ($request->hasFile('images')) {

                // get the images from the request
                $images = $request->file('images');

                // create a folder for the user in the storage
                $folder = "users/" . $request->user()->id;

                // iterate over the images and store them in the storage
                foreach ($images as $key => $image) {

                    // get image content
                    $imageContent = file_get_contents($image->getRealPath());

                    // create a rekognition client to analyse the image
                    $rekognition = new RekognitionClient([
                        'version' => 'latest',
                        'region' => 'eu-central-1',
                        'credentials' => [
                            'key' => env('AWS_ACCESS_KEY_ID'),
                            'secret' => env('AWS_SECRET_ACCESS_KEY'),
                        ],
                    ]);

                    // call the detectModerationLabels method to check if the image contains any explicit content
                    $result = $rekognition->detectModerationLabels([
                        'Image' => ['Bytes' => $imageContent],
                    ]);

                    // Call the DetectFaces method to check if the image contains any faces
                    $result_face_detection = $rekognition->detectFaces([
                        'Image' => [
                            'Bytes' => $imageContent,
                        ],
                        'Attributes' => ['ALL'], // Use 'ALL' to get detailed facial attributes, 'DEFAULT' for simpler details
                    ]);

                    // get the moderation labels
                    $labels = $result['ModerationLabels'];

                    // get the face details
                    $face_details = $result_face_detection['FaceDetails'];

                    // check if any inappropriate content labels were detected
                    // or if any faces were detected
                    if (!empty($labels)) {
                        // Handle the detection of inappropriate content
                        // For example, reject the upload or flag for manual review
                        // in this case: save generic 'removed_image_fallback' instead of user uploaded file
                        Arr::set($validated, 'images.' . $key, 'https://soundcollectbucket.s3.eu-central-1.amazonaws.com/users/image_filter_fallback/removed_image_fallback.png');
                    } elseif (!empty($face_details)) {
                        Arr::set($validated, 'images.' . $key, 'No human face should be visible on the picture.');
                    } else {
                        // store the image in the storage
                        $url_images = Storage::put($folder, $image, 'public');

                        // add the url of the image to the validated array
                        Arr::set($validated, 'images.' . $key, 'https://soundcollectbucket.s3.eu-central-1.amazonaws.com/' . $url_images);
                    }
                }
            }

            // wrap the call to the OpenWeather API in a try/catch block to handle and have error logs
            // because Laravel's HTTP client wrapper does not throw exceptions on client or server errors (400 and 500 level responses from servers)
            try {
                $response = Http::openWeather()->get(
                    '/',
                    [
                        'lat' => $validated['latitude'],
                        'lon' => $validated['longitude'],
                    ]
                );

                // Immediately execute the given callback if there was a client or server error
                $response->onError(fn () => $response->throw());

                $data = $response->object();

                Arr::set($validated, 'wind_speed', $data->wind->speed);
                Arr::set($validated, 'humidity', $data->main->humidity);
                Arr::set($validated, 'temperature', $data->main->temp);
                Arr::set($validated, 'pressure', $data->main->pressure);
            } catch (\Illuminate\Http\Client\RequestException $err) {
                // to report an exception but continue handling the current request
                report($err);

                // return false;
            }

            // call to sightengine API to check if text containes any inappropriate content
            try {
                $inappropriate_text_paramaters = array(
                    'text' => $validated['protection'],
                    'lang' => 'en,es',
                    'models' => 'general',
                    'mode' => 'ml',
                    'api_user' => env('SIGHTENGINE_API_USER'),
                    'api_secret' => env('SIGHTENGINE_API_SECRET'),
                );

                // this example uses cURL
                $check_text = curl_init('https://api.sightengine.com/1.0/text/check.json');
                curl_setopt($check_text, CURLOPT_POST, true);
                curl_setopt($check_text, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($check_text, CURLOPT_POSTFIELDS, $inappropriate_text_paramaters);
                $response_text_check = curl_exec($check_text);
                curl_close($check_text);

                $output_text_modification = json_decode($response_text_check, true);

                // check if the response is successful
                if ($output_text_modification['status'] === 'success') {

                    //  check if received response contains any inappropriate content
                    if ($output_text_modification['moderation_classes']['sexual'] || $output_text_modification['moderation_classes']['discriminatory'] || $output_text_modification['moderation_classes']['insulting'] || $output_text_modification['moderation_classes']['violent'] || $output_text_modification['moderation_classes']['toxic'] || $output_text_modification['moderation_classes']['self-harm'] > 0) {

                        // modify user input and replace it with generic message
                        Arr::set($validated, 'protection', 'User provided text modified for inappropriate content.');
                    } else {
                        // Arr::set($validated, 'protection', '$output_text_modification[moderation_classes][sexual] is.' . $output_text_modification['moderation_classes']['sexual']);
                    }
                }
            } catch (\Throwable $th) {
                // Arr::set($validated, 'protection', 'error when calling curl language filter: ' . $th);
            }

            // make http call to timezone api on this url http://api.timezonedb.com/v2.1/get-time-zone?key=YOUR_API_KEY&format=json&by=position&lat=40.689247&lng=-74.044502
            // to get the timezone of the user and then convert the time to the user's local time
            try {
                $local_time_api_response = Http::get('http://api.timezonedb.com/v2.1/get-time-zone', [
                    'key' => '1XUYSIWVPKW6',
                    'format' => 'json',
                    'by' => 'position',
                    'lat' => $validated['latitude'],
                    'lng' => $validated['longitude'],
                ]);

                // convert response into object
                $local_time_api_response_object = $local_time_api_response->object();

                // add the user_local_time parameter to the validated array
                Arr::set($validated, 'user_local_time', $local_time_api_response_object->formatted);
            } catch (\Throwable $th) {
                // return $this->error('Error when calling user_local_time api: ' . $th, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $observation = Observation::create($validated);

            if (array_key_exists('sound_types', $validated)) { // En realidad no hace falta esta comprobación porque "sound_types" es requerido pero por si acaso.
                $observation->types()->attach($validated['sound_types']);
            }

            if (array_key_exists('segments', $validated)) {
                $observation->segments()->createMany($validated['segments']);
            }

            return $this->success(
                new ObservationResource($observation->fresh()->load('segments')),
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            return $this->error('Error when calling user_local_time apiGeneral error when created observation is: ' . $th, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Observation $observation)
    {
        return $this->success(
            new ObservationResource($observation),
            Response::HTTP_OK
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Observation $observation)
    {
        return 'update';
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Observation $observation)
    {


        if ($observation->user_id !== auth()->user()->id && (Auth::guard('sanctum')->user() instanceof \App\Models\AdminUser) !== true) {
            return $this->error(
                'You can only delete your own observations',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $observation->delete();

        return $this->success(
            $observation->id,
            Response::HTTP_OK
        );
    }

    public function polygonShow(Request $request, PointInPolygonService $pointInPolygonService)
    {
        // We validate that the request has the required fields
        $request->validate([
            "concern" => ['required', new Enum(PolygonQuery::class)],
            'polygon' => ['required', 'array'],
            'polygon.*' => ['required', 'string'],
            'interval'  => ['required', 'array'],
            'interval.*' => ['required', 'date_format:H:i:s'],
            'interval.end' => ['after_or_equal:interval.start'],
        ]);

        $start = $request->interval['start'];
        $end = $request->interval['end'];

        // We use whereTime to filter the observations that are within the TIME interval (we do not care about the date)
        $observations = Observation::whereBetween('Leq', [20, 80])->whereTime('created_at', '>=', $start)->whereTime('created_at', '<=', $end)->get();

        $observationsFiltered = $observations->filter(
            fn ($observation) =>
            // We use the pointInPolygon method to filter the observations that are within the polygon, passing string, array args and comparing the result with the concern requested
            $pointInPolygonService->pointInPolygon(
                sprintf("%s %s", $observation->longitude, $observation->latitude),
                $request->polygon
            ) === $request->concern
        );

        return ObservationResource::collection($observationsFiltered);
    }

    public function polygonShowIntervalDateFilter(Request $request, PointInPolygonService $pointInPolygonService){
        // We validate that the request has the required fields
        $request->validate([
            "concern" => ['required', new Enum(PolygonQuery::class)],
            'polygon' => ['required', 'array'],
            'polygon.*' => ['required', 'string'],
            'interval'  => ['required', 'array'],
            'interval.*' => ['required', 'date_format:Y-m-d H:i:s'],
            'interval.end' => ['after_or_equal:interval.start'],
        ]);

        $start = $request->interval['start'];
        $end = $request->interval['end'];

        $observations = Observation::whereBetween('Leq', [20, 80])->where('created_at', '>=', $start)->where('created_at', '<=', $end)->get();

        $observationsFiltered = $observations->filter(
            fn ($observation) =>
            // We use the pointInPolygon method to filter the observations that are within the polygon, passing string, array args and comparing the result with the concern requested
            $pointInPolygonService->pointInPolygon(
                sprintf("%s %s", $observation->longitude, $observation->latitude),
                $request->polygon
            ) === $request->concern
        );

        return ObservationResource::collection($observationsFiltered);
    }
    public function geopackage(Request $request)
    {

        // Ruta del archivo GPKG a crear
        $outputPath = storage_path('app/public/observations.gpkg');

        // Obtener geojson
        $geojson = $request->geojson['features'];

        // Convertir a JSON
        $jsonContent = json_encode(['type' => 'FeatureCollection', 'features' => $geojson], JSON_PRETTY_PRINT);

        // Guardar en archivo
        $geojsonPath = storage_path('app/public/observacions.geojson');
        file_put_contents($geojsonPath, $jsonContent);

        // Comando para convertir archivo JSON a GPKG
        $command = "ogr2ogr -f 'GPKG' $outputPath $geojsonPath";
        shell_exec($command);

        return response()->download($outputPath);

    }

    public function KeyholeMarkupLanguage(Request $request)
    {

        // Ruta del archivo KML a crear
        $outputPath = storage_path('app/public/observations.kml');

        // Obtener geojson
        $geojson = $request->geojson['features'];

        // Convertir a JSON
        $jsonContent = json_encode(['type' => 'FeatureCollection', 'features' => $geojson], JSON_PRETTY_PRINT);

        // Guardar en archivo
        $geojsonPath = storage_path('app/public/observacions.geojson');
        file_put_contents($geojsonPath, $jsonContent);

        // Comando para convertir archivo JSON a KML
        $command = "ogr2ogr -f 'KML' $outputPath $geojsonPath";
        shell_exec($command);

        return response()->download($outputPath);

    }

    public function downloadAsCsv(Request $request, PointInPolygonService $pointInPolygonService)
    {
        try {

            // $request->polygon = ["2.0214844 41.545589", "1.8292236 41.1538424", "2.4581909 41.2798705", "2.0214844 41.545589"];

            $request->polygon = explode(',', $request->polygon);

            // return ($request->polygon);

            // We use whereTime to filter the observations that are within the TIME interval (we do not care about the date)
            $observations = Observation::whereBetween('Leq', [20, 80])->get();

            $observationsFiltered = $observations->filter(
                fn ($observation) =>
                // We use the pointInPolygon method to filter the observations that are within the polygon, passing string, array args and comparing the result with the concern requested
                $this->pointInPolygon(
                    sprintf("%s %s", $observation->longitude,  $observation->latitude),
                    $request->polygon
                ) === 'inside'
            );

            $observations = ObservationResource::collection($observationsFiltered);

            // Fetch observations from the database
            // $observations = DB::table('observations')->get();
            // $observations = ObservationResource::collection(Observation::all());

            // Convert observations to CSV format
            $csvContent = "Observation_id,LAeq,Longitude,Latitude\n"; // Example CSV header
            foreach ($observations as $observation) {
                $csvContent .= "{$observation->id},{$observation->Leq},{$observation->longitude},{$observation->latitude}\n";
            }

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="observations.csv"');
        } catch (\Throwable $th) {
            return $this->error('error when downloading csv is ' . $th);
        }
    }

    var $pointOnVertex = true; // Check if the point sits exactly on one of the vertices?

    function pointInPolygon($point, $polygon, $pointOnVertex = true)
    {
        $this->pointOnVertex = $pointOnVertex;

        // Transform string coordinates into arrays with x and y values
        $point = $this->pointStringToCoordinates($point);

        $vertices = array();
        foreach ($polygon as $vertex) {
            $vertices[] = $this->pointStringToCoordinates($vertex);
        }

        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
            return "vertex";
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $vertices_count = count($vertices);

        for ($i = 1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];
            if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
                return "boundary";
            }
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) {

                $xinters = (int)(($point['y'] - (int)$vertex1['y']) * ((int)$vertex2['x'] - (int)$vertex1['x']) / ((int)$vertex2['y'] - (int)$vertex1['y']) + (int)$vertex1['x']);

                if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                    return "boundary";
                }
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        if ($intersections % 2 != 0) {
            return "inside";
        } else {
            return "outside";
        }
    }

    function pointOnVertex($point, $vertices)
    {
        foreach ($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }
    }

    function pointStringToCoordinates($pointString)
    {
        $coordinates = explode(" ", $pointString);
        return array("x" => $coordinates[0], "y" => $coordinates[1]);
    }

    public function trashed(){
        return $this->success(
            ObservationResource::collection(Observation::onlyTrashed()->get()),
            Response::HTTP_OK
        );
    }

    public function restore($id){
        $observation = Observation::withTrashed()->find($id)->restore();
        return $this->success(
            new ObservationResource($observation),
            Response::HTTP_OK
        );
    }

    public function addGPKP(Request $request)
    {
        $file = $request->file('file'); // Recoge el archivo
        $fileName = $file->getClientOriginalName();
        $fileData = file_get_contents($file->getRealPath());
        $gpkgPath = storage_path('app/public/layer.gpkg');
        file_put_contents($gpkgPath, $fileData);
        $outputGeojson = storage_path('app/public/layer.geojson');


        // Comando ogrinfo para detectar el CRS y listar capas
        $processInfo = new Process(['ogrinfo', $gpkgPath, '-al', '-so']);
        try {
            $processInfo->mustRun();
            $output = $processInfo->getOutput();
        } catch (ProcessFailedException $exception) {
            return response()->json(['error' => 'Error al obtener información del GPKG'], 500);
        }

        // Extraer el nombre de la primera capa
        preg_match('/Layer name: (\w+)/', $output, $matches);
        if (isset($matches[1])) {
            $layerName = $matches[1]; // Nombre de la primera capa
        } else {
            return response()->json(['error' => 'No se pudo encontrar ninguna capa en el archivo GPKG'], 500);
        }

        // Verifica si el CRS es EPSG:4326
        if (strpos($output, 'EPSG:4326') === false) {
            // No es EPSG:4326, así que convertimos
            $processConvert = new Process(['ogr2ogr', '-f', 'GeoJSON', '-t_srs', 'EPSG:4326', $outputGeojson, $gpkgPath, $layerName]);

            try {
                $processConvert->mustRun();
            } catch (ProcessFailedException $exception) {
                return response()->json(['error' => 'Error al convertir GPKG a GeoJSON'], 500);
            }
        } else {
            // Ya está en EPSG:4326, simplemente convertimos a GeoJSON
            $processConvert = new Process(['ogr2ogr', '-f', 'GeoJSON', $outputGeojson, $gpkgPath, $layerName]);

            try {
                $processConvert->mustRun();
            } catch (ProcessFailedException $exception) {
                return response()->json(['error' => 'Error al convertir GPKG a GeoJSON'], 500);
            }
        }

        // Devolver el archivo GeoJSON
        return response()->file($outputGeojson);
    }

}
