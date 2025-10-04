<?php

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

echo "\n=== DIAGNÓSTICO COMPLETO ===\n";

try {
    // 1. Verificar permisos disponibles
    echo "=== PERMISOS DISPONIBLES ===\n";
    $permissions = Permission::where('guard_name', 'api')->get();
    foreach ($permissions as $perm) {
        echo "ID: {$perm->id} | Nombre: {$perm->name}\n";
    }
    
    // 2. Verificar usuario objetivo
    $user = User::find(2);
    echo "\n=== USUARIO OBJETIVO ===\n";
    echo "ID: {$user->id} | Nombre: {$user->first_name} {$user->last_name}\n";
    echo "Permisos actuales: " . $user->getAllPermissions()->pluck('name')->join(', ') . "\n";
    
    // 3. Obtener super admin y generar token
    $superAdmin = User::whereHas('roles', function($q) {
        $q->where('name', 'super-admin');
    })->first();
    
    $token = auth('api')->login($superAdmin);
    echo "\n=== TOKEN GENERADO ===\n";
    echo substr($token, 0, 50) . "...\n";
    
    // 4. Crear script curl con datos correctos
    $validPermissions = $permissions->take(3)->pluck('name')->toArray();
    $curlData = json_encode(['permissions' => $validPermissions]);
    
    echo "\n=== COMANDOS DE PRUEBA ===\n";
    echo "-- Datos válidos (nombres de permisos) --\n";
    echo "curl -X POST 'http://127.0.0.1:8000/api/admin/users/2/permissions' \\\n";
    echo "  -H 'Authorization: Bearer {$token}' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{$curlData}'\n\n";
    
    // 5. Simular el error del frontend (IDs en lugar de nombres)
    $invalidData = json_encode(['permissions' => [$permissions->first()->id, $permissions->skip(1)->first()->id]]);
    echo "-- Datos inválidos (IDs en lugar de nombres) --\n";
    echo "curl -X POST 'http://127.0.0.1:8000/api/admin/users/2/permissions' \\\n";
    echo "  -H 'Authorization: Bearer {$token}' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{$invalidData}'\n\n";
    
    // 6. Crear archivo de script de prueba
    $bashScript = "#!/bin/bash\n\n";
    $bashScript .= "echo 'Probando con datos válidos (nombres de permisos)'\n";
    $bashScript .= "curl -X POST 'http://127.0.0.1:8000/api/admin/users/2/permissions' \\\n";
    $bashScript .= "  -H 'Authorization: Bearer {$token}' \\\n";
    $bashScript .= "  -H 'Content-Type: application/json' \\\n";
    $bashScript .= "  -d '{$curlData}'\n\n";
    
    $bashScript .= "echo '\n\nProbando con datos inválidos (IDs en lugar de nombres)'\n";
    $bashScript .= "curl -X POST 'http://127.0.0.1:8000/api/admin/users/2/permissions' \\\n";
    $bashScript .= "  -H 'Authorization: Bearer {$token}' \\\n";
    $bashScript .= "  -H 'Content-Type: application/json' \\\n";
    $bashScript .= "  -d '{$invalidData}'\n";
    
    file_put_contents('test-permissions-curl.sh', $bashScript);
    echo "✅ Script de prueba creado: test-permissions-curl.sh\n";
    
    // 7. PowerShell version
    $psScript = "`$headers = @{\n";
    $psScript .= "    'Authorization' = 'Bearer {$token}'\n";
    $psScript .= "    'Content-Type' = 'application/json'\n";
    $psScript .= "}\n\n";
    $psScript .= "Write-Host 'Probando con datos válidos'\n";
    $psScript .= "`$body1 = '{$curlData}'\n";
    $psScript .= "Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/admin/users/2/permissions' -Method POST -Headers `$headers -Body `$body1\n\n";
    $psScript .= "Write-Host 'Probando con datos inválidos'\n";
    $psScript .= "`$body2 = '{$invalidData}'\n";
    $psScript .= "Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/admin/users/2/permissions' -Method POST -Headers `$headers -Body `$body2\n";
    
    file_put_contents('test-permissions.ps1', $psScript);
    echo "✅ Script PowerShell creado: test-permissions.ps1\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
