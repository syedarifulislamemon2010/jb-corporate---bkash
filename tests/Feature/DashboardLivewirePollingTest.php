<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardLivewirePollingTest extends TestCase
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

    public function test_dashboard_initializes_with_auto_refresh_enabled_and_renders_wire_poll(): void
    {
        Livewire::actingAs($this->user)
            ->test(Dashboard::class)
            ->assertSet('autoRefresh', true)
            ->assertSeeHtml('wire:poll.15s="refreshData"')
            ->assertSeeHtml('wire:click="refreshData"')
            ->assertSee('Auto-refresh (15s)')
            ->assertSee('Refresh');
    }

    public function test_toggling_auto_refresh_disables_wire_poll(): void
    {
        Livewire::actingAs($this->user)
            ->test(Dashboard::class)
            ->set('autoRefresh', false)
            ->assertSet('autoRefresh', false)
            ->assertDontSeeHtml('wire:poll.15s="refreshData"');
    }

    public function test_refresh_data_method_executes_smoothly(): void
    {
        Livewire::actingAs($this->user)
            ->test(Dashboard::class)
            ->call('refreshData')
            ->assertStatus(200);
    }
}