<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;

class SeedPermissions extends Command
{
    protected $signature = 'permission:seed';
    
    public function handle(): int
    {

        // Clear permission cache
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Seeding permissions from config/permission_groups.php');

        $permissionGroups = config('permission_groups');

        if (!$permissionGroups || !is_array($permissionGroups)) {
            $this->error('No permission groups found in config/permission_groups.php');
            return self::FAILURE;
        }

        $createdPermissions = [];

        foreach ($permissionGroups as $module => $actions) {
            foreach ($actions as $action) {
                $permission = "$action-$module";
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'api', // or 'web' if you use session auth
                ]);
                $createdPermissions[] = $permission;
            }
        }

        $this->info("Permissions created (" . count($createdPermissions) . "):");
        foreach ($createdPermissions as $perm) {
            $this->line("  - $perm");
        }

        // Create base roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'api']);
        $userRole  = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'api']);

        // Assign permissions
        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions(Permission::where('name', 'like', '%.view')->get());

        $this->newLine();
        $this->info('Admin role granted all permissions.');
        $this->info('User role granted only "view" permissions.');
        $this->newLine();
        $this->info('Permission seeding completed successfully.');

        return self::SUCCESS;
    }
}
