<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;

class FixUserCompanyAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔧 Verificando y corrigiendo asociaciones usuario-empresa...');

        // Verificar usuario principal de EcoTech
        $user = User::where('email', 'rbarrientos@tikinet.cl')->first();
        
        if (!$user) {
            $this->command->error('❌ Usuario rbarrientos@tikinet.cl no encontrado');
            return;
        }

        $this->command->info("👤 Usuario: {$user->first_name} {$user->last_name}");
        
        // Verificar empresas asociadas
        $companies = $user->companies;
        $this->command->info("🏢 Empresas asociadas: {$companies->count()}");
        
        if ($companies->count() > 0) {
            foreach ($companies as $company) {
                $this->command->info("   - {$company->company_name}");
            }
        } else {
            $this->command->warn('⚠️  Usuario no tiene empresas asociadas');
            
            // Asociar a EcoTech SPA
            $ecotech = Company::where('company_name', 'EcoTech SPA')->first();
            if ($ecotech) {
                $user->companies()->attach($ecotech->id);
                $this->command->info("✅ Usuario asociado a {$ecotech->company_name}");
            } else {
                $this->command->error('❌ EcoTech SPA no encontrada');
            }
        }

        // Verificar usuario de Digital Innovate
        $diUser = User::where('email', 'superadmin@digitalinnovate.cl')->first();
        if ($diUser) {
            $diCompanies = $diUser->companies;
            $this->command->info("\n👤 Usuario DI: {$diUser->first_name} {$diUser->last_name}");
            $this->command->info("🏢 Empresas asociadas: {$diCompanies->count()}");
            
            foreach ($diCompanies as $company) {
                $this->command->info("   - {$company->company_name}");
            }
        }

        $this->command->info("\n✅ Verificación completada");
    }
}
