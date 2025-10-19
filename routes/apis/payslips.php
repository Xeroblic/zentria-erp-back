<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayslipController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('payslips', [PayslipController::class, 'store'])
        ->middleware('can:create-payslips');

    Route::get('payslips', [PayslipController::class, 'index'])
        ->middleware('can:view-payslips');

    Route::get('payslips/{id}', [PayslipController::class, 'show'])
        ->middleware('can:view-payslips');

    Route::put('payslips/{id}', [PayslipController::class, 'update'])
        ->middleware('can:edit-payslips');

    Route::delete('payslips/{id}', [PayslipController::class, 'destroy'])
        ->middleware('can:delete-payslips');
});
