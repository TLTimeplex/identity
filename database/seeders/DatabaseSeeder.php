<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        ////////////////////////////////////
        // Roles and Permissions Seeder
        ////////////////////////////////////

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::createOrFirst(['name' => 'identity.admin']);
        Permission::createOrFirst(['name' => 'identity.admin.view']);
        Permission::createOrFirst(['name' => 'identity.admin.edit']);
        Permission::createOrFirst(['name' => 'identity.admin.delete']);

        // update cache to know about the newly created permissions (required if using WithoutModelEvents in seeders)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::createOrFirst(['name' => 'identity.admin']);
        $role->givePermissionTo("identity.admin");
    }
}
