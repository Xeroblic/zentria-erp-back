<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICACIÓN DEL SISTEMA ===\n";

try {
    echo "✅ Conexión a BD: OK\n";
    echo "📊 Users: " . \App\Models\User::count() . "\n";
    echo "📊 Companies: " . \App\Models\Company::count() . "\n";
    echo "📊 Roles: " . \Spatie\Permission\Models\Role::count() . "\n";
    echo "📊 Permissions: " . \Spatie\Permission\Models\Permission::count() . "\n";

    // Verificar que existe super-admin
    $superAdmin = \App\Models\User::role('super-admin')->first();
    if ($superAdmin) {
        echo "👤 Super Admin: {$superAdmin->email}\n";
    } else {
        echo "❌ No hay super-admin\n";
    }

    // Verificar rutas
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $apiRoutes = [];
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'api/') === 0) {
            $apiRoutes[] = implode('|', $route->methods()) . ' ' . $uri;
        }
    }
    
    echo "\n🛣️ Rutas API encontradas (" . count($apiRoutes) . "):\n";
    foreach (array_slice($apiRoutes, 0, 10) as $route) {
        echo "  {$route}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
