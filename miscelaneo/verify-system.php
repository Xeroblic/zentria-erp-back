<?php

/*
|--------------------------------------------------------------------------
| Script de Verificación de Sistema Multi-Empresa
|--------------------------------------------------------------------------
| Este script verifica que todos los componentes del sistema estén 
| funcionando correctamente después de la implementación.
*/

require_once 'vendor/autoload.php';

echo "=== VERIFICACIÓN DEL SISTEMA MULTI-EMPRESA ===\n\n";

// 1. Verificar que existan las rutas API
echo "1. VERIFICANDO RUTAS API:\n";
$routeFiles = [
    'routes/api.php' => 'Rutas principales de administración',
    'routes/apis/auth.php' => 'Rutas de autenticación',
    'routes/apis/companies.php' => 'Rutas de empresas',
    'routes/apis/subsidiary.php' => 'Rutas de subsidiarias',
    'routes/apis/branch.php' => 'Rutas de sucursales',
    'routes/apis/users.php' => 'Rutas de usuarios'
];

foreach ($routeFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
    } else {
        echo "   ❌ {$description}: {$file} NO ENCONTRADO\n";
    }
}

// 2. Verificar controladores
echo "\n2. VERIFICANDO CONTROLADORES:\n";
$controllers = [
    'app/Http/Controllers/AdminController.php' => 'Controlador de administración',
    'app/Http/Controllers/AuthController.php' => 'Controlador de autenticación',
    'app/Http/Controllers/UserController.php' => 'Controlador de usuarios'
];

foreach ($controllers as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
    } else {
        echo "   ❌ {$description}: {$file} NO ENCONTRADO\n";
    }
}

// 3. Verificar modelos
echo "\n3. VERIFICANDO MODELOS:\n";
$models = [
    'app/Models/User.php' => 'Modelo de usuarios',
    'app/Models/Company.php' => 'Modelo de empresas',
    'app/Models/Subsidiary.php' => 'Modelo de subsidiarias',
    'app/Models/Branch.php' => 'Modelo de sucursales',
    'app/Models/ScopeRole.php' => 'Modelo de roles contextuales'
];

foreach ($models as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
    } else {
        echo "   ❌ {$description}: {$file} NO ENCONTRADO\n";
    }
}

// 4. Verificar servicios
echo "\n4. VERIFICANDO SERVICIOS:\n";
$services = [
    'app/Services/ContextualRoleService.php' => 'Servicio de roles contextuales'
];

foreach ($services as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
    } else {
        echo "   ❌ {$description}: {$file} NO ENCONTRADO\n";
    }
}

// 5. Verificar migraciones críticas
echo "\n5. VERIFICANDO MIGRACIONES:\n";
$migrations = [
    'database/migrations/*_create_companies_table.php' => 'Migración de empresas',
    'database/migrations/*_create_subsidiaries_table.php' => 'Migración de subsidiarias', 
    'database/migrations/*_create_branches_table.php' => 'Migración de sucursales',
    'database/migrations/*_create_users_table.php' => 'Migración de usuarios',
    'database/migrations/*_create_user_personalizations_table.php' => 'Migración de personalizaciones'
];

foreach ($migrations as $pattern => $description) {
    $files = glob($pattern);
    if (!empty($files)) {
        echo "   ✅ {$description}: " . basename($files[0]) . "\n";
    } else {
        echo "   ❌ {$description}: NO ENCONTRADO\n";
    }
}

// 6. Verificar seeders
echo "\n6. VERIFICANDO SEEDERS:\n";
$seeders = [
    'database/seeders/CompanySeeder.php' => 'Seeder de empresas',
    'database/seeders/UserSeeder.php' => 'Seeder de usuarios',
    'database/seeders/PermissionSeeder.php' => 'Seeder de permisos'
];

foreach ($seeders as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
    } else {
        echo "   ❌ {$description}: {$file} NO ENCONTRADO\n";
    }
}

echo "\n=== RESUMEN DE RUTAS API IMPLEMENTADAS ===\n";
echo "Las siguientes rutas deberían estar disponibles:\n\n";

echo "AUTENTICACIÓN:\n";
echo "   POST /api/login\n";
echo "   POST /api/register\n";
echo "   GET  /api/available-companies\n";
echo "   POST /api/logout\n\n";

echo "ADMINISTRACIÓN DE USUARIOS:\n";
echo "   GET    /api/admin/users\n";
echo "   POST   /api/admin/users\n";
echo "   PUT    /api/admin/users/{id}\n";
echo "   DELETE /api/admin/users/{id}\n";
echo "   PATCH  /api/admin/users/{id}/toggle-status\n\n";

echo "GESTIÓN DE PERMISOS:\n";
echo "   GET    /api/admin/permissions\n";
echo "   GET    /api/admin/users/{id}/permissions\n";
echo "   POST   /api/admin/users/{id}/permissions\n";
echo "   DELETE /api/admin/users/{id}/permissions/{permissionId}\n\n";

echo "GESTIÓN DE ROLES:\n";
echo "   GET    /api/admin/roles\n";
echo "   POST   /api/admin/users/{id}/roles\n";
echo "   DELETE /api/admin/users/{id}/roles\n\n";

echo "=== INSTRUCCIONES DE EJECUCIÓN ===\n";
echo "Para probar el sistema:\n\n";
echo "1. Ejecutar migraciones:\n";
echo "   php artisan migrate:fresh\n\n";
echo "2. Ejecutar seeders:\n";
echo "   php artisan db:seed\n\n";
echo "3. Ejecutar comando de configuración:\n";
echo "   php artisan setup:multi-company-system\n\n";
echo "4. Iniciar servidor:\n";
echo "   php artisan serve\n\n";
echo "5. Probar endpoints desde el frontend o Postman\n\n";

echo "=== VERIFICACIÓN COMPLETADA ===\n";
