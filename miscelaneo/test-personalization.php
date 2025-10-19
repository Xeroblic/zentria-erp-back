<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once __DIR__ . '/vendor/autoload.php';

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

echo "=== PRUEBA DE PERSONALIZACIÓN CON EMPRESA ===\n";

try {
    // Buscar usuario para hacer prueba
    $user = \App\Models\User::where('email', 'rbarrientos@tikinet.cl')->first();
    
    if (!$user) {
        echo "❌ No se encontró usuario\n";
        exit;
    }
    
    echo "👤 Usuario: {$user->email}\n";
    echo "🏢 Empresas del usuario: " . $user->companies->count() . "\n";
    
    // Verificar personalización actual
    $personalization = $user->personalization;
    
    if ($personalization) {
        echo "✅ Personalización existente:\n";
        echo "  - ID: {$personalization->id}\n";
        echo "  - Tema: {$personalization->tema}\n";
        echo "  - Font Size: {$personalization->font_size}\n";
        echo "  - Company ID: " . ($personalization->company_id ?? 'null') . "\n";
        
        // Asignar empresa primaria si no tiene
        if (!$personalization->company_id) {
            $primaryCompany = $user->companies()->wherePivot('is_primary', true)->first();
            if ($primaryCompany) {
                $personalization->company_id = $primaryCompany->id;
                $personalization->save();
                echo "  ✅ Empresa primaria asignada: {$primaryCompany->company_name}\n";
            }
        }
    }
    
    // Simular la respuesta del controller actualizado
    echo "\n📡 Simulando respuesta del endpoint mejorado:\n";
    
    $companies = $user->companies()->with(['subsidiaries.branches'])->get();
    $currentCompany = $companies->firstWhere('id', $personalization->company_id) 
                     ?? $companies->firstWhere('pivot.is_primary', true) 
                     ?? $companies->first();
    
    if ($currentCompany) {
        echo "🏢 Empresa actual: {$currentCompany->company_name}\n";
        echo "📊 Subsidiarias ({$currentCompany->subsidiaries->count()}):\n";
        
        foreach ($currentCompany->subsidiaries as $subsidiary) {
            echo "  - {$subsidiary->subsidiary_name} ({$subsidiary->branches->count()} sucursales)\n";
            foreach ($subsidiary->branches as $branch) {
                echo "    * {$branch->branch_name}\n";
            }
        }
    }
    
    echo "\n🎯 Nueva estructura de respuesta:\n";
    echo "{\n";
    echo "  \"personalization\": { datos de personalización },\n";
    echo "  \"companies\": [ lista de empresas del usuario ],\n";
    echo "  \"current_company\": {\n";
    echo "    \"id\": {$currentCompany->id},\n";
    echo "    \"company_name\": \"{$currentCompany->company_name}\",\n";
    echo "    \"subsidiaries\": [ lista de subsidiarias con sucursales ]\n";
    echo "  }\n";
    echo "}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN PRUEBA ===\n";
