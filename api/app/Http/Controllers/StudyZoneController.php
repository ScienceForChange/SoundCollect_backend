<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use App\Traits\ApiResponses;
use App\Models\StudyZone;
use App\Http\Requests\StoreStudyZoneRequest;
use App\Http\Requests\UpdateStudyZoneRequest;
use App\Http\Resources\StudyZoneResource;
use App\Services\PointInPolygonService;
use App\Enums\Observation\PolygonQuery;
use Illuminate\Support\Facades\File;
use Throwable;

class StudyZoneController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Recuperamos todos los proyectos del usuario autenticado
        $studyZones = StudyZone::where('user_id', auth()->user()->id)->get();

        return $this->success(
            // Estas clases Resource sirven para poder dar un formato concreto al JSON de respuesta, es muy seguro que cualquier modificación que necesitéis
            // hacer en la respuesta de los proyectos se haga en la clase Resource del model que corresponda
            StudyZoneResource::collection($studyZones)
        ,
        Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudyZoneRequest $request)
    {
        $domain = env('AWS_URL');

        $validated = $request->validated();

        $polygon = new Polygon([
                    new LineString(
                        collect($request->coordinates)->map(function($coordinate) {
                            // Separa cada punto en latitud y longitud
                            list($longitude, $latitude) = explode(' ', $coordinate);

                            return new Point((float)$longitude, (float)$latitude);
                        })->all()
                    )
                ]);
        $studyZone = StudyZone::create([
            'user_id' =>        auth()->user()->id,
            'name' =>           $request->name,
            'description' =>    $request->description,
            'conclusion' =>     $request->conclusion,
            'start_date' =>     new \Carbon\Carbon($request->start_date),
            'end_date' =>       new \Carbon\Carbon($request->end_date),
            'coordinates' =>    $polygon
        ]);

        // Guardamos los colaboradores
        $collaborators = $request->collaborators;
        foreach ($collaborators as $key => $collaborator) {
            if (isset($collaborator['logo']) && $collaborator['logo'] !== null){
                $extension =  explode(';', explode('/', $collaborator['logo'])[1])[0];
                $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$collaborator['logo']));

                $fileName = uniqid() . '.' . $extension;

                $path = 'studyzone/' . $studyZone->id . '/collaborators-logos/' . $fileName;

                if(Storage::exists($path)){
                    Storage::delete($path);
                }

                if(Storage::put($path, $fileData, 'public')){
                    $collaborators[$key]['logo'] = $domain . $path;
                }
            }
        }

        $studyZone->collaborators()->createMany(
            $collaborators
        );

        // Guardamos los documentos
        if ($request->documents && count($request->documents) > 0){
            $documents = $request->documents;
            foreach ($documents as $key => $document) {

                $extension =  explode(';', explode('/', $document['file'])[1])[0];
                $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$document['file']));

                $path = 'studyzone/' . $studyZone->id . '/documents';

                // Comprobamos si el archivo ya existe y si es así le añadimos la fecha y hora actual
                $fileName = Storage::exists($path . '/' . Str::slug($document['name']).'.'. $extension ) ? Str::slug($document['name']) . '-' . date('Ymdhis') . '.' . $extension : Str::slug($document['name']) . '.' . $extension;

                $path = 'studyzone/' . $studyZone->id . '/documents/' . $fileName;

                if(Storage::put($path, $fileData, 'public')){
                    $documents[$key]['file'] = $domain . $path;
                    $documents[$key]['type'] = $extension;
                }

            }
            $studyZone->documents()->createMany(
                $documents
            );
        }

        return $this->success(
            new StudyZoneResource($studyZone),
            Response::HTTP_OK
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(StudyZone $studyZone)
    {
        return $this->success(
            new StudyZoneResource($studyZone),
            Response::HTTP_OK
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StudyZone $studyZone)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreStudyZoneRequest $request, StudyZone $studyZone)
    {
        $validated = $request->validated();

        $polygon = new Polygon([
                    new LineString(
                        collect($request->coordinates)->map(function($coordinate) {
                            // Separa cada punto en latitud y longitud
                            list($longitude, $latitude) = explode(' ', $coordinate);

                            return new Point((float)$longitude, (float)$latitude);
                        })->all()
                    )
                ]);

        $studyZone->update([
            'name' =>           $request->name,
            'description' =>    $request->description,
            'start_date' =>     new \Carbon\Carbon($request->start_date),
            'end_date' =>       new \Carbon\Carbon($request->end_date),
            'coordinates' =>    $polygon
        ]);

        return $this->success(
            new StudyZoneResource($studyZone),
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudyZone $studyZone)
    {
        if($studyZone->user_id !== auth()->user()->id){
            return $this->error(
                'You can only delete your own study zones',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $studyZone->delete();

        return $this->success(
            $studyZone->id,
            Response::HTTP_OK
        );
    }

    // Método para ocultar o mostrar una zona de estudio
    public function toggleVisibility(StudyZone $studyZone, Request $request)
    {
        if (!$studyZone) {
            return response()->json(['error' => 'Zona de estudio no encontrada'], 404);
        }

        if (isset($request->is_visible)) {
            $studyZone->is_visible = $request->is_visible;
        }
        else if (!isset($request->is_visible)) {
            $studyZone->is_visible = !$studyZone->is_visible;
        }
        else {
            return response()->json(['error' => 'Acción no válida'], 400);
        }

        // Guarda el cambio
        $studyZone->save();

         return $this->success(
            $studyZone->id,
            Response::HTTP_OK
        );
    }
}
