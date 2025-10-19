<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIAGNÓSTICO DE AISLAMIENTO ENTRE EMPRESAS ===\n\n";

// 1. Ver todas las empresas
echo "1. EMPRESAS EN EL SISTEMA:\n";
echo str_repeat("-", 40) . "\n";
$companies = App\Models\Company::all();
foreach ($companies as $company) {
    echo "ID: {$company->id} | {$company->company_name} | RUT: {$company->company_rut}\n";
}

// 2. Ver usuarios y sus empresas asociadas
echo "\n2. USUARIOS Y SUS EMPRESAS:\n";
echo str_repeat("-", 40) . "\n";
$users = App\Models\User::with(['companies'])->get();
foreach ($users as $user) {
    echo "👤 {$user->first_name} {$user->last_name} ({$user->email})\n";
    if ($user->companies->count() > 0) {
        foreach ($user->companies as $company) {
            echo "   └── 🏢 {$company->company_name} (ID: {$company->id})\n";
        }
    } else {
        echo "   └── ❌ Sin empresas asociadas\n";
    }
    echo "\n";
}

// 3. Ver tabla pivot company_user
echo "3. RELACIONES COMPANY-USER (PIVOT):\n";
echo str_repeat("-", 40) . "\n";
$pivots = DB::table('company_user')->get();
foreach ($pivots as $pivot) {
    $user = App\Models\User::find($pivot->user_id);
    $company = App\Models\Company::find($pivot->company_id);
    echo "User: {$user->email} ↔ Company: {$company->company_name}\n";
}

// 4. Verificar middleware de contexto
echo "\n4. VERIFICACIÓN DE CONTEXTO POR EMPRESA:\n";
echo str_repeat("-", 40) . "\n";

// Simular usuario de EcoTech
$ecoUser = App\Models\User::where('email', 'rbarrientos@tikinet.cl')->first();
if ($ecoUser) {
    echo "👤 Usuario EcoTech: {$ecoUser->email}\n";
    echo "Empresas accesibles:\n";
    foreach ($ecoUser->companies as $company) {
        echo "   - {$company->company_name}\n";
    }
}

// Simular usuario de Digital Innovate
$diUser = App\Models\User::where('email', 'superadmin@digitalinnovate.cl')->first();
if ($diUser) {
    echo "\n👤 Usuario Digital Innovate: {$diUser->email}\n";
    echo "Empresas accesibles:\n";
    foreach ($diUser->companies as $company) {
        echo "   - {$company->company_name}\n";
    }
}

// 5. Verificar si hay leakage de datos
echo "\n5. POSIBLE FILTRACIÓN DE DATOS:\n";
echo str_repeat("-", 40) . "\n";

$usersWithMultipleCompanies = App\Models\User::with('companies')
    ->get()
    ->filter(function($user) {
        return $user->companies->count() > 1;
    });

if ($usersWithMultipleCompanies->count() > 0) {
    echo "⚠️  USUARIOS CON ACCESO A MÚLTIPLES EMPRESAS:\n";
    foreach ($usersWithMultipleCompanies as $user) {
        echo "👤 {$user->email}:\n";
        foreach ($user->companies as $company) {
            echo "   - {$company->company_name}\n";
        }
        echo "\n";
    }
} else {
    echo "✅ No hay usuarios con acceso a múltiples empresas\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
