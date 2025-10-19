<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('branches', [BranchController::class, 'store'])
        ->middleware('can:create-branch');

    Route::get('branches', [BranchController::class, 'index'])
        ->middleware('can:view-branch');

    Route::put('branches/{id}', [BranchController::class, 'update'])
        ->middleware('can:edit-branch');

    Route::delete('branches/{id}', [BranchController::class, 'destroy'])
        ->middleware('can:delete-branch');
    
    Route::get('branches/{id}', [BranchController::class, 'show'])
        ->middleware('can:view-branch');

    // Actualizar solo la comuna de la sucursal
    Route::patch('branches/{id}/commune', [BranchController::class, 'updateCommune'])
        ->middleware('can:edit-branch')
        ->name('branches.updateCommune');
});
