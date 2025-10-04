<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Orden importante: primero roles y permisos
            RolesAndPermissionsSeeder::class,
            FixPermissionGuardSeeder::class,
            
            // Luego estructura empresarial
            EmpresaSeeder::class,
            
            // Usuarios principales
            SuperAdminSeeder::class,
            UsuarioBasicoSeeder::class,
            
            // Datos de ejemplo multi-empresa
            MultiCompanyExampleSeeder::class,
        ]);
    }

}
