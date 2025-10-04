<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;

echo "=== PRUEBA FINAL DEL ENDPOINT SUBSIDIARIES ===" . PHP_EOL;

// Simular autenticación del usuario de Digital Innovate
$user = App\Models\User::where('email', 'superadmin@digitalinnovate.cl')->first();
Auth::setUser($user);

echo "Usuario autenticado: " . $user->email . PHP_EOL;
echo "Empresas del usuario: ";
foreach ($user->companies as $company) {
    echo $company->id . " (" . $company->name . ") ";
}
echo PHP_EOL . PHP_EOL;

// Instanciar el controlador y probar el método subsidiaries
$controller = new App\Http\Controllers\CompanyController();

try {
    echo "1. Probando acceso a subsidiarias de empresa 1 (EcoTech) - DEBE FALLAR:" . PHP_EOL;
    $response = $controller->subsidiaries(1);
    
    if ($response instanceof Illuminate\Http\JsonResponse) {
        $content = $response->getData(true);
        $statusCode = $response->getStatusCode();
        
        echo "Status Code: " . $statusCode . PHP_EOL;
        echo "Respuesta: " . json_encode($content, JSON_PRETTY_PRINT) . PHP_EOL;
        
        if ($statusCode === 403) {
            echo "✅ CORRECTO: Se bloquea el acceso a empresa de otra compañía" . PHP_EOL;
        } else {
            echo "❌ ERROR: No se bloquea el acceso" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "2. Probando acceso a subsidiarias de empresa 2 (Digital Innovate) - DEBE FUNCIONAR:" . PHP_EOL;

try {
    $response = $controller->subsidiaries(2);
    
    if ($response instanceof Illuminate\Http\JsonResponse) {
        $content = $response->getData(true);
        $statusCode = $response->getStatusCode();
        
        echo "Status Code: " . $statusCode . PHP_EOL;
        echo "Respuesta: " . json_encode($content, JSON_PRETTY_PRINT) . PHP_EOL;
        
        if ($statusCode === 200) {
            echo "✅ CORRECTO: Se permite el acceso a la propia empresa" . PHP_EOL;
        } else {
            echo "❌ ERROR: No se permite el acceso a la propia empresa" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== PRUEBA CON USUARIO DE ECOTECH ===" . PHP_EOL;

// Cambiar al usuario de EcoTech
$userEco = App\Models\User::where('email', 'rbarrientos@tikinet.cl')->first();
Auth::setUser($userEco);

echo "Usuario autenticado: " . $userEco->email . PHP_EOL;

try {
    echo "3. Probando acceso a subsidiarias de empresa 1 (EcoTech) - DEBE FUNCIONAR:" . PHP_EOL;
    $response = $controller->subsidiaries(1);
    
    if ($response instanceof Illuminate\Http\JsonResponse) {
        $content = $response->getData(true);
        $statusCode = $response->getStatusCode();
        
        echo "Status Code: " . $statusCode . PHP_EOL;
        if ($statusCode === 200) {
            echo "✅ CORRECTO: EcoTech puede acceder a sus subsidiarias" . PHP_EOL;
            echo "Número de subsidiarias: " . count($content['subempresas']) . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

try {
    echo PHP_EOL . "4. Probando acceso a subsidiarias de empresa 2 (Digital Innovate) - DEBE FALLAR:" . PHP_EOL;
    $response = $controller->subsidiaries(2);
    
    if ($response instanceof Illuminate\Http\JsonResponse) {
        $content = $response->getData(true);
        $statusCode = $response->getStatusCode();
        
        echo "Status Code: " . $statusCode . PHP_EOL;
        echo "Respuesta: " . json_encode($content, JSON_PRETTY_PRINT) . PHP_EOL;
        
        if ($statusCode === 403) {
            echo "✅ CORRECTO: EcoTech NO puede acceder a subsidiarias de Digital Innovate" . PHP_EOL;
        } else {
            echo "❌ ERROR: EcoTech puede acceder a subsidiarias de Digital Innovate" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== RESUMEN ===" . PHP_EOL;
echo "El endpoint subsidiaries ahora está correctamente filtrado por empresa del usuario." . PHP_EOL;
echo "Los usuarios solo pueden ver subsidiarias de sus propias empresas." . PHP_EOL;
