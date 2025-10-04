<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test del endpoint subsidiaries con usuario de Digital Innovate
echo 'Probando endpoint subsidiaries con usuario de Digital Innovate...' . PHP_EOL;

// Simular login del usuario de Digital Innovate
$user = App\Models\User::where('email', 'superadmin@digitalinnovate.cl')->first();
if (!$user) {
    echo 'Usuario no encontrado' . PHP_EOL;
    exit;
}

echo 'Usuario encontrado: ' . $user->email . PHP_EOL;
echo 'Empresas del usuario: ';
foreach ($user->companies as $company) {
    echo $company->id . ' (' . $company->name . ') ';
}
echo PHP_EOL;

// Intentar acceder a subsidiarias de empresa 1 (EcoTech)
echo PHP_EOL . 'Intentando acceder a subsidiarias de empresa ID 1 (EcoTech)...' . PHP_EOL;

$userCompanyIds = $user->companies->pluck('id')->toArray();
echo 'IDs de empresas del usuario: ' . implode(', ', $userCompanyIds) . PHP_EOL;

if (!in_array(1, $userCompanyIds)) {
    echo 'CORRECTO: Usuario NO tiene acceso a empresa 1 - se bloquearía el acceso' . PHP_EOL;
} else {
    echo 'ERROR: Usuario SÍ tiene acceso a empresa 1' . PHP_EOL;
}

// Intentar acceder a subsidiarias de empresa 2 (Digital Innovate)  
echo PHP_EOL . 'Intentando acceder a subsidiarias de empresa ID 2 (Digital Innovate)...' . PHP_EOL;
if (in_array(2, $userCompanyIds)) {
    echo 'CORRECTO: Usuario SÍ tiene acceso a empresa 2' . PHP_EOL;
    
    $company = App\Models\Company::find(2);
    if ($company) {
        $subsidiaries = $company->subsidiaries()->with('branches')->get();
        echo 'Subsidiarias encontradas para Digital Innovate: ' . $subsidiaries->count() . PHP_EOL;
        foreach ($subsidiaries as $sub) {
            echo '- ' . $sub->name . ' (ID: ' . $sub->id . ')' . PHP_EOL;
        }
    }
} else {
    echo 'ERROR: Usuario NO tiene acceso a empresa 2' . PHP_EOL;
}

echo PHP_EOL . '--- PRUEBA CON USUARIO ECOTECH ---' . PHP_EOL;

// Ahora probar con usuario de EcoTech
$userEco = App\Models\User::where('email', 'rbarrientos@tikinet.cl')->first();
if ($userEco) {
    echo 'Usuario EcoTech encontrado: ' . $userEco->email . PHP_EOL;
    
    $userEcoCompanyIds = $userEco->companies->pluck('id')->toArray();
    echo 'IDs de empresas del usuario EcoTech: ' . implode(', ', $userEcoCompanyIds) . PHP_EOL;
    
    if (in_array(1, $userEcoCompanyIds)) {
        echo 'CORRECTO: Usuario EcoTech SÍ tiene acceso a empresa 1' . PHP_EOL;
        
        $company = App\Models\Company::find(1);
        if ($company) {
            $subsidiaries = $company->subsidiaries()->with('branches')->get();
            echo 'Subsidiarias encontradas para EcoTech: ' . $subsidiaries->count() . PHP_EOL;
            foreach ($subsidiaries as $sub) {
                echo '- ' . $sub->name . ' (ID: ' . $sub->id . ')' . PHP_EOL;
            }
        }
    }
    
    if (!in_array(2, $userEcoCompanyIds)) {
        echo 'CORRECTO: Usuario EcoTech NO tiene acceso a empresa 2 - se bloquearía' . PHP_EOL;
    } else {
        echo 'ERROR: Usuario EcoTech SÍ tiene acceso a empresa 2' . PHP_EOL;
    }
}
