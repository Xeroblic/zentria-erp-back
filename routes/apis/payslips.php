<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayslipController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('payslips', [PayslipController::class, 'store'])
        ->middleware('can:payslip.create');

    Route::get('payslips', [PayslipController::class, 'index'])
        ->middleware('can:payslip.view');

    Route::get('payslips/{id}', [PayslipController::class, 'show'])
        ->middleware('can:payslip.view');

    Route::put('payslips/{id}', [PayslipController::class, 'update'])
        ->middleware('can:payslip.edit');

    Route::delete('payslips/{id}', [PayslipController::class, 'destroy'])
        ->middleware('can:payslip.delete');
});
