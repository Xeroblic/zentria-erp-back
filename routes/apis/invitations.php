<?php
use App\Http\Controllers\Api\UserInvitationController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('/user/invite', [UserInvitationController::class, 'invite'])->middleware('can:invite-users');
    Route::get('/user/invitations', [UserInvitationController::class, 'index'])->middleware('can:invite-users');
    Route::delete('/user/invitations/{id}', [UserInvitationController::class, 'destroy'])->middleware('can:invite-users');
});

// Activation flow used by frontend
Route::get('/usuarios/activar/{token}', [UserInvitationController::class, 'showActivation']);
Route::post('/usuarios/activar', [UserInvitationController::class, 'activate']);
