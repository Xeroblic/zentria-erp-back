<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Falabella\FalabellaClient;
use App\Services\Falabella\FalabellaMockService;

echo "=== DIAGNÓSTICO DEL SERVICIO FALABELLA ===" . PHP_EOL;

try {
    // 1. Verificar configuración
    echo "1️⃣ Verificando configuración..." . PHP_EOL;
    $config = config('falabella');
    echo "   use_mock: " . ($config['use_mock'] ? 'true' : 'false') . " (tipo: " . gettype($config['use_mock']) . ")" . PHP_EOL;
    echo "   base_url: " . $config['base_url'] . PHP_EOL;
    echo "   user_id: " . ($config['user_id'] ? 'SET' : 'NOT_SET') . PHP_EOL;
    echo "   api_key: " . ($config['api_key'] ? 'SET (' . strlen($config['api_key']) . ' chars)' : 'NOT_SET') . PHP_EOL;
    echo PHP_EOL;

    // 2. Verificar qué cliente se está usando
    echo "2️⃣ Verificando instancia del cliente..." . PHP_EOL;
    $client = app(FalabellaClient::class);
    $clientType = get_class($client);
    $isMock = $client instanceof FalabellaMockService;
    
    echo "   Clase: " . $clientType . PHP_EOL;
    echo "   Es Mock: " . ($isMock ? 'SÍ' : 'NO') . PHP_EOL;
    echo "   Estado esperado: " . ($config['use_mock'] ? 'Mock' : 'Live') . PHP_EOL;
    
    if ($config['use_mock'] !== $isMock) {
        echo "   ⚠️  PROBLEMA: La configuración no coincide con la instancia!" . PHP_EOL;
    } else {
        echo "   ✅ Configuración correcta!" . PHP_EOL;
    }
    echo PHP_EOL;

    // 3. Probar obtener productos
    echo "3️⃣ Probando getProducts()..." . PHP_EOL;
    $products = $client->getProducts(3, 0);
    echo "   Productos obtenidos: " . count($products['Product'] ?? []) . PHP_EOL;
    
    if (!empty($products['Product'])) {
        foreach (array_slice($products['Product'], 0, 2) as $i => $product) {
            echo "   Producto " . ($i + 1) . ": {$product['Name']} - \${$product['Price']}" . PHP_EOL;
        }
    }
    echo PHP_EOL;

    // 4. Probar resumen de inventario
    echo "4️⃣ Probando getInventorySummary()..." . PHP_EOL;
    $summary = $client->getInventorySummary();
    echo "   Total productos: {$summary['totalProducts']}" . PHP_EOL;
    echo "   Valor total: \$" . number_format($summary['totalValue']) . PHP_EOL;
    echo "   Modo: " . ($summary['mode'] ?? 'NO_SPECIFIED') . PHP_EOL;
    echo PHP_EOL;

    if ($isMock) {
        echo "🧪 MODO MOCK ACTIVO - Datos de prueba" . PHP_EOL;
        echo "💡 Para usar datos reales: cambia FALABELLA_USE_MOCK=false en .env" . PHP_EOL;
    } else {
        echo "🚀 MODO LIVE ACTIVO - Conectando al API real de Falabella" . PHP_EOL;
        echo "⚠️  Si ves errores, revisa credenciales y permisos en Falabella" . PHP_EOL;
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Detalles del error:" . PHP_EOL;
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    
    if (strpos($e->getMessage(), 'Falabella') !== false) {
        echo PHP_EOL . "💡 Posibles causas:" . PHP_EOL;
        echo "   - Credenciales incorrectas (FALABELLA_USER_ID/FALABELLA_API_KEY)" . PHP_EOL;
        echo "   - Problema de conectividad" . PHP_EOL;
        echo "   - Timestamp/hora del servidor incorrecta" . PHP_EOL;
        echo "   - URL base incorrecta" . PHP_EOL;
    }
}
