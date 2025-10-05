<?php
use App\Http\Controllers\ProductCategoryController;

Route::middleware(['auth:api'])->scopeBindings()->group(function () {
  // listar
  Route::get('branches/{branch}/products/{product}/categories', [ProductCategoryController::class,'index'])
    ->middleware('can:view-product');

  // sync en bloque (no usa {category})
  Route::patch('branches/{branch}/products/{product}/categories', [ProductCategoryController::class,'sync'])
    ->middleware('can:edit-product');

  // 👉 para ATTACH y RESTORE desactiva el scoping:
  Route::post('branches/{branch}/products/{product}/categories/{category}', [ProductCategoryController::class,'attach'])
    ->withoutScopedBindings()->middleware('can:edit-product');

  Route::patch('branches/{branch}/products/{product}/categories/{category}/restore', [ProductCategoryController::class,'restore'])
    ->withoutScopedBindings()->middleware('can:edit-product');

  // Para DETACH sí conviene exigir que ya esté asociada (deja scoped):
  Route::delete('branches/{branch}/products/{product}/categories/{category}', [ProductCategoryController::class,'detach'])
    ->middleware('can:edit-product');
});