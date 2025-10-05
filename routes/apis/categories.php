<?php
use App\Http\Controllers\CategoriesController;

Route::middleware(['auth:api'])->group(function(){
    Route::get('categories', [CategoriesController::class,'index'])->middleware('can:view-category');
    Route::get('categories/tree', [CategoriesController::class,'tree'])->middleware('can:view-category');
    Route::post('categories', [CategoriesController::class,'store'])->middleware('can:create-category');
    Route::get('categories/{category}', [CategoriesController::class,'show'])->middleware('can:view-category');
    Route::match(['put','patch'], 'categories/{category}', [CategoriesController::class,'update'])->middleware('can:edit-category');
    Route::delete('categories/{category}', [CategoriesController::class,'destroy'])->middleware('can:delete-category');
});