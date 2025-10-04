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

echo "=== PERSONALIZACIÓN CON SUBSIDIARIAS ===\n";

try {
    $user = \App\Models\User::where('email', 'rbarrientos@tikinet.cl')->first();
    
    if (!$user) {
        echo "❌ Usuario no encontrado\n";
        exit;
    }
    
    echo "👤 Usuario: {$user->email}\n";
    
    // Simular la nueva respuesta del endpoint personalización
    $companies = $user->companies()->with(['subsidiaries.branches'])->get();
    $userPersonalization = $user->personalization;
    
    if (!$userPersonalization) {
        $primaryCompany = $user->companies()->wherePivot('is_primary', true)->first();
        $userPersonalization = $user->personalization()->create([
            'tema' => 1,
            'font_size' => 14,
            'company_id' => $primaryCompany ? $primaryCompany->id : null,
            'sucursal_principal' => null
        ]);
        echo "✅ Personalización creada\n";
    }
    
    $currentCompany = $companies->firstWhere('id', $userPersonalization->company_id) 
                     ?? $companies->firstWhere('pivot.is_primary', true) 
                     ?? $companies->first();
    
    $subsidiaries = $currentCompany ? $currentCompany->subsidiaries->map(function ($subsidiary) {
        return [
            'id' => $subsidiary->id,
            'subsidiary_name' => $subsidiary->subsidiary_name,
            'branches_count' => $subsidiary->branches->count(),
            'branches' => $subsidiary->branches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'branch_name' => $branch->branch_name,
                ];
            })->toArray()
        ];
    })->toArray() : [];
    
    echo "\n🎯 NUEVA RESPUESTA DE /api/user/personalization:\n";
    echo "==================================================\n";
    
    $response = [
        'personalization' => [
            'id' => $userPersonalization->id,
            'user_id' => $userPersonalization->user_id,
            'tema' => $userPersonalization->tema,
            'font_size' => $userPersonalization->font_size,
            'sucursal_principal' => $userPersonalization->sucursal_principal,
            'company_id' => $userPersonalization->company_id,
            'created_at' => $userPersonalization->created_at,
            'updated_at' => $userPersonalization->updated_at,
        ],
        'companies' => $companies->map(function ($company) {
            return [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'is_primary' => $company->pivot->is_primary,
                'position_in_company' => $company->pivot->position_in_company,
                'subsidiaries_count' => $company->subsidiaries->count(),
                'branches_count' => $company->subsidiaries->sum(fn($sub) => $sub->branches->count()),
            ];
        })->toArray(),
        'current_company' => $currentCompany ? [
            'id' => $currentCompany->id,
            'company_name' => $currentCompany->company_name,
            'subsidiaries' => $subsidiaries
        ] : null
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";
    
    echo "📝 EXPLICACIÓN DEL CAMBIO:\n";
    echo "=========================\n";
    echo "✅ Personalización ahora incluye información de empresa (company_id)\n";
    echo "✅ Lista de empresas del usuario disponible\n";
    echo "✅ Información detallada de empresa actual con subsidiarias\n";
    echo "✅ Para cambiar empresa: PUT /api/user/personalization con company_id\n";
    echo "✅ Para cambiar empresa: POST /api/user/switch-company con company_id\n";
    echo "✅ El 'Seleccionar Empresa' ahora muestra subsidiarias en lugar de empresas\n";
    
    echo "\n🔄 ENDPOINTS NUEVOS:\n";
    echo "===================\n";
    echo "GET  /api/user/personalization → Incluye empresas y subsidiarias\n";
    echo "PUT  /api/user/personalization → Actualiza personalización y empresa\n";
    echo "POST /api/user/switch-company   → Cambia empresa activa\n";
    
    echo "\n📱 USO EN FRONTEND:\n";
    echo "==================\n";
    echo "1. Llamar GET /api/user/personalization al iniciar\n";
    echo "2. Mostrar current_company.subsidiaries en 'Seleccionar Empresa'\n";
    echo "3. Para cambiar empresa usar PUT con company_id\n";
    echo "4. Ya no usar /api/available-companies (deprecated)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN ===\n";
