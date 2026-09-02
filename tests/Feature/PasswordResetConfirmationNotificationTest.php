<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\ForcePasswordChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetConfirmationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $tempUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->tempUser = User::create([
            'name'           => 'Jane Doe',
            'email'          => 'jane.doe@jb.com.bd',
            'mobile_no'      => '01711223344',
            'organization'   => 'Janata Bank PLC.',
            'password'       => Hash::make('TempSecret@123'),
            'account_status' => 'temp_password_issued',
        ]);
        $this->tempUser->assignRole('super_admin');
    }

    public function test_password_reset_dispatches_confirmation_email(): void
    {
        config([
            'bkash.email_enabled' => true,
            'bkash.sms_enabled'   => false,
        ]);

        $this->actingAs($this->tempUser);

        Livewire::test(ForcePasswordChange::class)
            ->fillForm([
                'current_password'      => 'TempSecret@123',
                'password'              => 'NewSecurePass@2026',
                'password_confirmation' => 'NewSecurePass@2026',
            ])
            ->call('changePassword')
            ->assertRedirect('/admin');

        $this->tempUser->refresh();
        $this->assertEquals('active', $this->tempUser->account_status);
        $this->assertTrue(Hash::check('NewSecurePass@2026', $this->tempUser->password));

        $transport = app('mailer')->getSymfonyTransport();
        $this->assertGreaterThanOrEqual(1, count($transport->messages()));

        $sentEmail = $transport->messages()[0];
        $this->assertStringContainsString('jane.doe@jb.com.bd', $sentEmail->toString());
    }

    public function test_password_reset_handles_sms_dispatch_gracefully_when_disabled(): void
    {
        config([
            'bkash.email_enabled' => false,
            'bkash.sms_enabled'   => false,
        ]);

        $this->actingAs($this->tempUser);

        Livewire::test(ForcePasswordChange::class)
            ->fillForm([
                'current_password'      => 'TempSecret@123',
                'password'              => 'AnotherSecurePass@2026',
                'password_confirmation' => 'AnotherSecurePass@2026',
            ])
            ->call('changePassword')
            ->assertRedirect('/admin');

        $this->tempUser->refresh();
        $this->assertEquals('active', $this->tempUser->account_status);
    }
}
