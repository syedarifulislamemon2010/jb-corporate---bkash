<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Shield roles and permissions
        $this->call(ShieldSeeder::class);

        // 2. Seed primary administrator accounts
        $kibria = User::updateOrCreate(
            ['email' => 'kibria@jb.com'],
            [
                'name'         => 'G S Kibria',
                'password'     => Hash::make('password'),
                'mobile_no'    => '01738535099',
                'organization' => '1',
            ]
        );
        $kibria->syncRoles(['super_admin', 'panel_user']);

        $emon = User::updateOrCreate(
            ['email' => 'emon@jb.com'],
            [
                'name'         => 'Syed Ariful Islam Emon',
                'password'     => Hash::make('123456'),
                'mobile_no'    => '01711223344',
                'organization' => '1',
            ]
        );
        $emon->syncRoles(['super_admin', 'panel_user']);

        // 3. Seed banking pipeline role accounts for testing / demonstration
        $checker = User::updateOrCreate(
            ['email' => 'checker@jb.com'],
            [
                'name'         => 'Janata Checker',
                'password'     => Hash::make('123456'),
                'mobile_no'    => '01700000001',
                'organization' => '1',
            ]
        );
        $checker->syncRoles(['bkash_authorizer']);

        $auth1 = User::updateOrCreate(
            ['email' => 'authorizer1@jb.com'],
            [
                'name'         => 'Janata 1st Authorizer',
                'password'     => Hash::make('123456'),
                'mobile_no'    => '01700000002',
                'organization' => '1',
            ]
        );
        $auth1->syncRoles(['bkash_authorizer']);

        $auth2 = User::updateOrCreate(
            ['email' => 'authorizer2@jb.com'],
            [
                'name'         => 'Janata 2nd Authorizer',
                'password'     => Hash::make('123456'),
                'mobile_no'    => '01700000003',
                'organization' => '1',
            ]
        );
        $auth2->syncRoles(['bkash_authorizer']);
    }
}
