<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPersonalizationController;
use Tymon\JWTAuth\Facades\JWTAuth;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación e Invitaciones de Usuario
|--------------------------------------------------------------------------
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login',    [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:api']], function () {

    // ---------- USERS ----------
    // Invitaciones unificadas en routes/apis/invitations.php (POST /api/user/invite)
    Route::get ('users',   [AuthController::class,'listUsers'])->middleware('can:view-user');
    Route::put ('users/{id}', [AuthController::class,'updateUser'])->middleware('can:edit-user');
    Route::delete('users/{id}', [AuthController::class,'deleteUser'])->middleware('can:delete-user');

    // ---------- ACTIVATION ----------
    // Activación pública gestionada en routes/apis/invitations.php

    // ---------- PROFILE ----------
    Route::get ('perfil', [AuthController::class,'perfil']);
    Route::middleware(['auth:api'])->get('/me', [UserController::class, 'me']);
    
    // ---------- COMPANY MANAGEMENT ----------
    Route::get('available-companies', [AuthController::class, 'getAvailableCompanies']);


    // ---------- PERSONALIZACIÓN ----------
    Route::get ('user/personalization', [UserPersonalizationController::class,'show'])
        ->middleware('can:show-profile');
    Route::put ('user/personalization', [UserPersonalizationController::class,'update'])
        ->middleware('can:edit-profile');


    // ---------- TOKENS ----------
    Route::post('refresh', function () {
        return response()->json(['token' => JWTAuth::refresh()]);
    });
    Route::post('logout',  [AuthController::class,'logout']);
});
