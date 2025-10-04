<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;

Route::middleware(['auth:api'])->group(function () {
    
    /// Funciones dentro de compañia tipo CRUD
    
    Route::post('companies', [CompanyController::class, 'store'])
        ->middleware('can:company.create');

    Route::get('companies', [CompanyController::class, 'index'])
        ->middleware('can:company.view');

    Route::get('companies/{id}', [CompanyController::class, 'show'])
        ->middleware('can:company.view');

    Route::put('companies/{id}', [CompanyController::class, 'update'])
        ->middleware('can:company.edit');
    
    Route::patch('companies/{id}', [CompanyController::class, 'update'])
        ->middleware('can:company.edit');

    Route::delete('companies/{id}', [CompanyController::class, 'destroy'])
        ->middleware('can:company.delete');


    ///  ruta para usuarios dentro de una empresa sub empresa y sucursales
    Route::get('companies/{id}/users', [CompanyController::class, 'getUsers'])
        ->middleware('can:company.view');


    /// Funciones relacionadas a las subempresas de una empresa
    Route::get('companies/{id}/subsidiaries', [CompanyController::class, 'subsidiaries'])
        ->middleware('can:subsidiary.view')
        ->name('companies.subsidiaries');

});
