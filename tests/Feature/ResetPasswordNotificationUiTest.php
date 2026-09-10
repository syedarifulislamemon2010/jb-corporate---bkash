<?php

namespace Tests\Feature;

use App\Filament\Livewire\CustomDatabaseNotifications;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResetPasswordNotificationUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user = User::create([
            'name'         => 'Test User',
            'email'        => 'test@jb.com',
            'mobile_no'    => '01711009999',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);
        $this->user->assignRole('super_admin');

        Notification::make()
            ->title('Transactions 1st Authorized by G S Kibria')
            ->body('File: RTGS_JANATA_BANK_2026_07_28_2Sloty.xlsx.xls | Total Trn: 2, Amount: BDT 3.00. Pending Final Authorization.')
            ->sendToDatabase($this->user);
    }

    public function test_authenticated_user_accessing_reset_password_renders_panel_styles(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/reset-password');
        $response->assertStatus(200);

        $content = $response->getContent();

        $this->assertStringContainsString('jb-notifications-slideover', $content, 'Expected custom-styles to be rendered in body end');
        $this->assertStringContainsString('jb-notif-heading', $content);
    }

    public function test_database_notifications_renders_with_explicit_svg_dimensions(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(CustomDatabaseNotifications::class);
        $html = $component->html();

        // Check that SVGs have explicit style dimensions
        $this->assertStringContainsString('style="width: 16px; height: 16px; min-width: 16px; max-width: 16px;"', $html);
        $this->assertStringContainsString('style="width: 14px; height: 14px; min-width: 14px; max-width: 14px;"', $html);
    }

    public function test_custom_styles_contains_notification_svg_constraints(): void
    {
        $rendered = view('filament.custom-styles')->render();

        $this->assertStringContainsString('.jb-notif-icon-box svg', $rendered);
        $this->assertStringContainsString('.jb-mark-all-btn svg', $rendered);
        $this->assertStringContainsString('.jb-clear-action-wrapper svg', $rendered);
        $this->assertStringContainsString('.jb-notif-empty-icon svg', $rendered);
    }

    public function test_guest_can_access_reset_password_page_without_errors(): void
    {
        $response = $this->get('/admin/reset-password');
        $response->assertStatus(200);
        $response->assertSee('Reset Password');
        $response->assertSee('Registered Mobile Number');
    }

    public function test_guest_does_not_see_notification_panel(): void
    {
        $response = $this->get('/admin/reset-password');
        $response->assertStatus(200);
        $response->assertDontSee('fi-simple-layout-header');
    }
}
