<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Notifications\PreferencesController;
use App\Http\Controllers\Notifications\AdminRoleBundlesController;
use App\Http\Controllers\Notifications\EventsTestController;
use App\Http\Controllers\Notifications\StreamController;

Route::middleware(['auth:api'])->group(function () {
    Route::get('me/notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/{id}/ack', [NotificationController::class, 'ack']);
    Route::post('notifications/{id}/assign', [NotificationController::class, 'assign']);

    Route::get('me/notification-preferences', [PreferencesController::class, 'index']);
    Route::put('me/notification-preferences', [PreferencesController::class, 'update']);

    Route::get('admin/role-bundles/{roleId}', [AdminRoleBundlesController::class, 'show']);
    Route::put('admin/role-bundles/{roleId}', [AdminRoleBundlesController::class, 'update']);

    Route::post('events/test', [EventsTestController::class, 'store']);

    Route::get('me/notifications/stream', [StreamController::class, 'stream']);
});

