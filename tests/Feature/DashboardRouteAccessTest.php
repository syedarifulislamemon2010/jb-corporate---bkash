<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user = User::create([
            'name'         => 'Test Admin User',
            'email'        => 'admin@jb.com',
            'mobile_no'    => '01711009999',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);
        $this->user->assignRole('super_admin');
    }

    public function test_authenticated_user_can_access_admin_redirect_to_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/admin');
        $this->assertNotEquals(500, $response->getStatusCode(), "GET /admin threw 500 error: " . $response->getContent());
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }

    public function test_authenticated_user_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/dashboard');
        $this->assertNotEquals(500, $response->getStatusCode(), "GET /admin/dashboard threw 500 error: " . $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_reset_password_page_accessible(): void
    {
        $response = $this->get('/admin/reset-password');
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertSee('Reset Password');
        $response->assertSee('Back to Dashboard');
    }

    public function test_bkash_checker_can_access_dashboard_and_transactions_without_403(): void
    {
        Role::firstOrCreate(['name' => 'bkash_checker', 'guard_name' => 'web']);
        $checker = User::create([
            'name'         => 'Test Checker',
            'email'        => 'checker_test@jb.com',
            'mobile_no'    => '01711001111',
            'organization' => '1',
            'password'     => bcrypt('123456'),
        ]);
        $checker->assignRole('bkash_checker');

        $responseDash = $this->actingAs($checker)->get('/admin/dashboard');
        $this->assertEquals(200, $responseDash->getStatusCode(), 'Checker was denied dashboard access.');

        $responseTxn = $this->actingAs($checker)->get('/admin/bkash-transactions');
        $this->assertNotEquals(403, $responseTxn->getStatusCode(), 'Checker received 403 on /admin/bkash-transactions.');
    }

    public function test_bkash_authorizers_can_access_dashboard_and_pipeline_without_403(): void
    {
        Role::firstOrCreate(['name' => 'bkash_authorizer_1', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bkash_authorizer_2', 'guard_name' => 'web']);

        $auth1 = User::create([
            'name'         => 'Test Auth 1',
            'email'        => 'auth1_test@jb.com',
            'mobile_no'    => '01711002222',
            'organization' => '1',
            'password'     => bcrypt('123456'),
        ]);
        $auth1->assignRole('bkash_authorizer_1');

        $responseAuth1 = $this->actingAs($auth1)->get('/admin/bkash-transaction-authorizations');
        $this->assertNotEquals(403, $responseAuth1->getStatusCode(), 'Authorizer 1 received 403 on authorizations.');

        $auth2 = User::create([
            'name'         => 'Test Auth 2',
            'email'        => 'auth2_test@jb.com',
            'mobile_no'    => '01711003333',
            'organization' => '1',
            'password'     => bcrypt('123456'),
        ]);
        $auth2->assignRole('bkash_authorizer_2');

        $responseAuth2 = $this->actingAs($auth2)->get('/admin/bkash-transaction-confirmations');
        $this->assertNotEquals(403, $responseAuth2->getStatusCode(), 'Authorizer 2 received 403 on confirmations.');
    }
}
