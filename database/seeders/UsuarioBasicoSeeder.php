<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Services\ContextualRoleService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsuarioBasicoSeeder extends Seeder
{
    public function run(): void
    {
        $contextualService = new ContextualRoleService();
        
        // Buscar sucursal real
        $branch = Branch::where('branch_name', 'Casa Matriz EcoPC')->first();

        if (!$branch) {
            $this->command->error('No se encontró la sucursal "Casa Matriz EcoPC". Ejecuta primero EcoTechSeeder.');
            return;
        }

        $company = $branch->subsidiary->company;


        $basicEmployee = User::firstOrCreate(
            ['email' => 'empleado@ecotech.cl'],
            [
                'first_name'       => 'Juan',
                'last_name'        => 'Pérez',
                'position'         => 'Vendedor',
                'rut'              => '22222222-2',
                'address'          => 'Calle Falsa 123',
                'commune_id'       => 13101, // Santiago
                'phone_number'     => '987654321',
                'password'         => bcrypt('12345678'),
                'is_active'        => true,
                'primary_branch_id' => $branch->id,
            ]
        );

        $contextualService->assignUserToCompany($basicEmployee, $company, 'employee', 'Vendedor');
        $contextualService->assignUserToBranch($basicEmployee, $branch, 'Vendedor', true);
        $basicEmployee->assignRole('employee');

        $branchAdmin = User::firstOrCreate(
            ['email' => 'admin.sucursal@ecotech.cl'],
            [
                'first_name'       => 'María',
                'last_name'        => 'González',
                'position'         => 'Administradora de Sucursal',
                'rut'              => '33333333-3',
                'address'          => 'Av. Principal 456',
                'commune_id'       => 13101, // Santiago
                'phone_number'     => '998877665',
                'password'         => bcrypt('12345678'),
                'is_active'        => true,
                'primary_branch_id' => $branch->id,
            ]
        );

        $contextualService->assignUserToCompany($branchAdmin, $company, 'manager', 'Administradora de Sucursal');
        $contextualService->assignUserToBranch($branchAdmin, $branch, 'Administradora', true);
        $contextualService->promoteToBranchAdmin($branchAdmin, $branch);
        $branchAdmin->assignRole('branch-admin');

        // 3. Técnico especializado
        $technician = User::firstOrCreate(
            ['email' => 'tecnico@ecotech.cl'],
            [
                'first_name'       => 'Carlos',
                'last_name'        => 'Rodríguez',
                'position'         => 'Técnico en Reparaciones',
                'rut'              => '44444444-4',
                'address'          => 'Los Aromos 789',
                'commune_id'       => 13101, // Santiago
                'phone_number'     => '955443322',
                'password'         => bcrypt('12345678'),
                'is_active'        => true,
                'primary_branch_id' => $branch->id,
            ]
        );

        $contextualService->assignUserToCompany($technician, $company, 'technician', 'Técnico en Reparaciones');
        $contextualService->assignUserToBranch($technician, $branch, 'Técnico', true);
        $technician->assignRole('technician');

        // 4. Empleado de bodega
        $warehouseEmployee = User::firstOrCreate(
            ['email' => 'bodega@ecotech.cl'],
            [
                'first_name'       => 'Luis',
                'last_name'        => 'Morales',
                'position'         => 'Encargado de Bodega',
                'rut'              => '55555555-5',
                'address'          => 'Santa Rosa 321',
                'commune_id'       => 13101, // Santiago
                'phone_number'     => '911223344',
                'password'         => bcrypt('12345678'),
                'is_active'        => true,
                'primary_branch_id' => $branch->id,
            ]
        );

        $contextualService->assignUserToCompany($warehouseEmployee, $company, 'warehouse-employee', 'Encargado de Bodega');
        $contextualService->assignUserToBranch($warehouseEmployee, $branch, 'Encargado de Bodega', true);
        $warehouseEmployee->assignRole('warehouse-employee');

        $this->command->info("✅ Usuarios básicos creados:");
        $this->command->info("   - Empleado básico: empleado@ecotech.cl");
        $this->command->info("   - Admin de sucursal: admin.sucursal@ecotech.cl");
        $this->command->info("   - Técnico: tecnico@ecotech.cl");
        $this->command->info("   - Bodeguero: bodega@ecotech.cl");
        $this->command->info("   - Todos asignados a: {$company->company_name} -> {$branch->branch_name}");
    }
}
