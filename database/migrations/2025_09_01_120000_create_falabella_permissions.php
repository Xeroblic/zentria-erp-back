<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear permisos para Falabella
        $permissions = [
            'falabella.view' => 'Ver datos de Falabella',
            'falabella.update' => 'Actualizar productos en Falabella',
            'falabella.export' => 'Exportar datos de Falabella',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'api'],
                ['description' => $description]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'LIKE', 'falabella.%')
            ->where('guard_name', 'api')
            ->delete();
    }
};
