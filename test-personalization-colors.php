<?php

require __DIR__ . '/vendor/autoload.php';

// Configurar entorno
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 VERIFICANDO COLUMNAS DE USER_PERSONALIZATIONS\n";
echo "==============================================\n\n";

try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('user_personalizations');
    
    echo "✅ Columnas encontradas:\n";
    foreach ($columns as $column) {
        echo "   - {$column}\n";
    }
    
    echo "\n🎨 Verificando columnas de color específicamente:\n";
    echo "   - tcolor: " . (in_array('tcolor', $columns) ? 'EXISTE ✅' : 'NO EXISTE ❌') . "\n";
    echo "   - tcolor_int: " . (in_array('tcolor_int', $columns) ? 'EXISTE ✅' : 'NO EXISTE ❌') . "\n";
    
    echo "\n🧪 Probando creación de personalización con colores:\n";
    
    // Obtener primer usuario
    $user = \App\Models\User::first();
    if ($user) {
        $personalization = $user->personalization()->firstOrCreate([]);
        $personalization->tcolor = 'emerald';
        $personalization->tcolor_int = '500';
        $personalization->save();
        
        echo "   ✅ Personalización guardada correctamente para usuario: {$user->email}\n";
        echo "   🎨 Color: {$personalization->tcolor}\n";
        echo "   🔢 Intensidad: {$personalization->tcolor_int}\n";
    } else {
        echo "   ⚠️  No hay usuarios en la base de datos\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
