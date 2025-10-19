<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubsidiaryController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('subsidiaries', [SubsidiaryController::class, 'store'])
        ->middleware('can:create-subsidiary');

    Route::get('subsidiaries', [SubsidiaryController::class, 'index'])
        ->middleware('can:viewAny,App\\Models\\Subsidiary');

    Route::get('subsidiaries/{id}', [SubsidiaryController::class, 'show']);

    Route::put('subsidiaries/{id}', [SubsidiaryController::class, 'update'])
        ->middleware('can:edit-subsidiary');

    Route::delete('subsidiaries/{id}', [SubsidiaryController::class, 'destroy'])
        ->middleware('can:delete-subsidiary');

    // Actualizar solo la comuna de la subsidiaria
    Route::patch('subsidiaries/{id}/commune', [SubsidiaryController::class, 'updateCommune'])
        ->middleware('can:edit-subsidiary')
        ->name('subsidiaries.updateCommune');
});
