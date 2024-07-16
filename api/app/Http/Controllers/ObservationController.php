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
        $validated = $request->validated();

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $folder = "users/" . $request->user()->id;
            foreach ($images as $key => $image) {
                $url_images = Storage::put($folder, $image, 'public');
                Arr::set($validated, 'images.' . $key, 'https://soundcollectbucket.s3.eu-central-1.amazonaws.com/' . $url_images);
            }
        }

        // We wrap the call to the OpenWeather API in a try/catch block to handle and have error logs
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


        // make http call to timezone api on this url http://api.timezonedb.com/v2.1/get-time-zone?key=YOUR_API_KEY&format=json&by=position&lat=40.689247&lng=-74.044502
        // to get the timezone of the user and then convert the time to the user's local time
        // api key is 1XUYSIWVPKW6
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
        if ($observation->user_id !== auth()->user()->id) {
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
}
