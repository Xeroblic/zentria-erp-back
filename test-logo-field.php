<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Verificación del campo company_logo ===\n";

try {
    $results = DB::select('DESCRIBE companies company_logo');
    if (count($results) > 0) {
        $field = $results[0];
        echo "Campo: company_logo\n";
        echo "Tipo: {$field->Type}\n";
        echo "Null permitido: {$field->Null}\n";
        echo "Default: {$field->Default}\n";
        
        if (strpos(strtolower($field->Type), 'longtext') !== false) {
            echo "✅ El campo soporta imágenes base64 (longtext)\n";
            echo "✅ Puede almacenar imágenes en formato:\n";
            echo "   - PNG\n";
            echo "   - JPEG\n";
            echo "   - WebP\n";
            echo "   - GIF\n";
            echo "   - SVG\n";
        } elseif (strpos(strtolower($field->Type), 'varchar') !== false || strpos(strtolower($field->Type), 'string') !== false) {
            echo "⚠️  El campo solo puede almacenar URLs o rutas (varchar/string)\n";
            echo "❌ NO puede almacenar imágenes base64 directamente\n";
        }
    }
    
    // Probar inserción de ejemplo
    echo "\n=== Prueba de inserción de logo ===\n";
    
    $company = App\Models\Company::first();
    if ($company) {
        // Prueba con URL
        $company->company_logo = 'https://example.com/logo.png';
        $company->save();
        echo "✅ URL guardada exitosamente\n";
        
        // Prueba con base64 pequeño (solo si es longtext)
        if (strpos(strtolower($field->Type), 'longtext') !== false) {
            $smallBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
            $company->company_logo = $smallBase64;
            $company->save();
            echo "✅ Base64 pequeño guardado exitosamente\n";
        }
        
        // Revertir
        $company->company_logo = null;
        $company->save();
        echo "✅ Campo revertido a null\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
