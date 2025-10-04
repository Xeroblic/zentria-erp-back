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

class SuperAdminSeeder extends Seeder
{
    private const GUARD = 'api';

    public function run(): void
    {
        // Buscar sucursal y empresa reales
        $branch = Branch::where('branch_name', 'Casa Matriz EcoPC')->first();
        
        if (!$branch) {
            $this->command->error('No se encontró la sucursal "Casa Matriz EcoPC". Ejecuta primero EcoTechSeeder.');
            return;
        }

        $company = $branch->subsidiary->company;

        // Crear o actualizar usuario superadmin
        $user = User::updateOrCreate(
            ['email' => 'rbarrientos@tikinet.cl'],
            [
                'first_name'       => 'Rodrigo',
                'middle_name'      => 'Mariano',
                'last_name'        => 'Barrientos',
                'second_last_name' => 'San Martin',
                'password'         => Hash::make('Hola2025!'),
                'address'          => 'Av. Libertador Bernardo OHiggins 1234',
                'position'         => 'CEO',
                'rut'              => '11111111-1',
                'phone_number'     => '999999999',
                'is_active'        => true,
                'primary_branch_id' => $branch->id,
            ]
        );

        // Crear rol super-admin si no existe
        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => self::GUARD,
        ]);

        // Obtener y asignar todos los permisos al rol y usuario
        $allPermissions = Permission::where('guard_name', self::GUARD)->get();
        $role->syncPermissions($allPermissions);
        $user->assignRole($role);
        $user->syncPermissions($allPermissions);

        // Usar el nuevo sistema multi-empresa
        $contextualService = new ContextualRoleService();
        
        // Asignar a la empresa como super-admin
        $contextualService->assignUserToCompany($user, $company, 'super-admin', 'CEO y Fundador');
        
        // Asignar a la sucursal principal
        $contextualService->assignUserToBranch($user, $branch, 'CEO', true);

        // Personalización global por defecto
        UserPersonalization::updateOrCreate(
            ['user_id' => $user->id],
            ['tema' => 1, 'font_size' => 14]
        );

        // Personalización específica para esta empresa
        UserCompanyPersonalization::updateOrCreate(
            ['user_id' => $user->id, 'company_id' => $company->id],
            [
                'tema' => 'dark',
                'font_size' => 16,
                'preferred_subsidiary_id' => $branch->subsidiary_id,
                'preferred_branch_id' => $branch->id,
                'language' => 'es',
                'sidebar_collapsed' => false,
                'dashboard_widgets' => [
                    'users_stats' => true,
                    'company_overview' => true,
                    'recent_activity' => true,
                    'financial_summary' => true,
                ]
            ]
        );

        $this->command->info("✅ Super admin creado con el nuevo sistema multi-empresa:");
        $this->command->info("   - Usuario: {$user->email}");
        $this->command->info("   - Empresa: {$company->company_name}");
        $this->command->info("   - Sucursal: {$branch->branch_name}");
        $this->command->info("   - Roles: super-admin global + company-admin contextual");
    }
}
