<?php

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "\n=== DIAGNÓSTICO DE ROLES ===\n";

try {
    // 1. Verificar roles disponibles
    echo "=== ROLES DISPONIBLES ===\n";
    $roles = Role::where('guard_name', 'api')->get();
    foreach ($roles as $role) {
        echo "ID: {$role->id} | Nombre: {$role->name}\n";
    }
    
    // 2. Verificar usuario objetivo
    $user = User::find(2);
    echo "\n=== USUARIO OBJETIVO ===\n";
    echo "ID: {$user->id} | Nombre: {$user->first_name} {$user->last_name}\n";
    echo "Roles actuales: " . $user->roles->pluck('name')->join(', ') . "\n";
    
    // 3. Obtener super admin y generar token
    $superAdmin = User::whereHas('roles', function($q) {
        $q->where('name', 'super-admin');
    })->first();
    
    $token = auth('api')->login($superAdmin);
    echo "\n=== TOKEN GENERADO ===\n";
    echo substr($token, 0, 50) . "...\n";
    
    // 4. Crear datos de prueba para roles
    $validRoles = $roles->take(2)->pluck('name')->toArray();
    $validRolesData = json_encode(['roles' => $validRoles]);
    
    // Datos inválidos (IDs)
    $invalidRolesData = json_encode(['roles' => [$roles->first()->id, $roles->skip(1)->first()->id]]);
    
    echo "\n=== COMANDOS DE PRUEBA PARA ROLES ===\n";
    echo "-- Datos válidos (nombres de roles) --\n";
    echo "Datos: {$validRolesData}\n\n";
    
    echo "-- Datos inválidos (IDs en lugar de nombres) --\n";
    echo "Datos: {$invalidRolesData}\n\n";
    
    // PowerShell commands
    echo "=== COMANDOS POWERSHELL ===\n";
    echo "\$headers = @{ 'Authorization' = 'Bearer {$token}'; 'Content-Type' = 'application/json' }\n\n";
    echo "# Probar con nombres válidos\n";
    echo "\$body = '{$validRolesData}'; Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/admin/users/2/roles' -Method POST -Headers \$headers -Body \$body\n\n";
    echo "# Probar con IDs (lo que envía el frontend)\n";
    echo "\$body = '{$invalidRolesData}'; Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/admin/users/2/roles' -Method POST -Headers \$headers -Body \$body\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
