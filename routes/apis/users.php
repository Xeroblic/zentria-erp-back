<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'can:user.view'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
     Route::put('/users/{id}/roles', [UserController::class, 'updateRoles'])
        ->middleware('can:user.edit-roles');
});
