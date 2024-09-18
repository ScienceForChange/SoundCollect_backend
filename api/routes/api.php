<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

use App\Http\Controllers\AudioProcessingController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\SFCController;
use App\Http\Controllers\MapController;
use App\Http\Resources\UserResource;
use App\Http\Resources\AdminUserResource;
use App\Http\Controllers\StudyZoneController;
use App\Http\Controllers\PolylineObservationController;



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
    ->group(function () {
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
    ->group(function () {
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
    ->group(function () {
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

Route::get('/polyline_observations', [PolylineObservationController::class, 'index'])->name('polyline_observations');

// add delete account page for google play store, that returns simple text response
Route::get('/delete-account', function () {
    return ('You can delete your account from the application itselft  "Proflie" -> "Delete account" OR send bearer token to this URL "soundcollectapp.com/api/user/profile/delete" form authenticated user to remove your account.');
})->name('delete-account');





//dashboard
Route::prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {

        Route::post('/register', \App\Http\Controllers\Auth\RegisteredUserController::class)
            ->name('register');

        Route::post('/login', \App\Http\Controllers\DashboardAuth\AdminLoginController::class)
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


        Route::middleware(['auth:sanctum'])
            ->group(function () {

                //devolvemos el usuario loggeado
                Route::get('/user', function (Request $request) {

                    if (Auth::user() instanceof \App\Models\AdminUser) {
                        return new JsonResponse([
                            'status' => 'success',
                            'data' => AdminUserResource::make(Auth::user()),
                        ], 200);
                    }

                    return new JsonResponse([
                        'status' => 'success',
                        'data' => UserResource::make(Auth::user()),
                    ], 200);

                });

                Route::name('observations.')
                    ->prefix('observations')
                    ->group(function () {
                    Route::get('/', [ObservationController::class, 'index'])->name('index');
                    Route::get('/{observation}', [ObservationController::class, 'show'])->name('show');
                    Route::post('/in-polygon', [ObservationController::class, 'polygonShow'])->name('map.show');
                    Route::post('/in-polygon-date-filter', [ObservationController::class, 'polygonShowIntervalDateFilter'])->name('map.show-date-filter');
                });

                Route::get('/study-zone', [StudyZoneController::class, 'index'])->name('index');
                Route::get('/study-zone/{studyZone}', [StudyZoneController::class, 'show'])->name('show');
                Route::post('/geopackage', [ObservationController::class, 'geopackage'])->name('geopackage');
                Route::post('/kml', [ObservationController::class, 'KeyholeMarkupLanguage'])->name('kml');

            });

        //adminPanel
        Route::middleware(['auth:sanctum', 'auth.admin', 'can:manage-admin'])
            ->name('admin-panel.')
            ->prefix('admin-panel')
            ->group(function () {

                Route::middleware(['can:manage-study-zones'])
                    ->prefix('study-zone')
                    ->name('study-zone.')
                    ->group(function (){
                        Route::get('/', [StudyZoneController::class, 'index'])->name('index');
                        Route::get('/{studyZone}', [StudyZoneController::class, 'show'])->name('show');
                        Route::post('/', [StudyZoneController::class, 'store'])->name('store')->middleware(['can:create-study-zones']);
                        Route::patch('/{studyZone}', [StudyZoneController::class, 'update'])->name('update')->middleware(['can:update-study-zones']);
                        Route::patch('/{studyZone}/toggle', [StudyZoneController::class, 'toggleVisibility'])->name('toggle-visibility')->middleware(['can:update-study-zones']);
                        Route::delete('/{studyZone}', [StudyZoneController::class, 'destroy'])->name('destroy')->middleware(['can:delete-study-zones']);
                    });

                // Gestión de roles solo para superadmin
                Route::middleware(['can:manage-roles'])
                    ->prefix('roles')
                    ->name('roles.')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\RoleController::class, 'index'])->name('index');
                        Route::get('/{role}', [\App\Http\Controllers\RoleController::class, 'show'])->name('show');
                        Route::post('/', [\App\Http\Controllers\RoleController::class, 'store'])->name('store')->middleware(['can:create-roles']);
                        Route::patch('/{role}', [\App\Http\Controllers\RoleController::class, 'update'])->name('update')->middleware(['can:update-roles']);
                        Route::delete('/{role}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('destroy')->middleware(['can:delete-roles']);
                    });

                // Gestión de permisos solo para superadmin
                Route::middleware(['can:manage-roles'])
                    ->prefix('permissions')
                    ->name('permissions.')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\PermissionController::class, 'index'])->name('index');
                    });

                Route::middleware(['can:manage-app-users'])
                    ->prefix('users')
                    ->name('users.')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
                        Route::get('/trashed', [\App\Http\Controllers\UserController::class, 'trashed'])->name('trashed')->middleware(['can:delete-app-users']);
                        Route::get('/{user}', [\App\Http\Controllers\UserController::class, 'show'])->name('show');
                        Route::patch('/restore/{user}', [\App\Http\Controllers\UserController::class, 'restore'])->name('restore')->middleware(['can:delete-app-users']);
                        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy')->middleware(['can:delete-app-users']);
                    });

                Route::middleware(['can:manage-observations'])
                    ->prefix('observations')
                    ->name('observations.')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\ObservationController::class, 'index'])->name('index');
                        Route::get('/trashed', [\App\Http\Controllers\ObservationController::class, 'trashed'])->name('trashed')->middleware(['can:delete-observations']);
                        Route::get('/{observation}', [\App\Http\Controllers\ObservationController::class, 'show'])->name('show');
                        Route::patch('/restore/{observation}', [\App\Http\Controllers\ObservationController::class, 'restore'])->name('restore')->middleware(['can:delete-observations']);
                        Route::delete('/{observation}', [\App\Http\Controllers\ObservationController::class, 'destroy'])->name('destroy')->middleware(['can:delete-observations']);
                    });

                Route::middleware(['can:manage-admin-users'])
                    ->prefix('admin-users')
                    ->name('admin-users.')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('index');
                        Route::get('/{user}', [\App\Http\Controllers\AdminUserController::class, 'show'])->name('show');
                        Route::post('/', [\App\Http\Controllers\AdminUserController::class, 'store'])->name('store');
                        Route::patch('/{user}', [\App\Http\Controllers\AdminUserController::class, 'update'])->name('update');
                        Route::delete('/{user}', [\App\Http\Controllers\AdminUserController::class, 'destroy'])->name('destroy');
                    });

                });

    });



