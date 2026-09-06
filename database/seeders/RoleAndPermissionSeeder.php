<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $roles = [
            'Admin',
            'Teacher',
            'Advisor',
            'Student',
            'Parent'
        ];

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }
}
