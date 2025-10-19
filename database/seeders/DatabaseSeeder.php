<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Migraciones de todas las comunas y regiones de chile -> esto no debería cambiar. Pero si se necesitan cambios aplicar al seeder para mantener
            // persistencia de datos
            RegionSeeder::class,
            ProvinceSeeder::class,
            CommuneSeeder::class,

            // Orden importante: primero roles y permisos
            RolesAndPermissionsSeeder::class,
            FixPermissionGuardSeeder::class,
            
            // Luego estructura empresarial
            EmpresaSeeder::class,
            
            // Usuarios principales -> se debe aplicar cambios de contraseñas inmediatos
            // o eliminarlos eventualmente
            SuperAdminSeeder::class,
            UsuarioBasicoSeeder::class,
            
            // Datos de ejemplo multi-empresa
            MultiCompanyExampleSeeder::class,
            
            // Datos de ejemplo catálogo - productos, marcas, categorías
            DemoCatalogSeeder::class,
        ]);
    }

}
