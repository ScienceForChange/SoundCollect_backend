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

        $user->delete();
        return response()->json(null, 204);

    }
}
