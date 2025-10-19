<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNÓSTICO DE AUTENTICACIÓN FALABELLA ===" . PHP_EOL;

// 1. Verificar configuración
echo "1️⃣ Configuración actual:" . PHP_EOL;
$config = config('falabella');
echo "   Base URL: " . $config['base_url'] . PHP_EOL;
echo "   User ID: " . $config['user_id'] . PHP_EOL;
echo "   API Key: " . substr($config['api_key'], 0, 8) . "..." . PHP_EOL;
echo "   Use Mock: " . ($config['use_mock'] ? 'SÍ' : 'NO') . PHP_EOL;
echo PHP_EOL;

// 2. Simular la generación de firma como lo hace Falabella
echo "2️⃣ Simulando generación de firma HMAC:" . PHP_EOL;

function generateFalabellaSignature($params, $apiKey) {
    // Importante: Los parámetros deben estar ordenados alfabéticamente
    ksort($params);
    
    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    
    echo "   Query string generada: " . $query . PHP_EOL;
    
    $signature = hash_hmac('sha256', $query, $apiKey);
    
    echo "   Firma generada: " . $signature . PHP_EOL;
    
    return $signature;
}

// Parámetros de ejemplo
$params = [
    'Action' => 'GetOrders',
    'CreatedAfter' => '2025-03-01T00:00:00Z',
    'Format' => 'JSON',
    'Timestamp' => now()->utc()->toIso8601String(),
    'UserID' => $config['user_id'],
    'Version' => '1.0',
];

echo "   Parámetros antes de ordenar:" . PHP_EOL;
foreach ($params as $key => $value) {
    echo "      {$key} = {$value}" . PHP_EOL;
}
echo PHP_EOL;

$signature = generateFalabellaSignature($params, $config['api_key']);
$params['Signature'] = $signature;

echo "3️⃣ URL final que se debería generar:" . PHP_EOL;
$finalUrl = $config['base_url'] . '/api?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
echo "   " . $finalUrl . PHP_EOL;
echo PHP_EOL;

// 3. Verificar contra tu URL
echo "4️⃣ Análisis de tu URL problemática:" . PHP_EOL;
$yourUrl = 'https://sellercenter-api.falabella.com?Action=GetOrders&CreatedAfter=2025-03-01T00%3A00%3A00&Format=JSON&Timestamp=2025-09-01T17%3A31%3A48-04%3A00&UserID=rbarrientos%40ecopc.cl&Version=1.0&Signature=4c7a737b2c2786dadc2f0f055045d4823dec9b8c3c742002a7f3878d5ab63426';

echo "   Tu URL: " . $yourUrl . PHP_EOL;
echo PHP_EOL;

echo "🔍 PROBLEMAS DETECTADOS:" . PHP_EOL;

// Problema 1: URL base incorrecta
if (strpos($yourUrl, 'sellercenter-api.falabella.com?') !== false) {
    echo "   ❌ URL base incorrecta: falta '/api' al final" . PHP_EOL;
    echo "      Debería ser: https://sellercenter.api.falabella.com/api" . PHP_EOL;
} else {
    echo "   ✅ URL base correcta" . PHP_EOL;
}

// Problema 2: Timestamp con zona horaria
if (strpos($yourUrl, '-04%3A00') !== false) {
    echo "   ❌ Timestamp con zona horaria: debería ser UTC" . PHP_EOL;
    echo "      Tu timestamp: 2025-09-01T17:31:48-04:00 (con zona horaria)" . PHP_EOL;
    echo "      Debería ser: " . now()->utc()->toIso8601String() . " (UTC)" . PHP_EOL;
} else {
    echo "   ✅ Timestamp UTC correcto" . PHP_EOL;
}

// Problema 3: Codificación de parámetros
if (strpos($yourUrl, '%3A00%3A00') !== false) {
    echo "   ⚠️  Codificación de URL: verifica que sea consistente" . PHP_EOL;
}

echo PHP_EOL;
echo "🔧 SOLUCIONES:" . PHP_EOL;
echo "   1. Usar la URL base correcta con '/api'" . PHP_EOL;
echo "   2. Timestamps siempre en UTC (sin zona horaria)" . PHP_EOL;
echo "   3. Parámetros ordenados alfabéticamente antes de firmar" . PHP_EOL;
echo "   4. Verificar que la API Key sea exactamente la que te dio Falabella" . PHP_EOL;
echo PHP_EOL;

// 4. Test con el servicio
echo "5️⃣ Verificando servicio Laravel:" . PHP_EOL;
try {
    $client = app(\App\Services\Falabella\FalabellaClient::class);
    $isLive = !($client instanceof \App\Services\Falabella\FalabellaMockService);
    
    echo "   Servicio activo: " . ($isLive ? 'LIVE' : 'MOCK') . PHP_EOL;
    
    if (!$isLive) {
        echo "   ✅ En modo MOCK - las peticiones funcionarán sin problemas" . PHP_EOL;
        
        // Test rápido
        $result = $client->getOrders('2025-08-01T00:00:00Z', null, null, 5, 0);
        echo "   📋 Test exitoso: " . count($result['Orders']['Order'] ?? []) . " órdenes encontradas" . PHP_EOL;
    } else {
        echo "   ⚠️  En modo LIVE - requiere credenciales válidas" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "   ❌ Error en servicio: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;
echo "💡 RECOMENDACIÓN:" . PHP_EOL;
echo "   Usa modo MOCK para desarrollo del frontend" . PHP_EOL;
echo "   Contacta con Falabella para verificar credenciales LIVE" . PHP_EOL;
