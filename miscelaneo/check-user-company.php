<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'superadmin@digitalinnovate.cl')->first();
if ($user) {
    echo 'Usuario: ' . $user->email . PHP_EOL;
    echo 'Empresas del usuario:' . PHP_EOL;
    foreach ($user->companies as $company) {
        echo '- ID: ' . $company->id . ' - Nombre: ' . $company->company_name . PHP_EOL;
    }
} else {
    echo 'Usuario no encontrado' . PHP_EOL;
}

echo PHP_EOL . 'Para acceder a subsidiarias debes usar:' . PHP_EOL;
echo 'GET /api/companies/2/subsidiaries (para Digital Innovate)' . PHP_EOL;
echo 'NO usar /api/companies/1/subsidiaries (es de EcoTech)' . PHP_EOL;
