<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    public function run(): void
    {{
        DB::table('regions')->truncate();
        DB::table('regions')->insert(
            [
                ['id' => 1, 'name' => 'Tarapacá', 'ordinal' => 'I', 'geographic_order' => 2],
                ['id' => 2, 'name' => 'Antofagasta', 'ordinal' => 'II', 'geographic_order' => 3],
                ['id' => 3, 'name' => 'Atacama', 'ordinal' => 'III', 'geographic_order' => 4],
                ['id' => 4, 'name' => 'Coquimbo', 'ordinal' => 'IV', 'geographic_order' => 5],
                ['id' => 5, 'name' => 'Valparaiso', 'ordinal' => 'V', 'geographic_order' => 6],
                ['id' => 6, 'name' => 'O\'Higgins', 'ordinal' => 'VI', 'geographic_order' => 8],
                ['id' => 7, 'name' => 'Maule', 'ordinal' => 'VII', 'geographic_order' => 9],
                ['id' => 8, 'name' => 'Biobío', 'ordinal' => 'VIII', 'geographic_order' => 11],
                ['id' => 9, 'name' => 'La Araucanía', 'ordinal' => 'IX', 'geographic_order' => 12],
                ['id' => 10, 'name' => 'Los Lagos', 'ordinal' => 'X', 'geographic_order' => 14],
                ['id' => 11, 'name' => 'Aysén del General Carlos Ibáñez del Campo', 'ordinal' => 'XI', 'geographic_order' => 15],
                ['id' => 12, 'name' => 'Magallanes y de la Antártica Chilena', 'ordinal' => 'XII', 'geographic_order' => 16],
                ['id' => 13, 'name' => 'Metropolitana de Santiago', 'ordinal' => 'RM', 'geographic_order' => 7],
                ['id' => 14, 'name' => 'Los Ríos', 'ordinal' => 'XIV', 'geographic_order' => 13],
                ['id' => 15, 'name' => 'Arica y Parinacota', 'ordinal' => 'XV', 'geographic_order' => 1],
                ['id' => 16, 'name' => 'Ñuble', 'ordinal' => 'XVI', 'geographic_order' => 10],
            ]
        );

        $this->command->info("✅ Regiones insertadas correctamente.");

        $this->command->info("Todas las regiones de chile han sido insertadas en la base de datos. (Actualizado a 16/10/2025)");
    }}
}
