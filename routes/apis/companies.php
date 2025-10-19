<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;

Route::middleware(['auth:api'])->group(function () {
    
    /// Funciones dentro de compañia tipo CRUD
    
    Route::post('companies', [CompanyController::class, 'store'])
        ->middleware('can:create-company');

    Route::get('companies', [CompanyController::class, 'index'])
        ->middleware('can:view-company');

    Route::get('companies/{id}', [CompanyController::class, 'show'])
        ->middleware('can:view-company');

    Route::put('companies/{id}', [CompanyController::class, 'update'])
        ->middleware('can:edit-company');
    
    Route::patch('companies/{id}', [CompanyController::class, 'update'])
        ->middleware('can:edit-company');

    Route::delete('companies/{id}', [CompanyController::class, 'destroy'])
        ->middleware('can:delete-company');


    ///  ruta para usuarios dentro de una empresa sub empresa y sucursales
    Route::get('companies/{id}/users', [CompanyController::class, 'getUsers'])
        ->middleware('can:view-company');


    /// Funciones relacionadas a las subempresas de una empresa
    Route::get('companies/{id}/subsidiaries', [CompanyController::class, 'subsidiaries'])
        ->middleware('can:view-subsidiary')
        ->name('companies.subsidiaries');

    // Actualizar solo la comuna de la empresa
    Route::patch('companies/{id}/commune', [CompanyController::class, 'updateCommune'])
        ->middleware('can:edit-company')
        ->name('companies.updateCommune');

});
