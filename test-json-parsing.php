<?php

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;

echo "\n=== PRUEBA DIRECTA DE REQUEST CON JSON ===\n";

try {
    // Datos de prueba
    $jsonData = json_encode(['permissions' => ['view-user', 'edit-user']]);
    echo "📤 JSON a enviar: {$jsonData}\n";
    
    // Crear request con contenido JSON en el body
    $request = new Request();
    $request->initialize(
        [], // query
        [], // request 
        [], // attributes
        [], // cookies
        [], // files
        [
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token_example'
        ], // server
        $jsonData // content
    );
    
    echo "✅ Request creado\n";
    echo "📋 Content-Type: " . $request->header('Content-Type') . "\n";
    echo "📋 Raw content: " . $request->getContent() . "\n";
    echo "📋 Request all: " . json_encode($request->all()) . "\n";
    
    // Simular el parsing manual que agregamos al controlador
    $requestData = $request->all();
    if (empty($requestData) && $request->getContent()) {
        $jsonData = json_decode($request->getContent(), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            $requestData = $jsonData;
        }
    }
    
    echo "📋 Datos parseados: " . json_encode($requestData) . "\n";
    
    // Probar validación
    $validator = \Validator::make($requestData, [
        'permissions' => 'required|array',
        'permissions.*' => 'string|exists:permissions,name'
    ]);
    
    if ($validator->fails()) {
        echo "❌ Validación falló: " . json_encode($validator->errors()) . "\n";
    } else {
        echo "✅ Validación exitosa\n";
        echo "📋 Permisos a asignar: " . implode(', ', $requestData['permissions']) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
