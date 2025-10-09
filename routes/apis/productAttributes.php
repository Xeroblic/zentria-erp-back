<?php
use App\Http\Controllers\ProductAttributesController;

Route::prefix('branches/{branch}/products/{product}')
    ->middleware(['auth:api']) ->group(function () {
    Route::get('attributes',   [ProductAttributesController::class, 'show'])->name('products.attributes.show');
    Route::patch('attributes', [ProductAttributesController::class, 'patch'])->name('products.attributes.patch')->middleware('can:edit-product');
    // Eliminar un path (o varios). Usamos query param ?path=specs.ram o ?paths[]=a&paths[]=b
    Route::delete('attributes', [ProductAttributesController::class, 'destroy'])->name('products.attributes.destroy')->middleware('can:edit-product');
});