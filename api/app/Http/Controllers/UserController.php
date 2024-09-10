<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // obtenemos todos los usuarios
        $users = User::all();
        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {

        if (!$user) {
            abort(404, 'User not found');
        };

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Hacemos soft delete del usuario
        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * List trashed users
     */
    public function trashed()
    {
        // Obtenemos los usuarios eliminados
        $users = User::onlyTrashed()->get();
        return UserResource::collection($users);
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        // Restauramos el usuario
        User::withTrashed()->find($id)->restore();
        return response()->json(null, 204);
    }
}
