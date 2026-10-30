<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['panel.access', 'modules.manage'];
        foreach (['customers', 'catalog', 'inventory', 'sales', 'crm', 'pos'] as $module) {
            $permissions[] = $module.'.view';
        }
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('owner', 'web')->syncPermissions($permissions);
        Role::findOrCreate('operator', 'web')->syncPermissions(['panel.access']);
    }
}
