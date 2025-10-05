<?php
use App\Http\Controllers\BranchProductsController;

Route::middleware(['auth:api'])->group(function(){
  Route::get('branches/{branch}/products', [BranchProductsController::class,'index'])->middleware('can:view-product');
  Route::post('branches/{branch}/products', [BranchProductsController::class,'store'])->middleware('can:create-product');
  Route::get('branches/{branch}/products/{product}', [BranchProductsController::class,'show'])->middleware('can:view-product');
  Route::match(['put','patch'], 'branches/{branch}/products/{product}', [BranchProductsController::class,'update'])->middleware('can:edit-product');
  Route::delete('branches/{branch}/products/{product}', [BranchProductsController::class,'destroy'])->middleware('can:delete-product');
});
