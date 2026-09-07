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
}
