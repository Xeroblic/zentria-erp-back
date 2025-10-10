<?php
use App\Http\Controllers\Api\BranchMediaController;

Route::middleware(['auth:api'])->group(function () {
    // Library de la sucursal (subir y listar para el picker)
    Route::post('/branches/{branch}/library/media', [BranchMediaController::class, 'uploadToLibrary'])
        ->name('branches.library.media.upload');

    Route::get('/branches/{branch}/library/media', [BranchMediaController::class, 'listLibrary'])
        ->name('branches.library.media.index');

    // Adjuntar desde la library de la misma branch a un modelo (producto/marca/categoría)
    Route::post('/branches/{branch}/{type}/{id}/media/attach-from-library', [BranchMediaController::class, 'attachFromLibrary'])
        ->whereIn('type', ['products','brands','categories'])
        ->whereNumber('id')
        ->name('branches.media.attachFromLibrary');

    // Listar media de un modelo en una branch (opcional ?collection=gallery)
    Route::get('/branches/{branch}/{type}/{id}/media', [BranchMediaController::class, 'listFor'])
        ->whereIn('type', ['products','brands','categories'])
        ->whereNumber('id')
        ->name('branches.media.index');

    // Borrar un media por id dentro de la branch
    Route::delete('/branches/{branch}/media/{id}', [BranchMediaController::class, 'destroy'])
        ->whereNumber('id')
        ->name('branches.media.destroy');
});
