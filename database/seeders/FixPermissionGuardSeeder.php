<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixPermissionGuardSeeder extends Seeder
{
    public function run()
    {
        $permissions = collect();

        foreach (config('permission_groups') as $group => $perms) {
            foreach ($perms as $permission) {
                $permissions->push(Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'api',
                ]));
            }
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'api']);
        $userRole  = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'api']);

        $adminRole->syncPermissions($permissions);

        $viewPermissions = $permissions->filter(fn($perm) => str_starts_with($perm->name, 'view-'));
        $userRole->syncPermissions($viewPermissions);

        $this->command->info('Permissions seeded and roles updated.');
    }

}
