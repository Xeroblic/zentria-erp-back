<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Falabella\FalabellaClient;

echo "=== PRUEBA DEL SERVICIO FALABELLA ===" . PHP_EOL;

try {
    // Obtener el cliente Falabella (automáticamente usa mock o real según .env)
    $falabellaClient = app(FalabellaClient::class);
    
    // Determinar si es mock o real
    $isMock = config('falabella.use_mock');
    echo "Modo: " . ($isMock ? 'MOCK (desarrollo)' : 'REAL (producción)') . PHP_EOL;
    echo "URL base: " . config('falabella.base_url') . PHP_EOL;
    echo PHP_EOL;

    // 1. Probar obtener productos
    echo "1️⃣ Probando getProducts()..." . PHP_EOL;
    $products = $falabellaClient->getProducts(5, 0); // Máximo 5 productos
    echo "✅ Productos obtenidos: " . count($products['Product'] ?? []) . PHP_EOL;
    
    if (!empty($products['Product'])) {
        $firstProduct = $products['Product'][0];
        echo "   Primer producto: {$firstProduct['Name']} (SKU: {$firstProduct['SellerSku']})" . PHP_EOL;
    }
    echo PHP_EOL;

    // 2. Probar resumen de inventario
    echo "2️⃣ Probando getInventorySummary()..." . PHP_EOL;
    $summary = $falabellaClient->getInventorySummary();
    echo "✅ Resumen obtenido:" . PHP_EOL;
    echo "   Total productos: {$summary['totalProducts']}" . PHP_EOL;
    echo "   Valor total: $" . number_format($summary['totalValue']) . PHP_EOL;
    echo "   Stock bajo: {$summary['lowStockCount']}" . PHP_EOL;
    echo "   Sin stock: {$summary['outOfStockCount']}" . PHP_EOL;
    echo PHP_EOL;

    // 3. Probar productos con stock bajo
    echo "3️⃣ Probando getLowStockProducts()..." . PHP_EOL;
    $lowStock = $falabellaClient->getLowStockProducts(5);
    echo "✅ Productos con stock bajo (≤5): " . count($lowStock) . PHP_EOL;
    
    foreach ($lowStock as $product) {
        echo "   - {$product['Name']}: {$product['Quantity']} unidades" . PHP_EOL;
    }
    echo PHP_EOL;

    // 4. Probar productos más vendidos
    echo "4️⃣ Probando getBestSellingProducts()..." . PHP_EOL;
    $bestSellers = $falabellaClient->getBestSellingProducts(30);
    echo "✅ Productos más vendidos (últimos 30 días): " . count($bestSellers) . PHP_EOL;
    
    foreach (array_slice($bestSellers, 0, 3) as $item) {
        echo "   - {$item['product']['Name']}: {$item['totalSold']} ventas" . PHP_EOL;
    }
    echo PHP_EOL;

    // 5. Probar categorías
    echo "5️⃣ Probando getCategories()..." . PHP_EOL;
    $categories = $falabellaClient->getCategories();
    echo "✅ Categorías obtenidas: " . count($categories['Category'] ?? []) . PHP_EOL;
    echo PHP_EOL;

    echo "🎉 ¡TODAS LAS PRUEBAS EXITOSAS!" . PHP_EOL;
    echo PHP_EOL;
    echo "🚀 ENDPOINTS DISPONIBLES PARA EL FRONTEND:" . PHP_EOL;
    echo "   GET /api/falabella/products?limit=100&offset=0" . PHP_EOL;
    echo "   GET /api/falabella/stock" . PHP_EOL;
    echo "   GET /api/falabella/sales?startDate=2025-01-01&endDate=2025-12-31" . PHP_EOL;
    echo "   GET /api/falabella/low-stock?threshold=5" . PHP_EOL;
    echo "   GET /api/falabella/best-sellers?days=30" . PHP_EOL;
    echo "   GET /api/falabella/inventory-summary" . PHP_EOL;
    echo "   GET /api/falabella/categories" . PHP_EOL;
    echo "   PUT /api/falabella/products/{sku}/price" . PHP_EOL;
    echo "   PUT /api/falabella/products/{sku}/stock" . PHP_EOL;
    echo PHP_EOL;
    echo "💡 Para cambiar a modo REAL:" . PHP_EOL;
    echo "   1. Configura FALABELLA_USER_ID y FALABELLA_API_KEY en .env" . PHP_EOL;
    echo "   2. Cambia FALABELLA_USE_MOCK=false en .env" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Revisa la configuración en .env y los logs" . PHP_EOL;
}
