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
            'super_admin'        => 'Super Administrator with full system access',
            'panel_user'         => 'Standard panel user role',
            'bkash_authorizer'   => 'bKash Authorizer role',
        ];

        $roleModels = [];
        foreach ($roles as $roleName => $description) {
            $roleModels[$roleName] = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );
        }

        $bankingPermissions = [
            'ViewAny:BkashTransactionBatch',
            'View:BkashTransactionBatch',
            'Update:BkashTransactionBatch',
            'Reorder:BkashTransactionBatch',
            'ViewAny:BkashFailedTransaction',
            'View:BkashFailedTransaction',
            'Delete:BkashFailedTransaction',
            'Reorder:BkashFailedTransaction',
            'ViewAny:BkashTransaction',
            'View:BkashTransaction',
            'Update:BkashTransaction',
            'Reorder:BkashTransaction',
            'ViewAny:EftReturn',
            'View:EftReturn',
            'Update:EftReturn',
            'Reorder:EftReturn',
            'View:Dashboard',
        ];

        $permModels = [];
        foreach ($bankingPermissions as $pName) {
            $permModels[] = Permission::firstOrCreate(['name' => $pName, 'guard_name' => 'web']);
        }

        foreach (['bkash_authorizer'] as $roleName) {
            if (isset($roleModels[$roleName])) {
                $roleModels[$roleName]->syncPermissions($permModels);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Shield roles and banking permissions seeded successfully.');
    }
}
