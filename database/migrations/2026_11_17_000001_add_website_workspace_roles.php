<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $websiteAccess = Permission::findOrCreate('website.access', 'web');
        $websiteManage = Permission::findOrCreate('website.manage', 'web');

        Role::findOrCreate('owner', 'web')->givePermissionTo([$websiteAccess, $websiteManage]);
        Role::findOrCreate('website_operator', 'web')->syncPermissions([$websiteAccess]);
        Role::findOrCreate('staff', 'web')->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Workspace roles may already be assigned to users; preserve them on rollback.
    }
};
