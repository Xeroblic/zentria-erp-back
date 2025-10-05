<?php
use App\Http\Controllers\BranchBrandsController;

Route::middleware(['auth:api'])->scopeBindings()->group(function(){
    // Brands (scoped a Branch)
    Route::get('branches/{branch}/brands', [BranchBrandsController::class,'index'])->middleware('can:view-brand');
    Route::post('branches/{branch}/brands', [BranchBrandsController::class,'store'])->middleware('can:create-brand');
    Route::get('branches/{branch}/brands/{brand}', [BranchBrandsController::class,'show'])->middleware('can:view-brand');
    Route::match(['put','patch'], 'branches/{branch}/brands/{brand}', [BranchBrandsController::class,'update'])->middleware('can:edit-brand');
    Route::delete('branches/{branch}/brands/{brand}', [BranchBrandsController::class,'destroy'])->middleware('can:delete-brand');
});