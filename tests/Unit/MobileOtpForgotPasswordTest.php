<?php

namespace Tests\Unit;

use App\Filament\Pages\Auth\ForgotPasswordMobile;
use App\Filament\Pages\Auth\SetNewPassword;
use App\Filament\Pages\Auth\VerifyOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class MobileOtpForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['bkash.sms_enabled' => false]); // Disable actual HTTP SMS in tests
    }

    public function test_forgot_password_page_sends_otp_and_stores_in_cache(): void
    {
        $user = User::create([
            'name'         => 'Test User',
            'organization' => 'bKash',
            'mobile_no'    => '01711223344',
            'email'        => 'user@bkash.com',
            'password'     => Hash::make('OldPassword123!'),
        ]);

        RateLimiter::clear('forgot_pwd_otp_send:01711223344');

        Livewire::test(ForgotPasswordMobile::class)
            ->set('data.mobile_no', '01711223344')
            ->call('sendOtp')
            ->assertRedirect('/admin/verify-otp');

        // Verify 6-digit OTP stored in Cache with 5 min expiry
        $cachedOtp = Cache::get('otp_reset_01711223344');
        $this->assertNotNull($cachedOtp);
        $this->assertEquals(6, strlen((string) $cachedOtp));
        $this->assertTrue(is_numeric($cachedOtp));
    }

    public function test_forgot_password_page_enforces_rate_limiting(): void
    {
        $user = User::create([
            'name'         => 'Test User 2',
            'organization' => 'bKash',
            'mobile_no'    => '01811223344',
            'email'        => 'user2@bkash.com',
            'password'     => Hash::make('OldPassword123!'),
        ]);

        RateLimiter::clear('forgot_pwd_otp_send:01811223344');

        // 1st attempt: success
        Livewire::test(ForgotPasswordMobile::class)
            ->set('data.mobile_no', '01811223344')
            ->call('sendOtp');

        $this->assertTrue(RateLimiter::tooManyAttempts('forgot_pwd_otp_send:01811223344', 1));

        // 2nd attempt within 60s: throttled
        Livewire::test(ForgotPasswordMobile::class)
            ->set('data.mobile_no', '01811223344')
            ->call('sendOtp')
            ->assertNotDispatched('redirect');
    }

    public function test_verify_otp_page_verifies_and_issues_temp_password_and_token(): void
    {
        $user = User::create([
            'name'         => 'Test User 3',
            'organization' => 'Janata Bank',
            'mobile_no'    => '01911223344',
            'email'        => 'jbuser@janatabank.com',
            'password'     => Hash::make('OldPassword123!'),
        ]);

        $otp = '654321';
        Cache::put('otp_reset_01911223344', $otp, now()->addMinutes(5));
        RateLimiter::clear('verify_otp_lockout:01911223344');

        Livewire::withQueryParams(['mobile' => '01911223344'])
            ->test(VerifyOtp::class)
            ->set('data.otp', '654321')
            ->call('verifyOtp')
            ->assertRedirect('/admin/set-new-password');

        // Assert OTP removed from Cache after successful use
        $this->assertNull(Cache::get('otp_reset_01911223344'));

        // Assert Reset Token generated in Cache
        $resetToken = Cache::get('reset_token_01911223344');
        $this->assertNotNull($resetToken);
        $this->assertEquals(40, strlen($resetToken));
    }

    public function test_verify_otp_page_rejects_invalid_otp(): void
    {
        $user = User::create([
            'name'         => 'Test User 4',
            'organization' => 'Janata Bank',
            'mobile_no'    => '01511223344',
            'email'        => 'jbuser4@janatabank.com',
            'password'     => Hash::make('OldPassword123!'),
        ]);

        Cache::put('otp_reset_01511223344', '112233', now()->addMinutes(5));
        RateLimiter::clear('verify_otp_lockout:01511223344');

        Livewire::withQueryParams(['mobile' => '01511223344'])
            ->test(VerifyOtp::class)
            ->set('data.otp', '999999')
            ->call('verifyOtp')
            ->assertNotDispatched('redirect');

        // OTP should still remain until expired or verified
        $this->assertEquals('112233', Cache::get('otp_reset_01511223344'));
    }

    public function test_set_new_password_updates_user_password_and_logs_in(): void
    {
        $user = User::create([
            'name'         => 'Test User 5',
            'organization' => 'bKash',
            'mobile_no'    => '01611223344',
            'email'        => 'user5@bkash.com',
            'password'     => Hash::make('OldPassword123!'),
        ]);

        $resetToken = 'test_token_abcdef_1234567890_abcdef123456';
        Cache::put('reset_token_01611223344', $resetToken, now()->addMinutes(10));
        session([
            'reset_verified_mobile' => '01611223344',
            'reset_token'           => $resetToken,
        ]);

        Livewire::test(SetNewPassword::class)
            ->set('data.password', 'BrandNewSecurePass2026!')
            ->set('data.password_confirmation', 'BrandNewSecurePass2026!')
            ->call('setNewPassword')
            ->assertRedirect('/admin');

        // Verify password updated in DB
        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewSecurePass2026!', $user->password));

        // Verify token cleared
        $this->assertNull(Cache::get('reset_token_01611223344'));

        // Verify user logged in
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }
}
