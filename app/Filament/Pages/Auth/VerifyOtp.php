<?php

namespace App\Filament\Pages\Auth;

use App\Helper\SMSGenerateHelper;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class VerifyOtp extends SimplePage implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'verify-otp';

    protected string $view = 'filament.pages.auth.verify-otp';

    public ?string $mobile_no = '';
    public ?string $otp = '';

    public function getHeading(): string
    {
        return 'Verify OTP';
    }

    public function getSubheading(): ?string
    {
        return 'Enter the 6-digit OTP sent to your mobile number.';
    }

    public function mount(): void
    {
        $this->mobile_no = session('reset_mobile_no', request()->query('mobile', ''));

        if (empty($this->mobile_no)) {
            $this->redirect('/admin/forgot-password');
        }
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $mobileNo = trim($this->mobile_no);
        $lockoutKey = 'verify_otp_lockout:' . $mobileNo;

        // Security: Lockout after 5 failed attempts for 15 minutes
        if (RateLimiter::tooManyAttempts($lockoutKey, 5)) {
            $minutes = ceil(RateLimiter::availableIn($lockoutKey) / 60);
            Notification::make()
                ->title('Account Temporarily Locked')
                ->body("Too many failed OTP attempts. Please try again in {$minutes} minutes.")
                ->danger()
                ->send();
            return;
        }

        $cachedOtp = Cache::get("otp_reset_{$mobileNo}");

        if (empty($cachedOtp) || $cachedOtp !== trim($this->otp)) {
            RateLimiter::hit($lockoutKey, 900); // 15-minute window

            Notification::make()
                ->title('Verification Failed')
                ->body('Invalid or expired OTP. Please request a new one.')
                ->danger()
                ->send();
            return;
        }

        // OTP is valid -> clear rate limiter and OTP cache
        RateLimiter::clear($lockoutKey);
        Cache::forget("otp_reset_{$mobileNo}");

        $user = User::where('mobile_no', $mobileNo)->first();

        if (!$user) {
            Notification::make()
                ->title('User Not Found')
                ->body('No user account associated with this number.')
                ->danger()
                ->send();
            return;
        }

        // Generate temporary password
        $tempPassword = Str::random(10);
        $user->update([
            'password' => Hash::make($tempPassword),
        ]);

        // Send SMS with temporary password using exact bank template Type 2
        if (config('bkash.sms_enabled', true)) {
            try {
                SMSGenerateHelper::generate(
                    mobile: $mobileNo,
                    password: $tempPassword,
                    type: 2
                );
                Log::info("SMS dispatch attempted for temporary password reset.");
            } catch (\Throwable $e) {
                Log::error("SMS dispatch error for temp password: " . $e->getMessage());
            }
        } else {
            Log::info("SMS dispatch attempted for temporary password (SMS disabled in config).");
        }

        // Store secure token in session and cache for step 3 authentication
        $resetToken = Str::random(40);
        Cache::put("reset_token_{$mobileNo}", $resetToken, now()->addMinutes(10));

        session([
            'reset_verified_mobile' => $mobileNo,
            'reset_token'           => $resetToken,
        ]);

        Notification::make()
            ->title('OTP Verified')
            ->body('A temporary password has been sent to your mobile. Please set your new password.')
            ->success()
            ->send();

        $this->redirect('/admin/set-new-password');
    }

    public function resendOtp(): void
    {
        $mobileNo = trim($this->mobile_no);
        $rateLimitKey = 'forgot_pwd_otp_send:' . $mobileNo;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Notification::make()
                ->title('Please Wait')
                ->body("Please wait {$seconds} seconds before requesting a new OTP.")
                ->warning()
                ->send();
            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $otp = (string) random_int(100000, 999999);
        Cache::put("otp_reset_{$mobileNo}", $otp, now()->addMinutes(5));

        if (config('bkash.sms_enabled', true)) {
            try {
                SMSGenerateHelper::generate(
                    mobile: $mobileNo,
                    password: $otp,
                    type: 4
                );
                Log::info("SMS dispatch attempted for resent OTP.");
            } catch (\Throwable $e) {
                Log::error("SMS resend error: " . $e->getMessage());
            }
        }

        Notification::make()
            ->title('New OTP Sent')
            ->body('A new 6-digit OTP has been sent to your mobile.')
            ->success()
            ->send();
    }
}
