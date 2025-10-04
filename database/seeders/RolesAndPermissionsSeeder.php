<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cache de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===== 1) Cargar permisos desde config (asi evitamos duplicados) =====
        $groups = config('permission_groups', []);
        if (!is_array($groups) || empty($groups)) {
            $this->command->error('permission_groups.php vacío o mal formateado.');
            return;
        }

        // Reunir todos los permisos uinicos de todos los grupos
        $allPermissions = collect($groups)->flatten()->unique()->values()->toArray();

        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api',
            ]);
        }

        // Helper para leer los gruposdd
        $G = fn(string $group) => $groups[$group] ?? [];

        // ===== 2) Como declarar ROLES =====
        // Sintaxis de cada item:
        //  - '*'                      => todos los permisos
        //  - '@grupo'                 => todo el grupo del configuracion
        //  - ['grupo' => ['perm1']]   => subset del grupo
        //  - 'permiso-suelto'         => fueraa de grupos
        $ROLE_MATRIX = [
            'super-admin' => ['*'],

            'company-admin' => [
                '@user', '@company', '@subsidiary', '@branch', '@payslip', '@report',
                'manage-roles', // extras fuera de grupos
            ],

            'subsidiary-admin' => [
                '@user', '@subsidiary', '@branch', '@payslip', '@report',
            ],

            'branch-admin' => [
                '@user', '@branch', '@payslip', '@report',
            ],

            'manager' => [
                ['user'    => ['view-user', 'edit-user']],
                ['branch'  => ['view-branch']],
                ['payslip' => ['view-payslips', 'edit-payslips']],
                ['report'  => ['view-reports']],
            ],

            'employee' => [
                ['user'    => ['view-user']],
                ['branch'  => ['view-branch']],
                ['payslip' => ['view-payslips']],
            ],

            'technician' => [
                ['user'    => ['view-user']],
                ['branch'  => ['view-branch']],
                ['payslip' => ['view-payslips']],
            ],

            'warehouse-employee' => [
                ['user'    => ['view-user']],
                ['branch'  => ['view-branch']],
                ['payslip' => ['view-payslips']],
            ],
        ];

        // ===== 3) Resolver matriz → lista final de permisos por rol =====
        $resolveSpec = function ($spec) use ($G, $groups, $allPermissions) {
            // '*' => todos
            if ($spec === '*') {
                return $allPermissions;
            }

            // '@grupo' => todo el grupo
            if (is_string($spec) && str_starts_with($spec, '@')) {
                $group = substr($spec, 1);
                if (!array_key_exists($group, $groups)) {
                    $this->command->warn("Grupo '@{$group}' no existe en permission_groups.php");
                    return [];
                }
                return $G($group);
            }

            // ['grupo' => ['perm1','perm2']]
            if (is_array($spec)) {
                $expanded = [];
                foreach ($spec as $group => $subset) {
                    if (!array_key_exists($group, $groups)) {
                        $this->command->warn("Grupo '{$group}' no existe en permission_groups.php");
                        continue;
                    }
                    // Filtrar solo los que existen dentro del grupo
                    $valid = array_values(array_intersect($G($group), (array) $subset));
                    $missing = array_diff((array) $subset, $valid);
                    if (!empty($missing)) {
                        $this->command->warn("Permisos no encontrados en grupo '{$group}': ".implode(', ', $missing));
                    }
                    $expanded = array_merge($expanded, $valid);
                }
                return $expanded;
            }

            // 'permiso-suelto'
            if (is_string($spec)) {
                if (!in_array($spec, $allPermissions, true)) {
                    $this->command->warn("Permiso suelto '{$spec}' no existe en permission_groups.php");
                    return [];
                }
                return [$spec];
            }

            // Desconocido
            $this->command->warn('Especificación de rol desconocida: '.json_encode($spec));
            return [];
        };

        $expandRole = function (array $roleSpec) use ($resolveSpec) {
            $perms = [];
            foreach ($roleSpec as $spec) {
                $perms = array_merge($perms, $resolveSpec($spec));
            }
            // unique y ordenado
            $perms = array_values(array_unique($perms));
            return $perms;
        };

        // ===== 4) Crear roles y sincronizar permisos =====
        foreach ($ROLE_MATRIX as $roleName => $roleSpec) {
            /** @var \Spatie\Permission\Models\Role $role */
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

            $perms = is_array($roleSpec) ? $expandRole($roleSpec) : [];
            $role->syncPermissions($perms); // deja el rol exactamente con estos permisos

            $this->command->info(sprintf(
                "Rol %-20s → %d permisos",
                $roleName,
                count($perms)
            ));
        }

        $this->command->info('✅ Roles y permisos creados/actualizados desde permission_groups.php');
    }
}
