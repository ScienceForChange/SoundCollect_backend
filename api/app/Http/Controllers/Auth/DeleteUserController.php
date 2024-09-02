<?php

namespace App\Http\Controllers\Auth;

use App\Traits\ApiResponses;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DeleteUserController
{
    use ApiResponses;
    public function __invoke()
    {
        $user = auth()->user();

        $user->otp()->delete();

        // $user->delete();
        
        // change user email to unique email
        $user->email = 'deleted_' . rand(1, 1000000) . '_' . $user->email;

        // save changes
        $user->save();

        return $this->success(
            [
                'message' => 'User no longer active.',
            ],
            Response::HTTP_OK
        );
    }
}
