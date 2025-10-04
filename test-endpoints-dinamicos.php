<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;

echo "=== PRUEBA DE ENDPOINTS DINÁMICOS (SIN HARDCODING) ===" . PHP_EOL;

// Probar con usuario de Digital Innovate
$user = App\Models\User::where('email', 'superadmin@digitalinnovate.cl')->first();
Auth::setUser($user);

echo "Usuario: " . $user->email . PHP_EOL;
echo "Empresa del usuario: " . ($user->companies->first()->company_name ?? 'NINGUNA') . PHP_EOL;
echo PHP_EOL;

$controller = new App\Http\Controllers\CompanyController();

// 1. Probar GET /my-company
echo "1️⃣ Probando GET /my-company..." . PHP_EOL;
try {
    $response = $controller->myCompany();
    $content = $response->getData(true);
    echo "✅ Funciona - Empresa: " . ($content['company_name'] ?? 'N/A') . " (ID: " . ($content['id'] ?? 'N/A') . ")" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

// 2. Probar GET /my-company/subsidiaries
echo PHP_EOL . "2️⃣ Probando GET /my-company/subsidiaries..." . PHP_EOL;
try {
    $response = $controller->myCompanySubsidiaries();
    $content = $response->getData(true);
    echo "✅ Funciona - Subsidiarias: " . count($content['subempresas']) . PHP_EOL;
    echo "   Empresa: " . $content['empresa']['nombre'] . " (ID: " . $content['empresa']['id'] . ")" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

// 3. Probar GET /my-company/users
echo PHP_EOL . "3️⃣ Probando GET /my-company/users..." . PHP_EOL;
try {
    $response = $controller->myCompanyUsers();
    $content = $response->getData(true);
    echo "✅ Funciona - Usuarios: " . count($content['usuarios']) . PHP_EOL;
    echo "   Empresa: " . $content['empresa']['nombre'] . " (ID: " . $content['empresa']['id'] . ")" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== RESUMEN PARA EL FRONTEND ===" . PHP_EOL;
echo "🚀 NUEVOS ENDPOINTS DINÁMICOS:" . PHP_EOL;
echo "   GET /api/my-company                 -> Obtiene tu empresa" . PHP_EOL;
echo "   PUT /api/my-company                 -> Actualiza tu empresa" . PHP_EOL;
echo "   GET /api/my-company/subsidiaries    -> Obtiene subsidiarias de tu empresa" . PHP_EOL;
echo "   GET /api/my-company/users           -> Obtiene usuarios de tu empresa" . PHP_EOL;
echo PHP_EOL;
echo "❌ ENDPOINTS DEPRECADOS (NO USAR):" . PHP_EOL;
echo "   GET /api/companies/1/subsidiaries   -> Hardcodeado, causaba 403" . PHP_EOL;
echo "   PUT /api/companies/1                -> Hardcodeado" . PHP_EOL;
echo PHP_EOL;
echo "🎯 MIGRACIÓN NECESARIA EN EL FRONTEND:" . PHP_EOL;
echo "   Reemplaza TODOS los endpoints con IDs hardcodeados" . PHP_EOL;
echo "   Usa los nuevos endpoints /my-company/*" . PHP_EOL;
