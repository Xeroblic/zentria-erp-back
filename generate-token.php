<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

echo "=== GENERANDO TOKEN PARA PROBAR API ===" . PHP_EOL;

// Buscar cualquier usuario existente
$user = User::first();

if (!$user) {
    echo "❌ No hay usuarios en la base de datos" . PHP_EOL;
    echo "💡 Crea un usuario primero con: php artisan db:seed" . PHP_EOL;
    exit;
}

// Generar token JWT
$token = JWTAuth::fromUser($user);

echo "✅ Token generado para: " . $user->email . PHP_EOL;
echo PHP_EOL;
echo "🔑 TOKEN (copia esto):" . PHP_EOL;
echo $token . PHP_EOL;
echo PHP_EOL;
echo "🧪 PRUEBAS QUE PUEDES HACER:" . PHP_EOL;
echo PHP_EOL;
echo "1️⃣ Diagnóstico del modo (GET):" . PHP_EOL;
echo "   URL: http://chilopson-erp-back.test/api/falabella/_mode" . PHP_EOL;
echo "   Header: Authorization: Bearer " . $token . PHP_EOL;
echo PHP_EOL;
echo "2️⃣ Productos (GET):" . PHP_EOL;
echo "   URL: http://chilopson-erp-back.test/api/falabella/products?limit=5" . PHP_EOL;
echo "   Header: Authorization: Bearer " . $token . PHP_EOL;
echo PHP_EOL;
echo "3️⃣ Resumen de inventario (GET):" . PHP_EOL;
echo "   URL: http://chilopson-erp-back.test/api/falabella/inventory-summary" . PHP_EOL;
echo "   Header: Authorization: Bearer " . $token . PHP_EOL;
echo PHP_EOL;
echo "💡 Si quieres volver a modo mock, cambia FALABELLA_USE_MOCK=true en .env" . PHP_EOL;
