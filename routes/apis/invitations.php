<?php
use App\Http\Controllers\Api\UserInvitationController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('/user/invite', [UserInvitationController::class, 'invite'])->middleware('can:invite-users');
});

// Activation flow used by frontend
Route::get('/usuarios/activar/{token}', [UserInvitationController::class, 'showActivation']);
Route::post('/api/usuarios/activar', [UserInvitationController::class, 'activate']);
