<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roleNames = ['admin', 'manager', 'finance', 'cashier'];
        foreach ($roleNames as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $adminPermissions = [
            'admin.dashboard.view',
            'admin.outlets.view',
            'admin.outlets.manage',
            'admin.tables.view',
            'admin.tables.manage',
            'admin.menu.view',
            'admin.menu.manage',
            'admin.users.view',
            'admin.users.manage',
            'admin.content.view',
            'admin.content.manage',
            'admin.configuration.view',
            'admin.configuration.propose',
            'admin.audit.view',
        ];

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->firstOrFail();
        foreach ($adminPermissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if (! $admin->hasPermissionTo($permission)) {
                $admin->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
