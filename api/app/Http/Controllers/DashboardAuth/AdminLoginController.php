<?php

namespace App\Http\Controllers\DashboardAuth;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\UserResource;
use App\Models\AdminUser;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    use ApiResponses;

    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => ['required','email'],
            'password' => 'required',
        ]);

        $user = AdminUser::query()
          ->where('email', $request->email)
          ->first();

        if (
            !$user ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ) {
            // Check if the user is not an admin user
            $user = User::query()
            ->where('email', $request->email)
            ->first();

            if (
                !$user ||
                !Hash::check(
                    $request->password,
                    $user->password
                )
            ) {
                throw ValidationException::withMessages([
                    'message' => [
                    'The provided credentials are incorrect.'
                    ],
                ]);
            }
            else{
                // Return the user as a normal user
                return $this->success(
                    [
                        'user'  => new UserResource($user),
                        'token' => $user->createToken(request('email'))->plainTextToken
                    ],
                    Response::HTTP_OK
                );
            }
        }
        // Return the user as an admin user
        return $this->success(
            [
                'user'  => new AdminUserResource($user),
                'token' => $user->createToken(request('email'))->plainTextToken
            ],
            Response::HTTP_OK
        );
    }

}
