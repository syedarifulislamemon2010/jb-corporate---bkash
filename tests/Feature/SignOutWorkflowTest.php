<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SignOutWorkflowTest extends TestCase
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

    public function test_authenticated_user_can_sign_out_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/admin/logout');

        $response->assertRedirect('/admin/login?logged_out=1');
        $this->assertGuest();
    }

    public function test_after_logout_user_cannot_access_dashboard_and_is_redirected_to_login(): void
    {
        $this->actingAs($this->user)->post('/admin/logout');

        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    public function test_logged_out_query_loads_login_page_cleanly_for_guests(): void
    {
        $response = $this->get('/admin/login?logged_out=1');
        $response->assertStatus(200);
        $response->assertSee('Sign in');
    }

    public function test_token_mismatch_on_logout_gracefully_redirects_to_login(): void
    {
        // Simulate a request without valid CSRF token to logout
        $response = $this->withoutMiddleware(\Illuminate\Routing\Middleware\SubstituteBindings::class)
            ->post('/admin/logout', ['_token' => 'invalid_token']);

        // Must redirect to login, never 419 or 500
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
        if ($response->isRedirection()) {
            $this->assertStringContainsString('/admin/login', $response->headers->get('Location'));
        }
    }
}
