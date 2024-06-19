<?php

use App\Http\Controllers\AudioProcessingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\SFCController;
use App\Http\Controllers\MapController;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', \App\Http\Controllers\Auth\RegisteredUserController::class)
                ->name('register');

Route::post('/login', \App\Http\Controllers\Auth\LoginController::class)
                ->name('login');

Route::post('/verify-email', \App\Http\Controllers\Auth\VerifyEmailController::class)
                ->middleware(['throttle:6,1'])
                ->name('verification.verify');

Route::post('/reset-password', \App\Http\Controllers\Auth\NewPasswordController::class)
                ->middleware(['guest:sanctum'])
                ->name('password.store');

Route::post('/logout', \App\Http\Controllers\Auth\LogoutController::class)
                ->middleware(['auth:sanctum'])
                ->name('logout');

//TODO: crear un controlador de usuarios loggeados
Route::middleware(['auth:sanctum'])
   ->group(function() {
        Route::get('/user', function (Request $request) {
            return new JsonResponse([
                'status' => 'success',
                'data' => UserResource::make($request->user()),
            ], 200);
        });

        Route::patch('/user/profile/edit', \App\Http\Controllers\Auth\EditUserController::class)->name('profile.edit');
        Route::delete('/user/profile/delete', \App\Http\Controllers\Auth\DeleteUserController::class)->name('profile.delete');
        // Route::post('/user/autocalibration', \App\Http\Controllers\AutocalibrationController::class)->name('autocalibration.update');
    });

Route::middleware(['auth:sanctum', 'verified'])
   ->group(function() {
        Route::get('/user/profile', function () {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'email_verified' => 'true',
                ],
            ]);
        })->name('profile');
});

Route::controller(\App\Http\Controllers\Auth\AuthOtpController::class)
        ->middleware(['guest', 'throttle:3,1'])
        ->group(function(){
    Route::post('/otp/generate', 'generate')->name('otp.generate');
});

Route::name('observations.')
    ->prefix('observations')
    ->group(function () {
    Route::get('/', [ObservationController::class, 'index'])->name('index');
    Route::get('/{observation}', [ObservationController::class, 'show'])->name('show');
    Route::post('/in-polygon', [ObservationController::class, 'polygonShow'])->name('map.show');
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/', [ObservationController::class, 'store'])->name('store');
        Route::delete('/{observation}', [ObservationController::class, 'destroy'])->name('destroy');
    });
});

Route::get('/map/observations', [MapController::class, 'index'])->name('map.index');

Route::get('/terms', [SFCController::class, 'terms'])->name('terms');

Route::post('/audio-process', [AudioProcessingController::class, 'process'])
        ->middleware(['auth:sanctum'])->name('audio-process');

Route::get('/user/observations', [ObservationController::class, 'userObservations'])
        ->middleware(['auth:sanctum'])->name('user-observations');

Route::post('/user/autocalibration', \App\Http\Controllers\AutocalibrationController::class)->middleware(['auth:sanctum'])->name('autocalibration.update');
