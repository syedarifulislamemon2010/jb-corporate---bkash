<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCanAccessPanelTest extends TestCase
{
    use RefreshDatabase;

    private $panel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->panel = Filament::getPanel('admin');
    }

    public function test_super_admin_can_access_panel(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::create([
            'name'         => 'Super Admin',
            'email'        => 'super@test.com',
            'mobile_no'    => '01711111111',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $user->assignRole('super_admin');

        $this->assertTrue($user->canAccessPanel($this->panel));
    }

    public function test_bkash_checker_can_access_panel(): void
    {
        Role::firstOrCreate(['name' => 'bkash_checker', 'guard_name' => 'web']);
        $user = User::create([
            'name'         => 'Checker User',
            'email'        => 'checker@test.com',
            'mobile_no'    => '01722222222',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $user->assignRole('bkash_checker');

        $this->assertTrue($user->canAccessPanel($this->panel));
    }

    public function test_bkash_authorizers_can_access_panel(): void
    {
        Role::firstOrCreate(['name' => 'bkash_authorizer_1', 'guard_name' => 'web']);
        $user1 = User::create([
            'name'         => 'Auth 1 User',
            'email'        => 'auth1@test.com',
            'mobile_no'    => '01733333333',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $user1->assignRole('bkash_authorizer_1');
        $this->assertTrue($user1->canAccessPanel($this->panel));

        Role::firstOrCreate(['name' => 'bkash_authorizer_2', 'guard_name' => 'web']);
        $user2 = User::create([
            'name'         => 'Auth 2 User',
            'email'        => 'auth2@test.com',
            'mobile_no'    => '01744444444',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $user2->assignRole('bkash_authorizer_2');
        $this->assertTrue($user2->canAccessPanel($this->panel));
    }

    public function test_local_testing_environment_allows_access(): void
    {
        $user = User::create([
            'name'         => 'Default User',
            'email'        => 'default@test.com',
            'mobile_no'    => '01755555555',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $this->assertTrue($user->canAccessPanel($this->panel));
    }
}