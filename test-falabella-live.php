<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Falabella\FalabellaClient;
use App\Services\Falabella\FalabellaMockService;

echo "=== PRUEBA CON DATOS REALES DE FALABELLA ===" . PHP_EOL;

try {
    // 1. Verificar configuración
    echo "1️⃣ Verificando configuración..." . PHP_EOL;
    $config = config('falabella');
    $isMockConfig = $config['use_mock'];
    
    echo "   Modo configurado: " . ($isMockConfig ? 'MOCK' : 'LIVE') . PHP_EOL;
    echo "   URL: " . $config['base_url'] . PHP_EOL;
    echo "   Usuario: " . $config['user_id'] . PHP_EOL;
    echo "   API Key: " . substr($config['api_key'], 0, 8) . "..." . PHP_EOL;
    echo PHP_EOL;

    // 2. Verificar instancia del cliente
    echo "2️⃣ Verificando cliente..." . PHP_EOL;
    $client = app(FalabellaClient::class);
    $isMockInstance = $client instanceof FalabellaMockService;
    
    echo "   Instancia: " . get_class($client) . PHP_EOL;
    echo "   Es Mock: " . ($isMockInstance ? 'SÍ' : 'NO') . PHP_EOL;
    
    if ($isMockConfig === $isMockInstance) {
        echo "   ✅ Configuración consistente" . PHP_EOL;
    } else {
        echo "   ❌ ERROR: Configuración inconsistente!" . PHP_EOL;
        echo "      Config dice: " . ($isMockConfig ? 'mock' : 'live') . PHP_EOL;
        echo "      Instancia es: " . ($isMockInstance ? 'mock' : 'live') . PHP_EOL;
    }
    echo PHP_EOL;

    if (!$isMockInstance) {
        echo "🚀 CONECTANDO AL API REAL DE FALABELLA..." . PHP_EOL;
        echo PHP_EOL;
        
        // 3. Probar conexión básica
        echo "3️⃣ Probando getProducts()..." . PHP_EOL;
        $startTime = microtime(true);
        $products = $client->getProducts(5, 0);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        echo "   ⏱️  Tiempo de respuesta: {$duration}ms" . PHP_EOL;
        echo "   📦 Productos obtenidos: " . count($products['Product'] ?? []) . PHP_EOL;
        
        if (!empty($products['Product'])) {
            echo "   🏷️  Primeros productos:" . PHP_EOL;
            foreach (array_slice($products['Product'], 0, 3) as $i => $product) {
                $name = $product['Name'] ?? 'Sin nombre';
                $sku = $product['SellerSku'] ?? 'Sin SKU';
                $price = isset($product['Price']) ? '$' . number_format($product['Price']) : 'Sin precio';
                $stock = $product['Quantity'] ?? 'Sin stock';
                
                echo "      " . ($i + 1) . ". {$name}" . PHP_EOL;
                echo "         SKU: {$sku} | Precio: {$price} | Stock: {$stock}" . PHP_EOL;
            }
        }
        echo PHP_EOL;

        // 4. Probar resumen de inventario
        echo "4️⃣ Probando getInventorySummary()..." . PHP_EOL;
        $startTime = microtime(true);
        $summary = $client->getInventorySummary();
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        echo "   ⏱️  Tiempo de respuesta: {$duration}ms" . PHP_EOL;
        echo "   📊 Resumen del inventario:" . PHP_EOL;
        echo "      Total productos: " . ($summary['totalProducts'] ?? 0) . PHP_EOL;
        echo "      Valor total: $" . number_format($summary['totalValue'] ?? 0) . PHP_EOL;
        echo "      Stock bajo (≤5): " . ($summary['lowStockCount'] ?? 0) . PHP_EOL;
        echo "      Sin stock: " . ($summary['outOfStockCount'] ?? 0) . PHP_EOL;
        echo "      Precio promedio: $" . number_format($summary['averagePrice'] ?? 0) . PHP_EOL;
        echo PHP_EOL;

        // 5. Probar productos con stock bajo
        echo "5️⃣ Probando getLowStockProducts()..." . PHP_EOL;
        $startTime = microtime(true);
        $lowStock = $client->getLowStockProducts(10);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        echo "   ⏱️  Tiempo de respuesta: {$duration}ms" . PHP_EOL;
        echo "   ⚠️  Productos con stock bajo (≤10): " . count($lowStock) . PHP_EOL;
        
        if (!empty($lowStock)) {
            echo "   🔻 Productos críticos:" . PHP_EOL;
            foreach (array_slice($lowStock, 0, 5) as $product) {
                $name = $product['Name'] ?? 'Sin nombre';
                $stock = $product['Quantity'] ?? 0;
                echo "      - {$name}: {$stock} unidades" . PHP_EOL;
            }
        }
        echo PHP_EOL;

        echo "🎉 ¡CONEXIÓN EXITOSA CON FALABELLA!" . PHP_EOL;
        echo "✅ Todos los endpoints funcionan correctamente" . PHP_EOL;
        echo "📡 Tu frontend puede consumir datos reales desde:" . PHP_EOL;
        echo "   http://chilopson-erp-back.test/api/falabella/*" . PHP_EOL;
        
    } else {
        echo "🧪 MODO MOCK DETECTADO" . PHP_EOL;
        echo "💡 Para usar datos reales:" . PHP_EOL;
        echo "   1. Verifica FALABELLA_USE_MOCK=false en .env" . PHP_EOL;
        echo "   2. Ejecuta: php artisan config:clear" . PHP_EOL;
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo PHP_EOL;
    echo "🔍 DETALLES DEL ERROR:" . PHP_EOL;
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo PHP_EOL;
    
    if (strpos($e->getMessage(), 'Signature mismatch') !== false) {
        echo "🔐 PROBLEMA DE AUTENTICACIÓN:" . PHP_EOL;
        echo "   - Verifica que FALABELLA_USER_ID sea correcto" . PHP_EOL;
        echo "   - Verifica que FALABELLA_API_KEY sea correcto" . PHP_EOL;
        echo "   - Contacta con Falabella para verificar credenciales" . PHP_EOL;
    } elseif (strpos($e->getMessage(), 'Access Denied') !== false) {
        echo "🚫 ACCESO DENEGADO:" . PHP_EOL;
        echo "   - Tu usuario puede no tener permisos para esta acción" . PHP_EOL;
        echo "   - Contacta con Falabella para verificar permisos" . PHP_EOL;
    } elseif (strpos($e->getMessage(), 'Connection') !== false) {
        echo "🌐 PROBLEMA DE CONECTIVIDAD:" . PHP_EOL;
        echo "   - Verifica tu conexión a internet" . PHP_EOL;
        echo "   - Verifica que la URL base sea correcta" . PHP_EOL;
    } else {
        echo "⚠️  ERROR DESCONOCIDO:" . PHP_EOL;
        echo "   - Revisa los logs de Laravel para más detalles" . PHP_EOL;
        echo "   - Verifica todas las configuraciones en .env" . PHP_EOL;
    }
    
    echo PHP_EOL;
    echo "🔄 Para volver a modo mock temporalmente:" . PHP_EOL;
    echo "   1. Cambia FALABELLA_USE_MOCK=true en .env" . PHP_EOL;
    echo "   2. Ejecuta: php artisan config:clear" . PHP_EOL;
}
