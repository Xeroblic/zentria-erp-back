<?php

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;
use App\Services\ContextualRoleService;

echo "\n=== SIMULACIÓN DE REQUEST FRONTEND ===\n";

try {
    // Crear instancia del controlador
    $contextualRoleService = new ContextualRoleService();
    $controller = new AdminController($contextualRoleService);
    
    // Autenticar como super admin
    $superAdmin = User::whereHas('roles', function($q) {
        $q->where('name', 'super-admin');
    })->first();
    
    if (!$superAdmin) {
        echo "❌ Super admin no encontrado\n";
        exit(1);
    }
    
    $token = auth('api')->login($superAdmin);
    echo "✅ Super admin autenticado: {$superAdmin->email}\n";
    
    // Usuario objetivo
    $targetUser = User::find(2);
    echo "🎯 Usuario objetivo: {$targetUser->first_name} {$targetUser->last_name}\n";
    
    // Simular diferentes tipos de requests que puede enviar el frontend
    $testCases = [
        [
            'name' => 'Request válido con array de permisos',
            'data' => ['permissions' => ['view-user', 'edit-user']]
        ],
        [
            'name' => 'Request vacío (simula el error del frontend)',
            'data' => []
        ],
        [
            'name' => 'Request con permissions null',
            'data' => ['permissions' => null]
        ],
        [
            'name' => 'Request con permissions como string',
            'data' => ['permissions' => 'view-user']
        ],
        [
            'name' => 'Request con estructura diferente',
            'data' => ['permission_ids' => [1, 2]]
        ]
    ];
    
    foreach ($testCases as $test) {
        echo "\n--- {$test['name']} ---\n";
        echo "📤 Datos enviados: " . json_encode($test['data']) . "\n";
        
        // Crear request simulado
        $request = Request::create('/api/admin/users/2/permissions', 'POST', $test['data']);
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $request->headers->set('Content-Type', 'application/json');
        
        try {
            $response = $controller->assignPermissionsToUser($request, 2);
            $responseData = json_decode($response->getContent(), true);
            
            echo "📥 Respuesta HTTP Status: " . $response->getStatusCode() . "\n";
            echo "📥 Respuesta: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Verificar logs
    echo "\n=== VERIFICAR LOGS ===\n";
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -20); // Últimas 20 líneas
        
        foreach ($recentLines as $line) {
            if (strpos($line, 'Assign Permissions Request') !== false) {
                echo "📋 Log: " . $line . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DE LA SIMULACIÓN ===\n";
