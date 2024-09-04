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
            if (isset($collaborator['logo_data']) && $collaborator['logo_data'] !== null){
                $collaborators[$key]['logo'] = $this->uploadLogo($collaborator['logo_data'], $studyZone->id);
            }
        }

        $studyZone->collaborators()->createMany(
            $collaborators
        );

        // Guardamos los documentos
        if ($request->documents && count($request->documents) > 0){

            $documents = $request->documents;

            foreach ($documents as $key => $document) {
                $docuemntUploaded = $this->uploadDocument($document['name'], $document['file_data'], $studyZone->id);
                $documents[$key]['file'] = $docuemntUploaded['file'];
                $documents[$key]['type'] = $docuemntUploaded['type'];
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

        // Recopilar los IDs de los colaboradores presentes en el request
        $collaboratorIds = collect($request->input('collaborators'))->pluck('id');

        // Eliminamos el logo de los colaboradores que no están en el request de Storage en caso de que exista
        $collaboratorsToDelete = $studyZone->collaborators()->whereNotIn('id', $collaboratorIds)->get();
        foreach ($collaboratorsToDelete as $collaborator) {
            if($collaborator->logo !== null){
                // Extraemos el dominio de AWS de la URL del logo
                $domain         = env('AWS_URL');
                $fileToDelete   = str_replace($domain, '', $collaborator->logo);
                if(Storage::exists($fileToDelete)){
                    Storage::delete($fileToDelete);
                }
            }
        }

        // Eliminar colaboradores que no están en el request
        $studyZone->collaborators()->whereNotIn('id', $collaboratorIds)->delete();


        foreach ($request->input('collaborators') as $collaborator) {
            // si el colaborador no tiene ID y tiene logo_data, se guarda el logo
            if(!isset($collaborator['id']) && isset($collaborator['logo_data']) && $collaborator['logo_data'] !== null){
                $collaborator['logo'] = $this->uploadLogo($collaborator['logo_data'], $studyZone->id);
            }
            // Si el colaborador tiene un ID y el logo no es null, se guarda el logo
            else if(isset($collaborator['id']) && isset($collaborator['logo_data']) && $collaborator['logo_data'] !== null){
                $collaborator['logo'] = $this->uploadLogo($collaborator['logo_data'], $studyZone->id);
            }
            // Si el colaborador tiene un ID y el logo es null, se elimina el logo
            else if(isset($collaborator['id']) && isset($collaborator['logo']) && $collaborator['logo'] === null){
                if(Storage::exists(Collaborator::find($collaborator['id'])['logo'])){
                    Storage::delete($collaborator['logo']);
                }
            }
            $studyZone->collaborators()->updateOrCreate(
                ['id' => isset($collaborator['id']) ? $collaborator['id'] : null,],
                [
                    'collaborator_name' => $collaborator['collaborator_name'],
                    'collaborator_web'  => isset($collaborator['collaborator_web']) ? $collaborator['collaborator_web'] : null,
                    'contact_name'      => $collaborator['contact_name'],
                    'contact_email'     => $collaborator['contact_email'],
                    'contact_phone'     => $collaborator['contact_phone'],
                    'logo'              => isset($collaborator['logo']) ? $collaborator['logo'] : null,
                ]
            );
        }

        // Recopilar los IDs de los documentos presentes en el request
        $documentIds = collect($request->input('documents'))->pluck('id');

        // Eliminamos los documentos que no están en el request de Storage
        $documentsToDelete = $studyZone->documents()->whereNotIn('id', $documentIds)->get();
        foreach ($documentsToDelete as $document) {
            // Extraemos el dominio de AWS de la URL del documento
            $domain         = env('AWS_URL');
            $fileToDelete   = str_replace($domain, '', $document->file);
            if(Storage::exists($fileToDelete)){
                Storage::delete($fileToDelete);
            }
        }

        // Eliminar documentos que no están en el request
        $studyZone->documents()->whereNotIn('id', $documentIds)->delete();

        foreach ($request->input('documents') as $document) {
            // si el documento no tiene ID y tiene file_data, se guarda el documento
            if(!isset($document['id']) && isset($document['file_data']) && $document['file_data'] !== null){
                $docuemntUploaded = $this->uploadDocument($document['name'], $document['file_data'], $studyZone->id);
                $document['file'] = $docuemntUploaded['file'];
                $document['type'] = $docuemntUploaded['type'];
            }
            // Si el documento tiene un ID y el file_data no es null, se guarda el documento
            else if(isset($document['id']) && isset($document['file_data']) && $document['file_data'] !== null){
                $docuemntUploaded = $this->uploadDocument($document['name'], $document['file_data'], $studyZone->id);
                $document['file'] = $docuemntUploaded['file'];
                $document['type'] = $docuemntUploaded['type'];
            }
            $studyZone->documents()->updateOrCreate(
                ['id' => isset($document['id']) ? $document['id'] : null,],
                [
                    'name' => $document['name'],
                    'file' => isset($document['file']) ? $document['file'] : null,
                    'type' => isset($document['type']) ? $document['type'] : null,
                ]
            );
        }

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

    // Método para subir el logo de un colaborador
    private function uploadLogo($logoData, $studyZoneId)
    {
        $domain     = env('AWS_URL');
        $extension  = explode(';', explode('/', $logoData)[1])[0];
        $fileData   = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$logoData));
        $fileName   = uniqid() . '.' . $extension;
        $path       = 'studyzone/' . $studyZoneId . '/collaborators-logos/' . $fileName;

        if(Storage::exists($path)){
            Storage::delete($path);
        }

        if(Storage::put($path, $fileData, 'public')){
            return $domain . $path;
        }
    }

    // Método para subir un documento
    private function uploadDocument($documentName, $documentData, $studyZoneId)
    {
        $domain     = env('AWS_URL');
        $extension  = explode(';', explode('/', $documentData)[1])[0];
        $fileData   = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$documentData));
        $path       = 'studyzone/' . $studyZoneId . '/documents';
        // Comprobamos si el archivo ya existe y si es así le añadimos la fecha y hora actual
        $fileName   = Storage::exists($path . '/' . Str::slug($documentName).'.'. $extension ) ? Str::slug($documentName) . '-' . date('Ymdhis') . '.' . $extension : Str::slug($documentName) . '.' . $extension;
        $path       = 'studyzone/' . $studyZoneId . '/documents/' . $fileName;

        if(Storage::put($path, $fileData, 'public')){
            return [
                'file' => $domain . $path,
                'type' => $extension
            ];
        }

    }
}
