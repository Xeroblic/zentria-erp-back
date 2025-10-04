<?php

/**
 * 🧪 VALIDADOR DE URLs DE INVITACIONES
 * 
 * Este script valida que todas las URLs del sistema de invitaciones
 * funcionan correctamente y ayuda a identificar problemas de routing.
 */

echo "🧪 VALIDADOR DE URLs DE INVITACIONES\n";
echo "=====================================\n\n";

// Configuración
$baseUrl = 'http://chilopson-erp-back.test';
$testUid = '1caec32d-3b8a-4739-9a47-a57a80f042ee';
$testToken = 'sqQF2bKiLAFMj4YhZ0OIjR6gDuLneUmv';

/**
 * Función para hacer peticiones HTTP
 */
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * Función para probar una URL
 */
function testUrl($label, $url, $expectedStatus = 200, $method = 'GET', $data = null) {
    echo "🔍 Probando: $label\n";
    echo "   URL: $url\n";
    
    $result = makeRequest($url, $method, $data);
    
    if ($result['error']) {
        echo "   ❌ ERROR: {$result['error']}\n";
        return false;
    }
    
    $statusIcon = $result['status'] == $expectedStatus ? '✅' : '❌';
    echo "   $statusIcon Status: {$result['status']} (esperado: $expectedStatus)\n";
    
    if ($result['status'] == $expectedStatus) {
        $response = json_decode($result['response'], true);
        if ($response) {
            echo "   📊 Respuesta válida: " . 
                 (isset($response['data']) ? 'data presente' : 'estructura básica') . "\n";
        }
        echo "   ✅ ÉXITO\n";
        return true;
    } else {
        echo "   ❌ FALLO\n";
        echo "   📄 Respuesta: " . substr($result['response'], 0, 200) . "...\n";
        return false;
    }
}

echo "🎯 PROBANDO URLs CORRECTAS\n";
echo "========================\n\n";

$correctTests = [
    [
        'label' => 'Información de invitación (URL correcta)',
        'url' => "$baseUrl/api/invitations/$testUid/$testToken/info",
        'expected' => 200
    ],
    [
        'label' => 'Lista de invitaciones (requiere auth)',
        'url' => "$baseUrl/api/invitations",
        'expected' => 401  // Sin autenticación debe devolver 401
    ],
    [
        'label' => 'Crear invitación (requiere auth)',
        'url' => "$baseUrl/api/invitations",
        'expected' => 401,  // Sin autenticación debe devolver 401
        'method' => 'POST'
    ]
];

$successCount = 0;
foreach ($correctTests as $test) {
    if (testUrl(
        $test['label'], 
        $test['url'], 
        $test['expected'], 
        $test['method'] ?? 'GET'
    )) {
        $successCount++;
    }
    echo "\n";
}

echo "🚨 PROBANDO URLs INCORRECTAS (deben fallar)\n";
echo "==========================================\n\n";

$incorrectTests = [
    [
        'label' => 'URL con doble api (debe fallar)',
        'url' => "$baseUrl/api/api/invitations/$testUid/$testToken/info",
        'expected' => 404  // Debe devolver 404
    ],
    [
        'label' => 'URL sin api (debe fallar)',
        'url' => "$baseUrl/invitations/$testUid/$testToken/info",
        'expected' => 404  // Debe devolver 404
    ]
];

$expectedFailCount = 0;
foreach ($incorrectTests as $test) {
    if (testUrl(
        $test['label'], 
        $test['url'], 
        $test['expected']
    )) {
        $expectedFailCount++;
    }
    echo "\n";
}

echo "📊 RESUMEN DE RESULTADOS\n";
echo "======================\n";
echo "✅ URLs correctas funcionando: $successCount/" . count($correctTests) . "\n";
echo "❌ URLs incorrectas fallando (como debe ser): $expectedFailCount/" . count($incorrectTests) . "\n";

$totalTests = count($correctTests) + count($incorrectTests);
$totalPassed = $successCount + $expectedFailCount;

echo "\n🎯 RESULTADO GENERAL: $totalPassed/$totalTests pruebas exitosas\n";

if ($totalPassed == $totalTests) {
    echo "\n🎉 ¡TODAS LAS PRUEBAS PASARON! El sistema funciona correctamente.\n";
    echo "   El problema es la construcción de URLs del cliente.\n";
} else {
    echo "\n⚠️  Hay problemas con el sistema de routing.\n";
}

echo "\n💡 URLS CORRECTAS PARA EL CLIENTE:\n";
echo "  Base URL: $baseUrl/api\n";
echo "  Info: /invitations/{uid}/{token}/info\n";
echo "  Accept: /invitations/{uid}/{token}/accept\n";
echo "  List: /invitations (requiere auth)\n";
echo "  Create: /invitations (POST, requiere auth)\n";

echo "\n❌ URLs QUE EL CLIENTE NO DEBE USAR:\n";
echo "  ❌ $baseUrl/api/api/invitations/... (doble api)\n";
echo "  ❌ $baseUrl/invitations/... (sin api)\n";

echo "\n🔧 Para generar nueva invitación de prueba:\n";
echo "   php create-test-invitation.php\n";
