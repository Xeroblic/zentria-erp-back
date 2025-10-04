<?php

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "\n=== PRUEBA DE REMOCIÓN ESPECÍFICA DE ROLES ===\n";

try {
    // Obtener super admin y generar token
    $superAdmin = User::whereHas('roles', function($q) {
        $q->where('name', 'super-admin');
    })->first();
    
    $token = auth('api')->login($superAdmin);
    echo "✅ Token generado: " . substr($token, 0, 30) . "...\n";
    
    // Usuario objetivo
    $user = User::find(2);
    echo "✅ Usuario objetivo: {$user->first_name} {$user->last_name}\n";
    echo "📋 Roles actuales: " . $user->roles->pluck('name')->join(', ') . "\n";
    
    // Roles disponibles
    $roles = Role::where('guard_name', 'api')->get();
    echo "\n=== ROLES DISPONIBLES ===\n";
    foreach ($roles as $role) {
        echo "ID: {$role->id} | Nombre: {$role->name}\n";
    }
    
    // Asignar algunos roles primero para poder removerlos
    $rolesToAssign = ['manager', 'technician'];
    $user->assignRole($rolesToAssign);
    echo "\n✅ Roles asignados para prueba: " . implode(', ', $rolesToAssign) . "\n";
    echo "📋 Roles del usuario ahora: " . $user->fresh()->roles->pluck('name')->join(', ') . "\n";
    
    // Obtener ID del rol 'manager' para removerlo
    $managerRole = Role::where('name', 'manager')->first();
    
    echo "\n=== COMANDOS DE PRUEBA ===\n";
    echo "# Remover rol 'manager' (ID: {$managerRole->id}) del usuario 2\n";
    echo "DELETE /api/admin/users/2/roles/{$managerRole->id}\n\n";
    
    echo "=== COMANDO POWERSHELL ===\n";
    echo "\$headers = @{ 'Authorization' = 'Bearer {$token}'; 'Content-Type' = 'application/json' }\n";
    echo "Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/admin/users/2/roles/{$managerRole->id}' -Method DELETE -Headers \$headers\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
