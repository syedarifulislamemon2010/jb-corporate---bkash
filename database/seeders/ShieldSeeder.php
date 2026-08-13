<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $roles = [
            'super_admin' => 'Super Administrator with full system access',
            'bkash_checker' => 'bKash Checker — verifies uploaded transaction files',
            'bkash_authorizer_1' => 'bKash 1st Authorizer — first-level approval',
            'bkash_authorizer_2' => 'bKash 2nd Authorizer — final approval and CBS settlement',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );
        }

        $this->command->info('Shield roles seeded successfully.');
    }
}
