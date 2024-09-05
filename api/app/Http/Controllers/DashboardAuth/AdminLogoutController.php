<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponses;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    use ApiResponses;

    public function __invoke(Request $request)
    {
        request()
            ->user()
            ->currentAccessToken()
            ->delete();


        return $this->success(
            [],
            Response::HTTP_OK);
    }
}

//  fsgdhsdg