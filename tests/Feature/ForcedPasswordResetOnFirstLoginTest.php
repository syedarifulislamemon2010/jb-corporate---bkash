<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\ForcePasswordChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ForcedPasswordResetOnFirstLoginTest extends TestCase
{
    use RefreshDatabase;

    protected User $tempUser;
    protected User $activeUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->tempUser = User::create([
            'name'           => 'Temp Password User',
            'email'          => 'temp@jb.com',
            'mobile_no'      => '01711009988',
            'organization'   => 'Janata Bank PLC.',
            'password'       => Hash::make('TempPass@123'),
            'account_status' => 'temp_password_issued',
        ]);
        $this->tempUser->assignRole('super_admin');

        $this->activeUser = User::create([
            'name'           => 'Active Admin User',
            'email'          => 'active@jb.com',
            'mobile_no'      => '01711009977',
            'organization'   => 'Janata Bank PLC.',
            'password'       => Hash::make('PermanentPass@123'),
            'account_status' => 'active',
        ]);
        $this->activeUser->assignRole('super_admin');
    }

    public function test_user_with_temp_password_is_redirected_from_dashboard_to_force_password_change(): void
    {
        $response = $this->actingAs($this->tempUser)->get('/admin/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/force-password-change');
    }

    public function test_user_with_temp_password_is_redirected_from_other_admin_pages(): void
    {
        $response = $this->actingAs($this->tempUser)->get('/admin/bkash-batches');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/force-password-change');
    }

    public function test_user_with_temp_password_can_view_force_password_change_page(): void
    {
        $response = $this->actingAs($this->tempUser)->get('/admin/force-password-change');

        $response->assertStatus(200);
        $response->assertSee('Change Temporary Password');
        $response->assertSee('Temporary Password');
        $response->assertSee('New Permanent Password');
    }

    public function test_unauthenticated_user_accessing_force_password_change_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/force-password-change');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    public function test_active_user_accessing_force_password_change_is_redirected_to_admin(): void
    {
        $response = $this->actingAs($this->activeUser)->get('/admin/force-password-change');

        $response->assertStatus(302);
        $response->assertRedirect('/admin');
    }

    public function test_user_submitting_incorrect_current_password_is_rejected(): void
    {
        $this->actingAs($this->tempUser);

        Livewire::test(ForcePasswordChange::class)
            ->fillForm([
                'current_password'      => 'WrongPass!123',
                'password'              => 'BrandNewPass@999',
                'password_confirmation' => 'BrandNewPass@999',
            ])
            ->call('changePassword')
            ->assertHasNoFormErrors();

        // User account_status must remain 'temp_password_issued'
        $this->tempUser->refresh();
        $this->assertEquals('temp_password_issued', $this->tempUser->account_status);
        $this->assertTrue(Hash::check('TempPass@123', $this->tempUser->password));
    }

    public function test_user_submitting_same_password_as_temp_password_is_rejected(): void
    {
        $this->actingAs($this->tempUser);

        Livewire::test(ForcePasswordChange::class)
            ->fillForm([
                'current_password'      => 'TempPass@123',
                'password'              => 'TempPass@123',
                'password_confirmation' => 'TempPass@123',
            ])
            ->call('changePassword');

        // User account_status must remain 'temp_password_issued'
        $this->tempUser->refresh();
        $this->assertEquals('temp_password_issued', $this->tempUser->account_status);
    }

    public function test_user_successfully_changing_password_becomes_active_and_redirects_to_admin(): void
    {
        $this->actingAs($this->tempUser);

        Livewire::test(ForcePasswordChange::class)
            ->fillForm([
                'current_password'      => 'TempPass@123',
                'password'              => 'BrandNewSecurePass@2026',
                'password_confirmation' => 'BrandNewSecurePass@2026',
            ])
            ->call('changePassword')
            ->assertRedirect('/admin');

        $this->tempUser->refresh();
        $this->assertEquals('active', $this->tempUser->account_status);
        $this->assertTrue(Hash::check('BrandNewSecurePass@2026', $this->tempUser->password));
    }

    public function test_active_user_can_access_dashboard_without_redirection(): void
    {
        $response = $this->actingAs($this->activeUser)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('bKash Settlement Dashboard');
    }

    public function test_user_with_temp_password_can_logout_cleanly(): void
    {
        $this->actingAs($this->tempUser);

        Livewire::test(ForcePasswordChange::class)
            ->call('signOut')
            ->assertRedirect('/admin/login');

        $this->assertFalse(auth()->check());
    }

    public function test_user_model_helper_methods(): void
    {
        $this->assertTrue($this->tempUser->isTempPasswordIssued());
        $this->assertFalse($this->activeUser->isTempPasswordIssued());

        $this->tempUser->markPasswordChanged();
        $this->assertEquals('active', $this->tempUser->account_status);
        $this->assertFalse($this->tempUser->isTempPasswordIssued());
    }
}
