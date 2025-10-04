<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupMultiCompanySystem extends Command
{
    protected $signature = 'setup:multi-company 
                            {--fresh : Execute fresh migration}
                            {--seed : Execute seeders}';

    protected $description = 'Setup the multi-company hierarchical system';

    public function handle()
    {
        $this->info('🚀 Configurando sistema multi-empresa jerárquico...');
        
        if ($this->option('fresh')) {
            $this->info('🔄 Ejecutando migración fresca...');
            Artisan::call('migrate:fresh', [], $this->getOutput());
        }

        if ($this->option('seed')) {
            $this->info('🌱 Ejecutando seeders...');
            
            $seeders = [
                'RolesAndPermissionsSeeder',
                'FixPermissionGuardSeeder', 
                'EmpresaSeeder',
                'SuperAdminSeeder',
                'UsuarioBasicoSeeder',
                'MultiCompanyExampleSeeder',
            ];

            foreach ($seeders as $seeder) {
                $this->info("   Ejecutando {$seeder}...");
                try {
                    Artisan::call('db:seed', ['--class' => $seeder], $this->getOutput());
                    $this->info("   ✅ {$seeder} completado");
                } catch (\Exception $e) {
                    $this->error("   ❌ Error en {$seeder}: " . $e->getMessage());
                }
            }
        }

        $this->info('🎉 Sistema multi-empresa configurado exitosamente!');
        $this->info('');
        $this->info('👥 Usuarios de prueba creados:');
        $this->table(
            ['Email', 'Contraseña', 'Rol', 'Empresa'],
            [
                ['rbarrientos@tikinet.cl', 'Hola2025!', 'Super Admin', 'Todas'],
                ['ceo@multiempresa.cl', 'password123', 'CEO Multi-empresa', 'EcoTech + Digital Innovate'],
                ['admin@digitalinnovate.cl', 'password123', 'Company Admin', 'Digital Innovate'],
                ['empleado@ecotech.cl', '12345678', 'Empleado', 'EcoTech'],
                ['admin.sucursal@ecotech.cl', '12345678', 'Branch Admin', 'EcoTech'],
            ]
        );
        
        $this->info('');
        $this->info('🏢 Funcionalidades implementadas:');
        $this->line('  ✅ Usuarios multi-empresa');
        $this->line('  ✅ Roles contextuales por empresa/subsidiaria/sucursal');
        $this->line('  ✅ Personalización por empresa');
        $this->line('  ✅ Control jerárquico de acceso');
        $this->line('  ✅ API para cambio de empresa activa');
        
        return Command::SUCCESS;
    }
}
