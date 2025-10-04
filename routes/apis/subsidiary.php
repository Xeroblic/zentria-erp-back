<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubsidiaryController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('subsidiaries', [SubsidiaryController::class, 'store'])
        ->middleware('can:subsidiary.create');

    Route::get('subsidiaries', [SubsidiaryController::class, 'index'])
        ->middleware('can:subsidiary.view');

    Route::get('subsidiaries/{id}', [SubsidiaryController::class, 'show'])
        ->middleware('can:subsidiary.view');

    Route::put('subsidiaries/{id}', [SubsidiaryController::class, 'update'])
        ->middleware('can:subsidiary.edit');

    Route::delete('subsidiaries/{id}', [SubsidiaryController::class, 'destroy'])
        ->middleware('can:subsidiary.delete');

    
});
