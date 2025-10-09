<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserPersonalizationController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Api\FalabellaController;

// Ruta temporal de prueba
Route::get('test-auth', [TestController::class, 'testAuth']);

// Rutas de autenticación directas (para compatibilidad con frontend)
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::middleware(['auth:api'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'perfil']);
    Route::get('perfil', [AuthController::class, 'perfil']); // Alias para compatibilidad
    Route::get('available-companies', [AuthController::class, 'getAvailableCompanies']);
    
    // Rutas de personalización directas (para compatibilidad con frontend)
    Route::get('user/personalization', [UserPersonalizationController::class, 'show']);
    Route::put('user/personalization', [UserPersonalizationController::class, 'update']);
    Route::post('user/switch-company', [UserPersonalizationController::class, 'switchCompany']);
});

// Rutas públicas de permisos y roles (para frontend)
Route::middleware(['auth:api'])->group(function () {
    Route::get('permissions', [AdminController::class, 'getPermissions']);
    Route::get('roles', [AdminController::class, 'getRoles']);
    Route::get('users', [AdminController::class, 'getUsers']);
});

// Rutas de administración que requieren autenticación
Route::middleware(['auth:api'])->prefix('admin')->group(function () {
    // Gestión de usuarios
    Route::get('users', [AdminController::class, 'getUsers']);
    Route::get('users/{id}', [AdminController::class, 'getUser']); // Nueva ruta para usuario individual
    Route::post('users', [AdminController::class, 'createUser']);
    Route::put('users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
    Route::patch('users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
    
    // Gestión de permisos de usuarios
    Route::get('users/{id}/permissions', [AdminController::class, 'getUserPermissions']);
    Route::post('users/{id}/permissions', [AdminController::class, 'assignPermissionsToUser']);
    Route::delete('users/{id}/permissions', [AdminController::class, 'revokePermissionsFromUser']);
    Route::delete('users/{userId}/permissions/{permissionId}', [AdminController::class, 'revokeSpecificPermissionFromUser']);
    
    // Gestión de roles de usuarios
    Route::get('users/{id}/roles', [AdminController::class, 'getUserRoles']);
    Route::post('users/{id}/roles', [AdminController::class, 'assignRolesToUser']);
    Route::delete('users/{id}/roles', [AdminController::class, 'revokeRolesFromUser']);
    Route::delete('users/{userId}/roles/{roleId}', [AdminController::class, 'revokeSpecificRoleFromUser']);
    
    // Obtener todos los permisos disponibles
    Route::get('permissions', [AdminController::class, 'getPermissions']);
    
    // Obtener todos los roles disponibles
    Route::get('roles', [AdminController::class, 'getRoles']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::post('branches', [BranchController::class, 'store'])
        ->middleware('can:branch.create');

    Route::get('branches', [BranchController::class, 'index'])
        ->middleware('can:branch.view');

    Route::put('branches/{id}', [BranchController::class, 'update'])
        ->middleware('can:branch.edit');

    Route::delete('branches/{id}', [BranchController::class, 'destroy'])
        ->middleware('can:branch.delete');
    
    Route::get('branches/{id}', [BranchController::class, 'show'])
        ->middleware('can:branch.view');
});

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
    
    // Rutas dinámicas para la empresa del usuario actual (sin IDs hardcodeados)
    Route::get('my-company', [CompanyController::class, 'myCompany'])
        ->middleware('can:company.view')
        ->name('my-company.show');
        
    Route::get('my-company/subsidiaries', [CompanyController::class, 'myCompanySubsidiaries'])
        ->middleware('can:subsidiary.view')
        ->name('my-company.subsidiaries');
        
    Route::get('my-company/users', [CompanyController::class, 'myCompanyUsers'])
        ->middleware('can:user.view')
        ->name('my-company.users');
        
    Route::put('my-company', [CompanyController::class, 'updateMyCompany'])
        ->middleware('can:company.edit')
        ->name('my-company.update');

});

use App\Http\Controllers\UserController;

Route::middleware(['auth:api', 'can:user.view'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
     Route::put('/users/{id}/roles', [UserController::class, 'updateRoles'])
        ->middleware('can:user.edit-roles');
});


// Rutas de autenticación bajo el prefijo 'auth'
Route::prefix('auth')->group(function () {
    require __DIR__ . '/apis/auth.php';
});

require __DIR__ . '/apis/companies.php';
require __DIR__ . '/apis/subsidiary.php';
require __DIR__ . '/apis/branch.php';
require __DIR__ . '/apis/users.php';
require __DIR__ . '/apis/invitations.php';
require __DIR__ . '/apis/brands.php';
require __DIR__ . '/apis/productCategory.php';
require __DIR__ . '/apis/products.php';
require __DIR__ . '/apis/categories.php';
require __DIR__ . '/apis/productAttributes.php';


// ===============================================
// RUTAS DE SERVICIOS EXTERNOS
// ===============================================

// Rutas de Falabella (requieren autenticación)
Route::middleware(['auth:api'])->prefix('falabella')->group(function () {
    // Endpoint de diagnóstico (temporal para debug)
    Route::get('/_mode', function (\App\Services\Falabella\FalabellaClient $client) {
        return response()->json([
            'mode' => $client instanceof \App\Services\Falabella\FalabellaMockService ? 'mock' : 'live',
            'use_mock_config' => config('falabella.use_mock'),
            'config_dump' => [
                'base_url' => config('falabella.base_url'),
                'user_id' => config('falabella.user_id') ? 'SET' : 'NOT_SET',
                'api_key' => config('falabella.api_key') ? 'SET' : 'NOT_SET',
            ]
        ]);
    });
    
    // Consultas de productos y stock
    Route::get('/products', [FalabellaController::class, 'products']);
    Route::get('/stock', [FalabellaController::class, 'stock']);
    Route::get('/categories', [FalabellaController::class, 'categories']);
    
    // Datos de ventas y análisis
    Route::get('/sales', [FalabellaController::class, 'sales']);
    Route::get('/orders', [FalabellaController::class, 'orders']);
    Route::get('/low-stock', [FalabellaController::class, 'lowStock']);
    Route::get('/best-sellers', [FalabellaController::class, 'bestSellers']);
    Route::get('/inventory-summary', [FalabellaController::class, 'inventorySummary']);
    
    // Actualizaciones (requieren permisos especiales)
    Route::middleware('can:falabella.update')->group(function () {
        Route::put('/products/{sku}/price', [FalabellaController::class, 'updatePrice']);
        Route::put('/products/{sku}/stock', [FalabellaController::class, 'updateStock']);
    });
});


// TODO: Aquí puedes agregar otros servicios como MercadoLibre, Amazon, etc.
// Route::middleware(['auth:api'])->prefix('mercadolibre')->group(function () {
//     // Rutas de MercadoLibre
// });

