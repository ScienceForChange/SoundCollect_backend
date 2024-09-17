<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\PermissionResource;
use App\Traits\ApiResponses;

class PermissionController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        // Aplicar el middleware para restringir el acceso solo a superadmins
        $this->middleware('can:super-admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('manage-permissions');
        // Listar todos los permisos
        $permissions = Permission::all();

        return $this->success(
            PermissionResource::collection($permissions),
            Response::HTTP_OK
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create-permissions');
        // Crear un nuevo permiso
        $permission = Permission::create($request->all());
        return response()->json($permission);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('manage-permissions');
        // Mostrar un permiso específico
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('update-permissions');
        // Actualizar un permiso específico
        $permission = Permission::findOrFail($id);
        $permission->update($request->all());
        return response()->json($permission);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete-permissions');
        // Eliminar un permiso específico
        $permission = Permission::findOrFail($id);
        $permission->delete();
        return response()->json($permission);
    }
}
