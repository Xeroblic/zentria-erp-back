<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::middleware(['auth:api', 'can:view-user'])->group(function () {
//     Route::get('/users', [UserController::class, 'index'])->name('users.index');
//     Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
//      Route::put('/users/{id}/roles', [UserController::class, 'updateRoles'])
//         ->middleware('can:edit-roles-user');
// });

// Rutas aisladas para actualizar solo la comuna
Route::middleware(['auth:api'])->group(function () {
    // Admins o con permiso user.edit pueden actualizar la comuna de cualquier usuario
    Route::patch('/users/{id}/commune', [UserController::class, 'updateCommune'])->name('users.updateCommune');
    // El propio usuario puede actualizar su comuna sin enviar ID
    Route::patch('/me/commune', [UserController::class, 'updateMyCommune'])->name('users.updateMyCommune');
});
