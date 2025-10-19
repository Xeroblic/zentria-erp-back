<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ScopeRole;
use App\Models\Subsidiary;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AccessContextDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar que existan los roles de acceso (no administrativos)
        Role::findOrCreate('company-member', 'api');
        Role::findOrCreate('subsidiary-member', 'api');
        // Permiso global no es necesario para lectura contextual; no lo asignamos

        // 1) Usuario empleado@ecotech.cl → acceso a 1 subsidiary
        $empleado = User::where('email', 'empleado@ecotech.cl')->first();
        $firstSubsidiary = Subsidiary::query()->first();
        if ($empleado && $firstSubsidiary) {
            ScopeRole::assignContextRole($empleado->id, 'subsidiary-member', 'subsidiary', $firstSubsidiary->id);
            $this->command?->info("[OK] 'empleado@ecotech.cl' ahora tiene acceso a la subsidiary ID {$firstSubsidiary->id}");
        } else {
            $this->command?->warn("[SKIP] No se pudo asignar subsidiary a empleado@ecotech.cl (usuario o subsidiary no encontrados)");
        }

        // 2) Usuario tecnico@ecotech.cl → acceso directo a 2 branches
        $tecnico = User::where('email', 'tecnico@ecotech.cl')->first();
        $branches = Branch::query()->limit(2)->pluck('id')->all();
        if ($tecnico && count($branches) >= 2) {
            $attach = [];
            foreach ($branches as $bid) { $attach[$bid] = ['is_primary' => false, 'position' => null]; }
            $tecnico->branches()->syncWithoutDetaching($attach);
            $this->command?->info("[OK] 'tecnico@ecotech.cl' ahora tiene acceso directo a branches: " . implode(',', $branches));
        } else {
            $this->command?->warn("[SKIP] No se pudo asignar branches a tecnico@ecotech.cl (usuario o branches insuficientes)");
        }

        // 3) Usuario bodega@ecotech.cl → acceso a 2 subsidiaries
        $bodega = User::where('email', 'bodega@ecotech.cl')->first();
        $twoSubs = Subsidiary::query()->limit(2)->pluck('id')->all();
        if ($bodega && count($twoSubs) >= 2) {
            foreach ($twoSubs as $sid) {
                ScopeRole::assignContextRole($bodega->id, 'subsidiary-member', 'subsidiary', $sid);
            }
            $this->command?->info("[OK] 'bodega@ecotech.cl' ahora tiene acceso a subsidiaries: " . implode(',', $twoSubs));
        } else {
            $this->command?->warn("[SKIP] No se pudo asignar subsidiaries a bodega@ecotech.cl (usuario o subsidiaries insuficientes)");
        }
    }
}
