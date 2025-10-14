<?php
use App\Http\Controllers\Api\UserAvatarController;

Route::middleware(['auth:api'])->group(function () {
    Route::get('/users/{user}/avatar',  [UserAvatarController::class, 'show'])
        ->name('users.avatar.show');

    Route::post('/users/{user}/avatar',  [UserAvatarController::class, 'update'])
        ->name('users.avatar.update');

    Route::delete('/users/{user}/avatar', [UserAvatarController::class, 'destroy'])
        ->name('users.avatar.destroy');
});