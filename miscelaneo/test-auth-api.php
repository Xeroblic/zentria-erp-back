<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRUEBA DE API AUTH ===\n";

try {
    // Encontrar un usuario para hacer login
    $user = \App\Models\User::where('email', 'rbarrientos@tikinet.cl')->first();
    
    if (!$user) {
        echo "❌ No se encontró el usuario super-admin\n";
        exit;
    }
    
    echo "👤 Usuario encontrado: {$user->email}\n";
    echo "🏢 Empresas del usuario: " . $user->companies->count() . "\n";
    
    // Crear token manualmente para prueba
    $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
    echo "🔑 Token generado: " . substr($token, 0, 50) . "...\n";
    
    // Simular request a available-companies
    echo "\n📡 Simulando llamada a getAvailableCompanies...\n";
    
    // Cambiar el usuario autenticado
    \Tymon\JWTAuth\Facades\JWTAuth::setToken($token);
    $authenticatedUser = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();
    
    echo "✅ Usuario autenticado: {$authenticatedUser->email}\n";
    
    // Obtener companies disponibles
    $companies = $authenticatedUser->companies()->with(['subsidiaries.branches'])->get();
    
    echo "🏢 Empresas disponibles: " . $companies->count() . "\n";
    
    foreach ($companies as $company) {
        echo "  - {$company->company_name} (ID: {$company->id})\n";
        echo "    Subsidiarias: " . $company->subsidiaries->count() . "\n";
        echo "    Sucursales: " . $company->subsidiaries->sum(fn($sub) => $sub->branches->count()) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN PRUEBA ===\n";
