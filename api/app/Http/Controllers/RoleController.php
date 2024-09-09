<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class RoleController extends Controller
{
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
        $this->authorize('manage-roles');

        // Listar todos los roles con sus permisos
        $roles = Role::with('permissions')->get();
        return response()->json($roles);

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
        if ($request->has('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        return response()->json($role);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('manage-roles');

        // Mostrar un rol específico
        $role = Role::findOrFail($id);
        return response()->json($role);
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
        $role->update($request->all());
        return response()->json($role);
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
