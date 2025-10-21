<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Subsidiary;
use App\Models\UserPersonalization;
use App\Models\UserCompanyPersonalization;
use App\Services\ContextualRoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MultiCompanyExampleSeeder extends Seeder
{
    public function run(): void
    {
        $contextualService = new ContextualRoleService();

        // Obtener empresas existentes
        $ecotech = Company::where('company_name', 'LIKE', '%EcoTech%')->first();
        if (!$ecotech) {
            $this->command->error('No se encontró EcoTech. Ejecuta primero EcoTechSeeder.');
            return;
        }

        // Crear segunda empresa
        $digitalInnovate = Company::firstOrCreate([
            'company_name' => 'Digital Innovate SpA',
            'company_rut' => '88.888.888-8',
            'legal_name' => 'Digital Innovate Servicios Tecnológicos SpA',
            'company_website' => 'https://digitalinnovate.cl',
            'company_phone' => '+56-2-9999-8888',
            'representative_name' => 'María González',
            'contact_email' => 'contacto@digitalinnovate.cl',
            'company_address' => 'Av. Providencia 2000, Santiago',
            'commune_id' => 13123, // Providencia
            'business_activity' => 'Desarrollo de software y consultoría tecnológica',
            'company_type' => 'SpA',
            'is_active' => true,
        ]);

        // Crear subsidiaria para Digital Innovate
        $diSubsidiary = Subsidiary::firstOrCreate([
            'company_id' => $digitalInnovate->id,
            'subsidiary_name' => 'Digital Innovate Centro',
        ], [
            'subsidiary_rut' => '99.999.999-9',
            'subsidiary_website' => 'https://centro.digitalinnovate.cl',
            'subsidiary_phone' => '+56-2-9999-8888',
            'subsidiary_address' => 'Av. Providencia 2000, Santiago',
            'commune_id' => 13123, // Providencia
            'subsidiary_email' => 'centro@digitalinnovate.cl',
            'subsidiary_manager_name' => 'Pedro Morales',
            'subsidiary_manager_phone' => '+56-9-8888-9999',
            'subsidiary_manager_email' => 'pedro.morales@digitalinnovate.cl',
            'subsidiary_status' => true,
        ]);

        // Crear sucursales para Digital Innovate
        $diHqBranch = Branch::firstOrCreate([
            'subsidiary_id' => $diSubsidiary->id,
            'branch_name' => 'Casa Matriz Digital Innovate',
        ], [
            'branch_address' => 'Av. Providencia 2000, Santiago',
            'commune_id' => 13123, // Providencia
            'branch_phone' => '+56-2-9999-8888',
            'branch_email' => 'hq@digitalinnovate.cl',
            'branch_status' => true,
            'branch_manager_name' => 'Ana Silva',
            'branch_manager_phone' => '+56-9-7777-6666',
            'branch_manager_email' => 'ana.silva@digitalinnovate.cl',
        ]);

        $diDevBranch = Branch::firstOrCreate([
            'subsidiary_id' => $diSubsidiary->id,
            'branch_name' => 'Centro de Desarrollo DI',
        ], [
            'branch_address' => 'Las Condes 1500, Santiago',
            'commune_id' => 13114, // Las Condes
            'branch_phone' => '+56-2-9999-8889',
            'branch_email' => 'dev@digitalinnovate.cl',
            'branch_status' => true,
            'branch_manager_name' => 'José Ramirez',
            'branch_manager_phone' => '+56-9-6666-5555',
            'branch_manager_email' => 'jose.ramirez@digitalinnovate.cl',
        ]);

        // === CREAR USUARIOS DE EJEMPLO ===

        // 1. CEO Multi-empresa (trabaja en ambas empresas)
        $ceoUser = User::firstOrCreate([
            'email' => 'ceo@multiempresa.cl'
        ], [
            'first_name' => 'Carlos',
            'middle_name' => 'Eduardo',
            'last_name' => 'Martínez',
            'second_last_name' => 'López',
            'password' => Hash::make('password123'),
            'position' => 'CEO Ejecutivo',
            'rut' => '22.222.222-2',
            'phone_number' => '+56-9-8888-7777',
            'address' => 'Vitacura 3000, Santiago',
            'commune_id' => 13132, // Vitacura
            'is_active' => true,
            'primary_branch_id' => $diHqBranch->id,
        ]);

        // Asignar CEO a ambas empresas
        $contextualService->assignUserToCompany($ceoUser, $ecotech, 'company-admin', 'CEO Ejecutivo');
        $contextualService->assignUserToCompany($ceoUser, $digitalInnovate, 'company-admin', 'CEO y Fundador');

        // Asignar a sucursales
        $ecoHq = Branch::where('branch_name', 'LIKE', '%Casa Matriz EcoPC%')->first();
        if ($ecoHq) {
            $contextualService->assignUserToBranch($ceoUser, $ecoHq, 'CEO');
        }
        $contextualService->assignUserToBranch($ceoUser, $diHqBranch, 'CEO', true);

        // 2. Administrador solo de Digital Innovate
        $diAdmin = User::firstOrCreate([
            'email' => 'admin@digitalinnovate.cl'
        ], [
            'first_name' => 'Ana',
            'last_name' => 'Silva',
            'password' => Hash::make('password123'),
            'position' => 'Administradora General',
            'rut' => '33.333.333-3',
            'phone_number' => '+56-9-7777-6666',
            'commune_id' => 13123, // Providencia (por sucursal HQ)
            'is_active' => true,
            'primary_branch_id' => $diHqBranch->id,
        ]);

        $contextualService->assignUserToCompany($diAdmin, $digitalInnovate, 'company-admin', 'Administradora General');
        $contextualService->assignUserToBranch($diAdmin, $diHqBranch, 'Administradora', true);

        // 3. Desarrollador Senior (Solo Digital Innovate - Centro de Desarrollo)
        $developer = User::firstOrCreate([
            'email' => 'dev.senior@digitalinnovate.cl'
        ], [
            'first_name' => 'José',
            'last_name' => 'Ramirez',
            'password' => Hash::make('password123'),
            'position' => 'Desarrollador Senior',
            'rut' => '44.444.444-4',
            'phone_number' => '+56-9-6666-5555',
            'commune_id' => 13114, // Las Condes (por sucursal dev)
            'is_active' => true,
            'primary_branch_id' => $diDevBranch->id,
        ]);

        $contextualService->assignUserToCompany($developer, $digitalInnovate, 'employee', 'Desarrollador Senior');
        $contextualService->assignUserToBranch($developer, $diDevBranch, 'Desarrollador Senior', true);
        $contextualService->promoteToBranchAdmin($developer, $diDevBranch);

        // 4. Manager que maneja sucursal de desarrollo
        $devManager = User::firstOrCreate([
            'email' => 'manager.dev@digitalinnovate.cl'
        ], [
            'first_name' => 'Laura',
            'last_name' => 'Fernández',
            'password' => Hash::make('password123'),
            'position' => 'Gerente de Desarrollo',
            'rut' => '55.555.555-5',
            'phone_number' => '+56-9-5555-4444',
            'commune_id' => 13114, // Las Condes (por sucursal dev)
            'is_active' => true,
            'primary_branch_id' => $diDevBranch->id,
        ]);

        $contextualService->assignUserToCompany($devManager, $digitalInnovate, 'manager', 'Gerente de Desarrollo');
        $contextualService->assignUserToBranch($devManager, $diDevBranch, 'Gerente', true);
        $contextualService->promoteToSubsidiaryAdmin($devManager, $diSubsidiary);

        // === CREAR PERSONALIZACIONES ===
        
        // Personalización CEO para EcoTech
        UserCompanyPersonalization::firstOrCreate([
            'user_id' => $ceoUser->id,
            'company_id' => $ecotech->id,
        ], [
            'tema' => 'light',
            'font_size' => 14,
            'preferred_subsidiary_id' => $ecoHq ? $ecoHq->subsidiary_id : null,
            'preferred_branch_id' => $ecoHq ? $ecoHq->id : null,
            'language' => 'es',
            'dashboard_widgets' => [
                'financial_summary' => true,
                'users_stats' => true,
                'company_overview' => true,
            ]
        ]);

        // Personalización CEO para Digital Innovate
        UserCompanyPersonalization::firstOrCreate([
            'user_id' => $ceoUser->id,
            'company_id' => $digitalInnovate->id,
        ], [
            'tema' => 'dark',
            'font_size' => 16,
            'preferred_subsidiary_id' => $diSubsidiary->id,
            'preferred_branch_id' => $diHqBranch->id,
            'language' => 'es',
            'dashboard_widgets' => [
                'financial_summary' => true,
                'users_stats' => true,
                'company_overview' => true,
                'recent_activity' => true,
                'development_metrics' => true,
            ]
        ]);

        // Personalización para admin DI
        UserCompanyPersonalization::firstOrCreate([
            'user_id' => $diAdmin->id,
            'company_id' => $digitalInnovate->id,
        ], [
            'tema' => 'light',
            'font_size' => 14,
            'preferred_subsidiary_id' => $diSubsidiary->id,
            'preferred_branch_id' => $diHqBranch->id,
            'language' => 'es',
        ]);

        $this->command->info("✅ Seeder multi-empresa completado:");
        $this->command->info("   - Empresa creada: {$digitalInnovate->company_name}");
        $this->command->info("   - Usuarios multi-empresa: 1 CEO");
        $this->command->info("   - Usuarios especializados: 3");
        $this->command->info("   - Personalizaciones por empresa: configuradas");
    }
}
