<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNÓSTICO DEL ERROR 500 ===" . PHP_EOL;

echo "1️⃣ Verificando configuración después del arreglo..." . PHP_EOL;
$config = config('falabella');
echo "   Base URL: " . $config['base_url'] . PHP_EOL;
echo "   User ID: " . $config['user_id'] . PHP_EOL;
echo "   Use Mock: " . ($config['use_mock'] ? 'SÍ' : 'NO') . PHP_EOL;
echo PHP_EOL;

echo "2️⃣ Probando el servicio directamente..." . PHP_EOL;
try {
    $client = app(\App\Services\Falabella\FalabellaClient::class);
    $isLive = !($client instanceof \App\Services\Falabella\FalabellaMockService);
    echo "   Servicio activo: " . ($isLive ? 'LIVE' : 'MOCK') . PHP_EOL;
    
    // Test del endpoint que está fallando
    echo "   Probando getInventorySummary()..." . PHP_EOL;
    $inventory = $client->getInventorySummary();
    
    echo "   ✅ Respuesta exitosa:" . PHP_EOL;
    echo "      Total productos: " . ($inventory['totalProducts'] ?? 0) . PHP_EOL;
    echo "      Valor total: $" . number_format($inventory['totalValue'] ?? 0) . PHP_EOL;
    echo "      Stock bajo: " . ($inventory['lowStockCount'] ?? 0) . PHP_EOL;
    echo "      Sin stock: " . ($inventory['outOfStockCount'] ?? 0) . PHP_EOL;
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}
echo PHP_EOL;

echo "3️⃣ Probando otros endpoints..." . PHP_EOL;
$endpoints = [
    'products' => 'getProducts',
    'orders' => 'getOrders',
    'low-stock' => 'getLowStockProducts',
];

foreach ($endpoints as $name => $method) {
    try {
        echo "   Probando {$name}..." . PHP_EOL;
        
        if ($method === 'getProducts') {
            $result = $client->getProducts(3, 0);
            $count = count($result['Product'] ?? []);
            echo "      ✅ {$count} productos encontrados" . PHP_EOL;
        } elseif ($method === 'getOrders') {
            $result = $client->getOrders('2025-08-01T00:00:00Z', null, null, 3, 0);
            $count = count($result['Orders']['Order'] ?? []);
            echo "      ✅ {$count} órdenes encontradas" . PHP_EOL;
        } elseif ($method === 'getLowStockProducts') {
            $result = $client->getLowStockProducts(10);
            $count = count($result);
            echo "      ✅ {$count} productos con stock bajo" . PHP_EOL;
        }
        
    } catch (Exception $e) {
        echo "      ❌ Error en {$name}: " . $e->getMessage() . PHP_EOL;
    }
}
echo PHP_EOL;

echo "4️⃣ URL de prueba para el navegador:" . PHP_EOL;
echo "   http://chilopson-erp-back.test/api/falabella/inventory-summary" . PHP_EOL;
echo PHP_EOL;

echo "✅ RESUMEN:" . PHP_EOL;
if ($config['use_mock']) {
    echo "   - Modo MOCK activado: Los endpoints deberían funcionar" . PHP_EOL;
    echo "   - URL corregida: " . $config['base_url'] . PHP_EOL;
    echo "   - Error 500 debería estar resuelto" . PHP_EOL;
} else {
    echo "   ⚠️  Modo LIVE: Puede fallar por credenciales" . PHP_EOL;
    echo "   - Para desarrollo, usa FALABELLA_USE_MOCK=true" . PHP_EOL;
}
echo PHP_EOL;
echo "🚀 Refresca tu navegador y prueba de nuevo!" . PHP_EOL;
