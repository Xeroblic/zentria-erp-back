<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserAccessController;

Route::middleware(['auth:api'])->group(function () {
    // Gestionar acceso por subsidiaries (subsidiary-member)
    Route::post('users/{user}/access/subsidiaries', [UserAccessController::class, 'syncSubsidiaries']);

    // Gestionar acceso por branches (pivot branch_user)
    Route::post('users/{user}/access/branches', [UserAccessController::class, 'syncBranches']);

    // Gestionar acceso por companies (pivot company_user + scope_roles company-member)
    Route::post('users/{user}/access/companies', [UserAccessController::class, 'syncCompanies']);
});
