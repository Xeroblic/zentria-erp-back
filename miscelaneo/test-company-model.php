<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;

echo "=== Prueba de actualización de empresa con subsidiaries ===\n";

try {
    // Obtener la empresa principal
    $company = Company::with('subsidiaries')->first();
    
    if (!$company) {
        echo "No se encontró ninguna empresa en la base de datos.\n";
        exit(1);
    }
    
    echo "Empresa encontrada: {$company->company_name}\n";
    echo "Subsidiaries cargadas: " . ($company->relationLoaded('subsidiaries') ? 'Sí' : 'No') . "\n";
    echo "Número de subsidiaries: " . $company->subsidiaries->count() . "\n";
    
    // Simular actualización
    $originalName = $company->company_name;
    $newName = 'EcoTech SPA Updated';
    
    $company->update([
        'company_name' => $newName,
        'company_rut' => '76795560-9',
        'business_activity' => 'Soluciones tecnológicas y servicios informáticos'
    ]);
    
    echo "Empresa actualizada exitosamente.\n";
    echo "Nombre anterior: {$originalName}\n";
    echo "Nombre nuevo: {$company->company_name}\n";
    
    // Revertir cambio
    $company->update(['company_name' => $originalName]);
    echo "Cambio revertido.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
