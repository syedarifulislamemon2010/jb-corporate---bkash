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
}
