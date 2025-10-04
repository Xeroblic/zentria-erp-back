<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== INVESTIGACIÓN DE FILTRACIÓN DE DATOS ===\n\n";

// Obtener el usuario problemático
$ceoUser = App\Models\User::where('email', 'ceo@multiempresa.cl')->first();

if (!$ceoUser) {
    echo "❌ No se encontró el usuario ceo@multiempresa.cl\n";
    exit;
}

echo "🔍 Usuario problemático encontrado:\n";
echo "   Email: {$ceoUser->email}\n";
echo "   Nombre: {$ceoUser->first_name} {$ceoUser->last_name}\n";
echo "   ID: {$ceoUser->id}\n\n";

echo "🏢 Empresas asociadas:\n";
foreach ($ceoUser->companies as $company) {
    echo "   - {$company->company_name} (ID: {$company->id})\n";
}

echo "\n📋 Roles del usuario:\n";
$roles = $ceoUser->roles;
foreach ($roles as $role) {
    echo "   - {$role->name}\n";
}

echo "\n🏬 Sucursales asociadas:\n";
$branches = $ceoUser->branches;
foreach ($branches as $branch) {
    echo "   - {$branch->branch_name} (Empresa: {$branch->subsidiary->company->company_name})\n";
}

echo "\n💡 OPCIONES DE SOLUCIÓN:\n";
echo "1. Eliminar este usuario multi-empresa\n";
echo "2. Asignarlo solo a una empresa\n";
echo "3. Implementar filtros más estrictos en los endpoints\n";
echo "4. Revisar la lógica de autorización\n\n";

echo "¿Este usuario debería tener acceso a múltiples empresas?\n";
echo "Si NO, es un problema de seguridad que debe corregirse.\n";
