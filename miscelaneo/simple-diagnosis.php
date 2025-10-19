<?php

echo "=== DIAGNÓSTICO SIMPLE DEL PROBLEMA ===" . PHP_EOL;

echo "🔍 PROBLEMAS EN TU URL:" . PHP_EOL;
$tuUrl = 'https://sellercenter-api.falabella.com?Action=GetOrders&CreatedAfter=2025-03-01T00%3A00%3A00&Format=JSON&Timestamp=2025-09-01T17%3A31%3A48-04%3A00&UserID=rbarrientos%40ecopc.cl&Version=1.0&Signature=4c7a737b2c2786dadc2f0f055045d4823dec9b8c3c742002a7f3878d5ab63426';

echo "Tu URL problemática:" . PHP_EOL;
echo $tuUrl . PHP_EOL;
echo PHP_EOL;

// Generar URL correcta
$correctBaseUrl = 'https://sellercenter.api.falabella.com/api';
$apiKey = 'ade1b7960deee161d9193c60c3416da3f1b19587';
$userId = 'rbarrientos@ecopc.cl';

$params = [
    'Action' => 'GetOrders',
    'CreatedAfter' => '2025-03-01T00:00:00Z',
    'Format' => 'JSON',
    'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'), // UTC timestamp
    'UserID' => $userId,
    'Version' => '1.0',
];

// Ordenar alfabéticamente (IMPORTANTE)
ksort($params);

echo "Parámetros ordenados alfabéticamente:" . PHP_EOL;
foreach ($params as $key => $value) {
    echo "   {$key} = {$value}" . PHP_EOL;
}
echo PHP_EOL;

// Generar query string
$query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
echo "Query string para firma:" . PHP_EOL;
echo $query . PHP_EOL;
echo PHP_EOL;

// Generar firma
$signature = hash_hmac('sha256', $query, $apiKey);
echo "Firma HMAC generada:" . PHP_EOL;
echo $signature . PHP_EOL;
echo PHP_EOL;

// URL final
$params['Signature'] = $signature;
$finalUrl = $correctBaseUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

echo "URL CORRECTA que deberías usar:" . PHP_EOL;
echo $finalUrl . PHP_EOL;
echo PHP_EOL;

echo "🔧 ERRORES EN TU URL:" . PHP_EOL;
echo "1. ❌ Dominio: 'sellercenter-api' debería ser 'sellercenter.api'" . PHP_EOL;
echo "2. ❌ Falta '/api' después del dominio" . PHP_EOL;
echo "3. ❌ Timestamp con zona horaria (-04:00) debería ser UTC (Z)" . PHP_EOL;
echo "4. ❌ Posiblemente parámetros desordenados antes de firmar" . PHP_EOL;
echo PHP_EOL;

echo "💡 SOLUCIÓN RECOMENDADA:" . PHP_EOL;
echo "¡NO hagas llamadas directas desde tu frontend a Falabella!" . PHP_EOL;
echo "Usa tu backend Laravel que YA está configurado correctamente:" . PHP_EOL;
echo "   - http://localhost:8000/api/falabella/orders" . PHP_EOL;
echo "   - http://localhost:8000/api/falabella/products" . PHP_EOL;
echo "   - http://localhost:8000/api/falabella/inventory-summary" . PHP_EOL;
echo PHP_EOL;
echo "🚀 Tu backend Laravel maneja toda la autenticación automáticamente" . PHP_EOL;
