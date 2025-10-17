<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\CommuneController;

// Rutas públicas solo-GET para datos geográficos
Route::get('regions', [RegionController::class, 'index']);
Route::get('regions/{id}', [RegionController::class, 'show']);

Route::get('provinces', [ProvinceController::class, 'index']);
Route::get('provinces/{id}', [ProvinceController::class, 'show']);

Route::get('communes', [CommuneController::class, 'index']);
Route::get('communes/{id}', [CommuneController::class, 'show']);

