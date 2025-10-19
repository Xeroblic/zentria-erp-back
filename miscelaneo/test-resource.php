<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Http\Resources\CompanyResource;

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Prueba de CompanyResource ===\n";

try {
    // Obtener empresa
    $company = Company::with('subsidiaries')->first();
    
    if (!$company) {
        echo "No se encontró empresa\n";
        exit(1);
    }
    
    echo "Empresa encontrada: {$company->company_name}\n";
    echo "Subsidiaries cargadas: " . ($company->relationLoaded('subsidiaries') ? 'Sí' : 'No') . "\n";
    
    // Crear resource
    $resource = new CompanyResource($company);
    
    // Convertir a array
    $array = $resource->toArray(request());
    
    echo "Resource creado exitosamente\n";
    echo "Datos del resource:\n";
    echo json_encode($array, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
