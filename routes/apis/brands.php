<?php
use App\Http\Controllers\BranchBrandsController;

Route::middleware(['auth:api'])->scopeBindings()->group(function(){
    // Brands (scoped a Branch)
    Route::get('branches/{branch}/brands', [BranchBrandsController::class,'index']);
    Route::post('branches/{branch}/brands', [BranchBrandsController::class,'store'])->middleware('can:create-brand');
    Route::get('branches/{branch}/brands/{brand}', [BranchBrandsController::class,'show']);
    Route::match(['put','patch'], 'branches/{branch}/brands/{brand}', [BranchBrandsController::class,'update'])->middleware('can:edit-brand');
    Route::delete('branches/{branch}/brands/{brand}', [BranchBrandsController::class,'destroy'])->middleware('can:delete-brand');
    Route::patch('branches/{branch}/brands/{brand}/toggle-status',     [BranchBrandsController::class, 'toggleStatus'])->middleware('can:edit-brand');
});
