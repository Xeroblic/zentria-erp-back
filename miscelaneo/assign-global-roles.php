<?php

require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\ScopeRole;
use Spatie\Permission\Models\Role;

echo "=== ASIGNACIÓN AUTOMÁTICA DE ROLES GLOBALES ===\n\n";

// Mapeo de roles contextuales a roles globales
$roleMapping = [
    'company-admin' => 'company-admin',
    'subsidiary-admin' => 'subsidiary-admin', 
    'branch-admin' => 'branch-admin',
    'manager' => 'manager'
];

$users = User::with(['roles', 'scopeRoles.role'])->get();

foreach ($users as $user) {
    echo "👤 Procesando: {$user->first_name} {$user->last_name} ({$user->email})\n";
    
    // Obtener roles contextuales únicos
    $contextualRoles = $user->scopeRoles->pluck('role.name')->unique();
    $globalRoles = $user->roles->pluck('name');
    
    echo "   Roles contextuales: " . ($contextualRoles->isEmpty() ? 'Ninguno' : $contextualRoles->implode(', ')) . "\n";
    echo "   Roles globales: " . ($globalRoles->isEmpty() ? 'Ninguno' : $globalRoles->implode(', ')) . "\n";
    
    $rolesToAssign = [];
    
    foreach ($contextualRoles as $contextualRole) {
        if (isset($roleMapping[$contextualRole])) {
            $globalRole = $roleMapping[$contextualRole];
            
            // Solo asignar si no lo tiene ya
            if (!$globalRoles->contains($globalRole)) {
                $rolesToAssign[] = $globalRole;
            }
        }
    }
    
    if (!empty($rolesToAssign)) {
        echo "   ✅ Asignando roles globales: " . implode(', ', $rolesToAssign) . "\n";
        
        foreach ($rolesToAssign as $roleToAssign) {
            try {
                $user->assignRole($roleToAssign);
                echo "      ✓ Asignado: $roleToAssign\n";
            } catch (Exception $e) {
                echo "      ❌ Error asignando $roleToAssign: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "   ℹ️  No necesita roles adicionales\n";
    }
    
    echo "   ──────────────────────────────────\n";
}

echo "\n🎉 PROCESO COMPLETADO\n";
echo "Ahora todos los usuarios con roles contextuales tienen sus roles globales correspondientes.\n";
echo "Esto asegura que tengan los permisos correctos asignados.\n";
?>
