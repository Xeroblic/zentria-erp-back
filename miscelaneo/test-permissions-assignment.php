<?php

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

echo "\n=== PRUEBA DE ASIGNACIÓN DE PERMISOS ===\n";

try {
    // Buscar un usuario de prueba (usuario ID 2)
    $user = User::find(2);
    if (!$user) {
        echo "❌ Usuario ID 2 no encontrado\n";
        exit(1);
    }
    
    echo "✅ Usuario encontrado: {$user->first_name} {$user->last_name} ({$user->email})\n";
    
    // Verificar permisos disponibles
    $permissions = Permission::where('guard_name', 'api')->limit(3)->get();
    echo "✅ Permisos disponibles: " . $permissions->pluck('name')->join(', ') . "\n";
    
    // Simular request data
    $requestData = [
        'permissions' => $permissions->pluck('name')->toArray()
    ];
    
    echo "📤 Datos que se enviarían: " . json_encode($requestData, JSON_PRETTY_PRINT) . "\n";
    
    // Verificar permisos actuales del usuario
    $currentPermissions = $user->getAllPermissions();
    echo "📋 Permisos actuales del usuario: " . $currentPermissions->pluck('name')->join(', ') . "\n";
    
    // Probar asignación directa
    echo "\n🔧 Probando asignación directa...\n";
    $user->givePermissionTo($requestData['permissions']);
    
    $newPermissions = $user->getAllPermissions();
    echo "✅ Permisos después de asignación: " . $newPermissions->pluck('name')->join(', ') . "\n";
    
    echo "\n=== PRUEBA DE CURL ===\n";
    
    // Obtener token de un super admin para hacer la prueba
    $superAdmin = User::whereHas('roles', function($q) {
        $q->where('name', 'super-admin');
    })->first();
    
    if ($superAdmin) {
        $token = auth('api')->login($superAdmin);
        echo "🔑 Token generado para super admin: " . substr($token, 0, 20) . "...\n";
        
        // Datos para curl
        $curlData = [
            'permissions' => ['view-user', 'edit-user']
        ];
        
        echo "📤 Comando curl de prueba:\n";
        echo "curl -X POST http://127.0.0.1:8000/api/admin/users/2/permissions \\\n";
        echo "  -H 'Authorization: Bearer {$token}' \\\n";
        echo "  -H 'Content-Type: application/json' \\\n";
        echo "  -d '" . json_encode($curlData) . "'\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
