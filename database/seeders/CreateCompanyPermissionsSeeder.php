<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class CreateCompanyPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔐 Verificando y creando permisos de empresa...');

        $guard = 'api';
        
        // Permisos de empresa que necesitamos
        $companyPermissions = [
            'company.view' => 'Ver información de empresa',
            'company.edit' => 'Editar información de empresa', 
            'company.create' => 'Crear empresa',
            'company.delete' => 'Eliminar empresa'
        ];

        foreach ($companyPermissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guard]
            );
            
            $status = $permission->wasRecentlyCreated ? 'CREADO' : 'EXISTE';
            $this->command->info("   {$status}: {$name}");
        }

        // Asignar permisos al rol super-admin
        $superAdminRole = Role::where('name', 'super-admin')->where('guard_name', $guard)->first();
        
        if ($superAdminRole) {
            $this->command->info("\n🔗 Asignando permisos a rol super-admin...");
            
            foreach ($companyPermissions as $name => $description) {
                if (!$superAdminRole->hasPermissionTo($name)) {
                    $superAdminRole->givePermissionTo($name);
                    $this->command->info("   ✅ {$name} asignado");
                } else {
                    $this->command->info("   ℹ️  {$name} ya existe");
                }
            }
        } else {
            $this->command->error('❌ Rol super-admin no encontrado');
        }

        // También asignar a company-admin
        $companyAdminRole = Role::where('name', 'company-admin')->where('guard_name', $guard)->first();
        
        if ($companyAdminRole) {
            $this->command->info("\n🔗 Asignando permisos a rol company-admin...");
            
            $adminPermissions = ['company.view', 'company.edit'];
            foreach ($adminPermissions as $name) {
                if (!$companyAdminRole->hasPermissionTo($name)) {
                    $companyAdminRole->givePermissionTo($name);
                    $this->command->info("   ✅ {$name} asignado");
                } else {
                    $this->command->info("   ℹ️  {$name} ya existe");
                }
            }
        }

        $this->command->info("\n✅ Permisos de empresa configurados correctamente");
    }
}
