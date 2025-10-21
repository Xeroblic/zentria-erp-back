<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Subsidiary;
use App\Models\Branch;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        // Empresa
        $empresa = Company::firstOrCreate(
            ['company_rut' => '76795560-9'],
            [
                'company_name'        => 'EcoTech SPA',
                'legal_name'          => 'EcoTech Soluciones Tecnológicas SpA',
                'business_activity'   => 'Soluciones tecnológicas y servicios informáticos',
                'company_address'     => 'Av. Andrés Bello 2457, Providencia, Santiago',
                'commune_id'          => 13123, // Providencia
                'company_phone'       => '+56 9 1234 5678',
                'contact_email'       => 'contacto@ecotech.cl',
                'company_website'     => 'https://ecotech.cl',
                'representative_name' => 'Rodrigo Barrientos',
                'company_logo'        => null,
                'company_type'        => 'SPA',
                'is_active'           => true,
            ]
        );

        $this->command->info("🏢 Empresa creada: {$empresa->company_name}");

        // Subempresa 1
        $sub1 = Subsidiary::firstOrCreate(
            ['company_id' => $empresa->id, 'subsidiary_rut' => '76650123-1'],
            [
                'subsidiary_name'             => 'EcoPC',
                'subsidiary_address'          => 'Nueva Providencia 1363, Santiago',
                'commune_id'                  => 13123, // Providencia
                'subsidiary_phone'            => '+56 2 2345 6789',
                'subsidiary_email'            => 'ventas@ecopc.cl',
                'subsidiary_website'          => 'https://ecopc.cl',
                'subsidiary_manager_name'     => 'Claudia Gómez',
                'subsidiary_manager_email'    => 'claudia@ecopc.cl',
                'subsidiary_manager_phone'    => '+56 9 9876 5432',
                'subsidiary_created_at'       => now(),
                'subsidiary_status'           => true,
            ]
        );

        // Subempresa 2
        $sub2 = Subsidiary::firstOrCreate(
            ['company_id' => $empresa->id, 'subsidiary_rut' => '76543218-2'],
            [
                'subsidiary_name'             => 'EcoTI',
                'subsidiary_address'          => 'Manuel Montt 201, Providencia',
                'commune_id'                  => 13123, // Providencia
                'subsidiary_phone'            => '+56 2 2789 1234',
                'subsidiary_email'            => 'soporte@ecoti.cl',
                'subsidiary_website'          => 'https://ecoti.cl',
                'subsidiary_manager_name'     => 'Felipe Ríos',
                'subsidiary_manager_email'    => 'felipe@ecoti.cl',
                'subsidiary_manager_phone'    => '+56 9 8765 4321',
                'subsidiary_created_at'       => now(),
                'subsidiary_status'           => true,
            ]
        );

        // Subempresa 3
        $sub3 = Subsidiary::firstOrCreate(
            ['company_id' => $empresa->id, 'subsidiary_rut' => '76549876-5'],
            [
                'subsidiary_name'             => 'RentaPC',
                'subsidiary_address'          => 'Eliodoro Yáñez 1747, Providencia',
                'commune_id'                  => 13123, // Providencia
                'subsidiary_phone'            => '+56 2 2456 7890',
                'subsidiary_email'            => 'contacto@rentapc.cl',
                'subsidiary_website'          => 'https://rentapc.cl',
                'subsidiary_manager_name'     => 'Javiera Torres',
                'subsidiary_manager_email'    => 'javiera@rentapc.cl',
                'subsidiary_manager_phone'    => '+56 9 7766 5544',
                'subsidiary_created_at'       => now(),
                'subsidiary_status'           => true,
            ]
        );

        $this->crearSucursal($sub1, 'Casa Matriz EcoPC', 'Av. Santa Rosa 1234, Santiago Centro', 13101);     // Santiago
        $this->crearSucursal($sub1, 'EcoPC Ñuñoa', 'Pedro de Valdivia 303, Ñuñoa', 13120);                   // Ñuñoa
        $this->crearSucursal($sub2, 'Laboratorio EcoTI', 'Av. Providencia 2222, Providencia', 13123);        // Providencia
        $this->crearSucursal($sub3, 'Oficina RentaPC', 'Av. Vicuña Mackenna 1001, San Joaquín', 13129);      // San Joaquín

        $this->command->info("✅ Empresa + Subempresas + Sucursales creadas correctamente.");
    }

    private function crearSucursal(Subsidiary $sub, string $nombre, string $direccion, ?int $communeId = null)
    {
        Branch::firstOrCreate(
            ['subsidiary_id' => $sub->id, 'branch_name' => $nombre],
            [
                'branch_address'        => $direccion,
                'commune_id'            => $communeId,
                'branch_phone'          => '+56 2 2000 0000',
                'branch_email'          => strtolower(str_replace(' ', '', $nombre)) . '@ecotech.cl',
                'branch_created_at'     => now(),
                'branch_status'         => true,
                'branch_manager_name'   => 'Administrador Local',
                'branch_manager_phone'  => '+56 9 1111 2222',
                'branch_manager_email'  => 'admin@ecotech.cl',
                'branch_opening_hours'  => 'Lunes a Viernes 09:00 - 18:00',
                'branch_location'       => null,
            ]
        );

        $this->command->line("🏣 Sucursal creada: {$nombre}");
    }
}
