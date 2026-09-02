<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogViewerAutoRefreshTest extends TestCase
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

    public function test_log_viewer_view_contains_auto_refresh_and_manual_refresh_controls(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/log-viewer');

        $response->assertStatus(200);

        // Verify Auto-refresh elements matching Dashboard
        $response->assertSee('jb-log-controls', false);
        $response->assertSee('db-autorefresh-label', false);
        $response->assertSee('db-autorefresh-checkbox', false);
        $response->assertSee('Auto-refresh (15s)', false);
        $response->assertSee('db-pulse-dot-sm', false);
        $response->assertSee('jb-log-pulse-dot', false);

        // Verify Manual Refresh button matching Dashboard
        $response->assertSee('db-btn-refresh', false);
        $response->assertSee('jb-log-manual-refresh-btn', false);
        $response->assertSee('jb-log-refresh-icon', false);
        $response->assertSee('jb-log-refresh-text', false);

        // Verify 15-second timer interval and reload-logs-button integration
        $response->assertSee('15000', false);
        $response->assertSee('reload-logs-button', false);
    }

    public function test_log_viewer_gate_authorizes_super_admin(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->user)->allows('viewLogViewer'));

        $regularUser = User::create([
            'name'         => 'Regular Officer',
            'email'        => 'officer@jb.com',
            'mobile_no'    => '01799887766',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);

        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($regularUser)->allows('viewLogViewer'));
    }

    public function test_log_viewer_api_files_endpoint(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/log-viewer/api/files');
        $response->assertStatus(200);
    }

    public function test_log_viewer_api_files_denies_unauthenticated_guest(): void
    {
        $response = $this->getJson('/admin/log-viewer/api/files');
        $response->assertStatus(403);
    }

    public function test_log_viewer_gate_allows_authenticated_user_in_local_environment(): void
    {
        $regularUser = User::create([
            'name'         => 'Local Officer',
            'email'        => 'local@jb.com',
            'mobile_no'    => '01711224466',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);

        $this->app->detectEnvironment(fn () => 'local');

        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($regularUser)->allows('viewLogViewer'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser(null)->allows('viewLogViewer'));
    }
}
