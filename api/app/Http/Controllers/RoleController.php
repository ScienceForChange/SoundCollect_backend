<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponses;
use App\Http\Resources\RoleResource;

class RoleController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('manage-roles');

        // Listar todos los roles con sus permisos
        $roles = Role::with('permissions')->get();

        return $this->success(
            RoleResource::collection($roles),
            Response::HTTP_OK
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create-roles');

        // Crear un nuevo rol
        $role = Role::create(['name' => Str::slug($request->name), 'guard_name' => 'admin']);

        // En caso de llegar con permisos, asignarlos al rol
        if ($request->has('permissions_list')) {
            $role->givePermissionTo($request->permissions_list);
        }
        $role->givePermissionTo('manage-admin');

        return $this->success(
            new RoleResource($role),
            Response::HTTP_OK
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('manage-roles');

        // Mostrar un rol específico
        $role = Role::findOrFail($id)->load('permissions');
        return $this->success(
            new RoleResource($role),
            Response::HTTP_OK
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $this->authorize('update-roles');

        $role = Role::findOrFail($id);

        // Si el rol es superadmin, no se puede modificar
        if ($role->id === 1 || $role->name === 'superadmin') {
            return response()->json(['message' => 'No se puede modificar el rol superadmin'], 403);
        }

        // Actualizar un rol específico
        $role = Role::findOrFail($id);
        $role->update(['name' => Str::slug($request->name)]);
        $role->syncPermissions($request->permissions_list);
        //añadimos el permiso por defecto manage-admin
        $role->givePermissionTo('manage-admin');

        return $this->success(
            new RoleResource($role),
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->authorize('delete-roles');

        $role = Role::findOrFail($id);

        // Si el rol es superadmin, no se puede eliminar
        if ($role->id === 1 || $role->name === 'superadmin') {
            return response()->json(['message' => 'No se puede eliminar el rol superadmin'], 403);
        }

        //Eliminar un rol específico
        $role->delete();
        return response()->json(null, 204);
    }
}
