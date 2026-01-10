<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Summary of PermissionSeeder
 * @author Tiago França
 * @copyright (c) 2026
 *
 * @suppress PHP0413
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        throw_unless(
            class_exists(Permission::class) && class_exists(Role::class),
            \Exception::class,
            'Spatie Permission package is not installed.'
        );

        $customerPermissions = [
            //
        ];

        $staffPermissions = [
            //
        ];

        $permissions = array_unique([
            // Put here all permissions

            'user.create',
            'user.view',
            'user.update',
            'user.delete',
            'user.list',

            // ...

            ...$customerPermissions,
            ...$staffPermissions,
        ]);

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Default roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // Admin has all permissions
        $admin->syncPermissions($permissions);

        $staff->syncPermissions($staffPermissions);
    }
}
