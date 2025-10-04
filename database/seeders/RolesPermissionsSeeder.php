<?php
// database/seeders/RolesPermissionsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    private const GUARD = 'api';

    private const PERMISSIONS = [
        // Company
        'create-company', 'view-company', 'edit-company', 'delete-company',
        // Subsidiary
        'create-subsidiary','view-subsidiary','edit-subsidiary','delete-subsidiary',
        // Branch
        'create-branch','view-branch','edit-branch','delete-branch',
        // Users
        'invite-user','view-users','edit-users','delete-users',
        // Warehouse
        'view-warehouse','create-inspection',

        'users.index', 'users.store', 'users.update', 'users.destroy',
    ];


    private const ROLES = [
        'super-admin'        => 'ALL',
        'company-admin'      => [
            'create-subsidiary','view-subsidiary','edit-subsidiary','delete-subsidiary',
            'create-branch','view-branch','edit-branch','delete-branch',
            'invite-user','view-users','edit-users',
        ],
        'subsidiary-admin'   => [
            'create-branch','view-branch','edit-branch','delete-branch',
            'invite-user','view-users','edit-users',
        ],
        'branch-admin'       => [
            'view-branch','invite-user','view-users',
        ],
        'warehouse-employee' => [
            'view-warehouse','create-inspection',
        ],
    ];

    public function run(): void
    {
        /* -----------------------------------------------------------------
         | 0.  Reset cache de Spatie
         * ----------------------------------------------------------------*/
        App::make(PermissionRegistrar::class)->forgetCachedPermissions();

        /* -----------------------------------------------------------------
         | 1.  Permisos
         * ----------------------------------------------------------------*/
        foreach (self::PERMISSIONS as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm],
                ['guard_name' => self::GUARD]
            );
        }
        $all = Permission::where('guard_name', self::GUARD)->get();

        /* -----------------------------------------------------------------
         | 2.  Roles y sincronización
         * ----------------------------------------------------------------*/
        foreach (self::ROLES as $roleName => $permList) {
            /** @var Role $role */
            $role = Role::updateOrCreate(
                ['name' => $roleName],
                ['guard_name' => self::GUARD]          // fuerza el guard correcto
            );

            $perms = $permList === 'ALL'
                ? $all
                : Permission::whereIn('name', $permList)
                            ->where('guard_name', self::GUARD)
                            ->get();

            $role->syncPermissions($perms);
        }
    }
}
