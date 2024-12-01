<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\AdminUser;
use App\Http\Resources\AdminUserResource;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtenemos todos los usuarios
        $users = AdminUser::all();
        return AdminUserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminUserRequest $request)
    {
        $adminUser = AdminUser::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'avatar_id' => 1,
        ]);

        $adminUser->assignRole($request->roles_list);

        return new AdminUserResource($adminUser);

    }

    /**
     * Display the specified resource.
     */
    public function show(AdminUser $user)
    {
        if (!$user) {
            abort(404, 'User not found');
        };

        return new AdminUserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminUserRequest $request, AdminUser $user)
    {

        // Obtenemos el primer usuario con el rol superadmin
        $superadmin = AdminUser::role('superadmin')->first();
        // Si el usuario a actualizar es el superadmin, no permitimos actualizar el usuario
        if ($user->id === $superadmin->id) {
            // Si el usuario superadmin y coincide con el usuario logueado, permitimos la actualización de mail y password
            if ($user->id === auth('sanctum')->user()->id) {
                $user->update([
                    'email' => $request->email,
                ]);
                // Si se envió un password, lo actualizamos
                if ($request->password) {
                    $user->update([
                        'password' => Hash::make($request->password),
                    ]);
                }
            }
            return new AdminUserResource($user);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Si se envió un password, lo actualizamos
        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        $user->syncRoles($request->roles_list);

        return new AdminUserResource($user);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( AdminUser $user)
    {

        // Obtenemos el primer usuario con el rol superadmin
        $superadmin = AdminUser::role('superadmin')->orderBy('created_at')->first();
        // Si el usuario a actualizar es el superadmin, no permitimos borrar el usuario

        if ($user->id === $superadmin->id) {
            return response()->json(['message' => 'No se puede eliminar el usuario superadmin'], 403);
        }

        // Eliminamos el usuario permanentemente eludimos el softdelete
        $user->forceDelete();

        return response()->json(null, 204);

    }
}
