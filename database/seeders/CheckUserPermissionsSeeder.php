<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class CheckUserPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔍 Verificando permisos del usuario...');

        $user = User::where('email', 'rbarrientos@tikinet.cl')->first();
        
        if (!$user) {
            $this->command->error('❌ Usuario no encontrado');
            return;
        }

        $this->command->info("👤 Usuario: {$user->email}");
        
        // Verificar roles
        $roles = $user->roles;
        $this->command->info("📋 Roles ({$roles->count()}):");
        foreach ($roles as $role) {
            $this->command->info("   - {$role->name}");
        }

        // Verificar permisos específicos
        $permissions = [
            'company.view',
            'company.edit', 
            'company.create',
            'company.delete'
        ];

        $this->command->info("\n🔐 Permisos de empresa:");
        foreach ($permissions as $permission) {
            $hasPermission = $user->hasPermissionTo($permission);
            $status = $hasPermission ? '✅' : '❌';
            $this->command->info("   {$status} {$permission}");
        }

        // Si no tiene permisos, asignar role super-admin
        if (!$user->hasPermissionTo('company.view')) {
            $this->command->warn('⚠️  Usuario no tiene permisos de empresa');
            
            if (!$user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
                $this->command->info('✅ Rol super-admin asignado');
            }
        }

        // Verificar después de asignar rol
        $this->command->info("\n🔄 Verificación post-asignación:");
        $hasPermission = $user->hasPermissionTo('company.view');
        $status = $hasPermission ? '✅' : '❌';
        $this->command->info("   {$status} company.view");
    }
}
