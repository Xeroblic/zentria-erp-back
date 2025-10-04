<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\UserPersonalization;
use App\Models\UserCompanyPersonalization;
use App\Services\ContextualRoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DigitalInnovateSuperAdminSeeder extends Seeder
{
    private const GUARD = 'api';

    public function run(): void
    {
        $this->command->info('🚀 Creando Super Admin para Digital Innovate SPA...');

        // Buscar la empresa Digital Innovate
        $company = Company::where('company_name', 'Digital Innovate SpA')->first();
        
        if (!$company) {
            $this->command->error('❌ No se encontró la empresa "Digital Innovate SpA". Ejecuta primero MultiCompanyExampleSeeder.');
            return;
        }

        // Buscar la sucursal Casa Matriz de Digital Innovate
        $branch = Branch::where('branch_name', 'Casa Matriz Digital Innovate')->first();
        
        if (!$branch) {
            $this->command->error('❌ No se encontró la sucursal "Casa Matriz Digital Innovate". Ejecuta primero MultiCompanyExampleSeeder.');
            return;
        }

        $this->command->info("Empresa encontrada: {$company->company_name} (ID: {$company->id})");
        $this->command->info("Sucursal encontrada: {$branch->branch_name} (ID: {$branch->id})");

        // Crear o actualizar usuario super admin para Digital Innovate
        $user = User::updateOrCreate(
            ['email' => 'superadmin@digitalinnovate.cl'],
            [
                'first_name'       => 'María',
                'middle_name'      => 'Carmen',
                'last_name'        => 'González',
                'second_last_name' => 'Rodríguez',
                'password'         => Hash::make('DigitalInnovate2025!'),
                'address'          => 'Av. Providencia 2000, Santiago',
                'position'         => 'Super Administradora',
                'rut'              => '98765432-1',
                'phone_number'     => '+56999887766',
                'is_active'        => true,
                'primary_branch_id' => $branch->id,
            ]
        );

        $this->command->info("Usuario creado/actualizado: {$user->first_name} {$user->last_name} ({$user->email})");

        // Crear rol super-admin si no existe
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => self::GUARD],
            ['description' => 'Super Administrador con acceso completo al sistema']
        );

        // Asignar rol super-admin al usuario
        if (!$user->hasRole('super-admin', self::GUARD)) {
            $user->assignRole($superAdminRole);
            $this->command->info("Rol 'super-admin' asignado a {$user->email}");
        } else {
            $this->command->info("El usuario {$user->email} ya tiene rol 'super-admin'");
        }

        // Usar el servicio contextual para asociar a la empresa
        $contextualService = new ContextualRoleService();
        
        // Asignar usuario a la empresa Digital Innovate como company-admin también
        try {
            $contextualService->assignUserToCompany($user, $company, 'company-admin', 'Super Administradora');
            $this->command->info("Usuario asignado a empresa: {$company->company_name}");
        } catch (\Exception $e) {
            $this->command->warn("Error al asignar usuario a empresa: " . $e->getMessage());
        }

        // Asignar usuario a la sucursal como manager
        try {
            $contextualService->assignUserToBranch($user, $branch, 'Super Administradora', true);
            $this->command->info("Usuario asignado a sucursal: {$branch->branch_name}");
        } catch (\Exception $e) {
            $this->command->warn("Error al asignar usuario a sucursal: " . $e->getMessage());
        }

        // Crear personalización básica para el usuario
        UserPersonalization::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tema' => 1, // Tema oscuro (valor numérico)
                'font_size' => 14,
                'sucursal_principal' => $branch->id,
                'company_id' => $company->id,
                'tcolor' => '#1E40AF', // Azul corporativo
                'tcolor_int' => '7' // Intensidad azul (como string según migración)
            ]
        );

        // Crear personalización específica para la empresa
        UserCompanyPersonalization::updateOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => $company->id
            ],
            [
                'tema' => 'dark',
                'font_size' => 14,
                'preferred_subsidiary_id' => $branch->subsidiary->id,
                'preferred_branch_id' => $branch->id,
                'language' => 'es',
                'sidebar_collapsed' => false,
                'dashboard_widgets' => json_encode([
                    ['name' => 'empresa-stats', 'position' => 1],
                    ['name' => 'usuarios-activos', 'position' => 2],
                    ['name' => 'notificaciones', 'position' => 3]
                ])
            ]
        );

        $this->command->info("Personalización creada para {$user->email}");

        $this->command->info('');
        $this->command->info('¡Super Admin para Digital Innovate SPA creado exitosamente!');
        $this->command->info('');
        $this->command->info('Credenciales de acceso:');
        $this->command->info("   Email: superadmin@digitalinnovate.cl");
        $this->command->info("   Password: DigitalInnovate2025!");
        $this->command->info('');
        $this->command->info('Empresa: Digital Innovate SpA');
        $this->command->info('Sucursal principal: Casa Matriz Digital Innovate');
        $this->command->info('Rol: super-admin');
        $this->command->info('');
    }
}
