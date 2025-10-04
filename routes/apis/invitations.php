<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvitationController;

/*
|--------------------------------------------------------------------------
| Rutas de Sistema de Invitaciones
|--------------------------------------------------------------------------
| 
| Sistema modular completo para gestión de invitaciones de usuarios
| con UID + Token para seguridad mejorada y flujo completo de activación
|
*/

// Rutas públicas (sin autenticación)
Route::prefix('invitations')->group(function () {
    
    // Obtener información de invitación para formulario de activación
    Route::get('{uid}/{token}/info', [InvitationController::class, 'getInvitationInfo'])
        ->name('invitations.info');
    
    // Aceptar invitación y crear cuenta
    Route::post('{uid}/{token}/accept', [InvitationController::class, 'accept'])
        ->name('invitations.accept');
});

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth:api'])->prefix('invitations')->group(function () {
    
    // CRUD básico de invitaciones
    Route::get('/', [InvitationController::class, 'index'])
        ->name('invitations.index')
        ->middleware('can:invite-users');
        
    Route::post('/', [InvitationController::class, 'store'])
        ->name('invitations.store')
        ->middleware('can:invite-users');
        
    Route::get('{id}', [InvitationController::class, 'show'])
        ->name('invitations.show')
        ->middleware('can:invite-users');
        
    // Acciones específicas
    Route::post('{id}/resend', [InvitationController::class, 'resend'])
        ->name('invitations.resend')
        ->middleware('can:invite-users');
        
    Route::delete('{id}/cancel', [InvitationController::class, 'cancel'])
        ->name('invitations.cancel')
        ->middleware('can:invite-users');
        
    // Estadísticas y reportes
    Route::get('stats/summary', [InvitationController::class, 'stats'])
        ->name('invitations.stats')
        ->middleware('can:invite-users');
});
