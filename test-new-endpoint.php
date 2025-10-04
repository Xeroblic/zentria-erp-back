<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;

echo "=== PRUEBA DEL NUEVO ENDPOINT MY-COMPANY/SUBSIDIARIES ===" . PHP_EOL;

// Probar con usuario de Digital Innovate
$user = App\Models\User::where('email', 'superadmin@digitalinnovate.cl')->first();
Auth::setUser($user);

echo "Usuario: " . $user->email . PHP_EOL;

$controller = new App\Http\Controllers\CompanyController();

try {
    echo "Probando nuevo endpoint myCompanySubsidiaries..." . PHP_EOL;
    $response = $controller->myCompanySubsidiaries();
    
    if ($response instanceof Illuminate\Http\JsonResponse) {
        $content = $response->getData(true);
        $statusCode = $response->getStatusCode();
        
        echo "Status Code: " . $statusCode . PHP_EOL;
        echo "Respuesta: " . json_encode($content, JSON_PRETTY_PRINT) . PHP_EOL;
        
        if ($statusCode === 200) {
            echo "✅ CORRECTO: El nuevo endpoint funciona" . PHP_EOL;
            echo "Empresa: " . $content['empresa']['nombre'] . " (ID: " . $content['empresa']['id'] . ")" . PHP_EOL;
            echo "Subsidiarias encontradas: " . count($content['subempresas']) . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== AHORA PUEDES USAR ===" . PHP_EOL;
echo "GET /api/my-company/subsidiaries" . PHP_EOL;
echo "En lugar de:" . PHP_EOL;
echo "GET /api/companies/1/subsidiaries (❌ incorrecto)" . PHP_EOL;
echo "GET /api/companies/2/subsidiaries (✅ correcto pero hardcodeado)" . PHP_EOL;
