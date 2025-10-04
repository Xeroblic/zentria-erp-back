<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('branches', [BranchController::class, 'store'])
        ->middleware('can:branch.create');

    Route::get('branches', [BranchController::class, 'index'])
        ->middleware('can:branch.view');

    Route::put('branches/{id}', [BranchController::class, 'update'])
        ->middleware('can:branch.edit');

    Route::delete('branches/{id}', [BranchController::class, 'destroy'])
        ->middleware('can:branch.delete');
    
    Route::get('branches/{id}', [BranchController::class, 'show'])
        ->middleware('can:branch.view');
});