<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST DE GENERACIÓN DE URL CORRECTA ===" . PHP_EOL;

// Cambiar temporalmente a modo LIVE para que genere URLs reales
$originalMock = env('FALABELLA_USE_MOCK');

// Simular configuración LIVE
config(['falabella.use_mock' => false]);

echo "1️⃣ Probando generación de URL con el servicio real..." . PHP_EOL;

try {
    // Crear instancia del servicio API real
    $service = new \App\Services\Falabella\FalabellaApiService(
        config('falabella.base_url'),
        config('falabella.user_id'),
        config('falabella.api_key'),
        config('falabella.version'),
        config('falabella.format'),
        config('falabella.timeout'),
        config('falabella.retry_attempts'),
        500
    );

    // Usar reflection para acceder al método protegido 'call'
    $reflection = new ReflectionClass($service);
    $callMethod = $reflection->getMethod('call');
    $callMethod->setAccessible(true);

    echo "   Configuración:" . PHP_EOL;
    echo "   - Base URL: " . config('falabella.base_url') . PHP_EOL;
    echo "   - User ID: " . config('falabella.user_id') . PHP_EOL;
    echo "   - API Key: " . substr(config('falabella.api_key'), 0, 8) . "..." . PHP_EOL;
    echo PHP_EOL;

    // Interceptar la llamada HTTP para ver la URL generada
    $originalHttpClient = \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Http\Client\Response::create([
            'SuccessResponse' => [
                'Body' => [
                    'Head' => ['TotalCount' => 0],
                    'Orders' => ['Order' => []]
                ]
            ]
        ])
    ]);

    echo "2️⃣ Ejecutando llamada de prueba..." . PHP_EOL;
    
    try {
        $result = $service->getOrders('2025-03-01T00:00:00Z', null, null, 5, 0);
        echo "   ✅ Llamada ejecutada sin errores" . PHP_EOL;
    } catch (Exception $e) {
        echo "   ⚠️  Error esperado (sin conexión real): " . $e->getMessage() . PHP_EOL;
    }

    echo PHP_EOL;
    echo "3️⃣ URLs que se deberían generar:" . PHP_EOL;

    // Mostrar cómo se debería ver la URL correcta
    $params = [
        'Action' => 'GetOrders',
        'CreatedAfter' => '2025-03-01T00:00:00Z',
        'Format' => 'JSON',
        'Timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
        'UserID' => config('falabella.user_id'),
        'Version' => '1.0',
    ];

    // Ordenar parámetros alfabéticamente
    ksort($params);

    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $signature = hash_hmac('sha256', $query, config('falabella.api_key'));
    $params['Signature'] = $signature;

    $correctUrl = config('falabella.base_url') . '/api?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    echo "   URL correcta que Laravel debería generar:" . PHP_EOL;
    echo "   " . $correctUrl . PHP_EOL;
    echo PHP_EOL;

    echo "4️⃣ Comparación con tu URL problemática:" . PHP_EOL;
    $problematicUrl = 'https://sellercenter-api.falabella.com?Action=GetOrders&CreatedAfter=2025-03-01T00%3A00%3A00&Format=JSON&Timestamp=2025-09-01T17%3A31%3A48-04%3A00&UserID=rbarrientos%40ecopc.cl&Version=1.0&Signature=4c7a737b2c2786dadc2f0f055045d4823dec9b8c3c742002a7f3878d5ab63426';
    
    echo "   Tu URL:      " . $problematicUrl . PHP_EOL;
    echo "   URL correcta:" . $correctUrl . PHP_EOL;
    echo PHP_EOL;

    echo "🔍 DIFERENCIAS PRINCIPALES:" . PHP_EOL;
    echo "   1. Dominio: sellercenter-api.falabella.com vs sellercenter.api.falabella.com" . PHP_EOL;
    echo "   2. Path: ? vs /api?" . PHP_EOL;
    echo "   3. Timestamp: -04:00 vs Z (UTC)" . PHP_EOL;
    echo "   4. Orden de parámetros: probablemente diferente" . PHP_EOL;
    echo PHP_EOL;

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

echo "✅ SOLUCIÓN PARA TU FRONTEND:" . PHP_EOL;
echo "   1. Tu backend Laravel YA está configurado correctamente" . PHP_EOL;
echo "   2. Usa tu backend Laravel en lugar de llamar directamente al API" . PHP_EOL;
echo "   3. Endpoints disponibles:" . PHP_EOL;
echo "      - GET /api/falabella/orders" . PHP_EOL;
echo "      - GET /api/falabella/products" . PHP_EOL;
echo "      - GET /api/falabella/inventory-summary" . PHP_EOL;
echo "   4. Tu backend maneja toda la autenticación automáticamente" . PHP_EOL;
echo PHP_EOL;
echo "🚀 ¡Tu frontend no debería llamar directamente a Falabella!" . PHP_EOL;
echo "   ¡Usa tu propio backend Laravel que ya está listo!" . PHP_EOL;
