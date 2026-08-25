<?php

namespace App\Filament\Pages\Auth;

use App\Helper\SMSGenerateHelper;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordMobile extends SimplePage implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'forgot-password';

    protected string $view = 'filament.pages.auth.forgot-password-mobile';

    public ?string $mobile_no = '';

    public function getHeading(): string
    {
        return 'Forgot Password';
    }

    public function getSubheading(): ?string
    {
        return 'Enter your registered mobile number to receive a password reset OTP.';
    }

    public function mount(): void
    {
        $this->mobile_no = session('reset_mobile_no', '');
    }

    public function sendOtp(): void
    {
        $this->validate([
            'mobile_no' => ['required', 'string', 'min:10', 'max:15'],
        ]);

        $mobileNo = trim($this->mobile_no);
        $rateLimitKey = 'forgot_pwd_otp_send:' . $mobileNo;

        // Security: Rate limiting (max 1 OTP request every 60 seconds)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Notification::make()
                ->title('Too Many Requests')
                ->body("Please wait {$seconds} seconds before requesting another OTP.")
                ->warning()
                ->send();
            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        // Security: Check if user exists (Do not reveal whether user exists in notification)
        $user = User::where('mobile_no', $mobileNo)->first();

        if ($user) {
            // Generate 6-digit secure numeric OTP
            $otp = (string) random_int(100000, 999999);

            // Store in Cache with 5-minute TTL
            Cache::put("otp_reset_{$mobileNo}", $otp, now()->addMinutes(5));

            // Send SMS using exact bank template Type 4
            if (config('bkash.sms_enabled', true)) {
                try {
                    SMSGenerateHelper::generate(
                        mobile: $mobileNo,
                        password: $otp,
                        type: 4
                    );
                    Log::info("SMS dispatch attempted for password reset OTP.");
                } catch (\Throwable $e) {
                    Log::error("SMS dispatch error: " . $e->getMessage());
                }
            } else {
                Log::info("SMS dispatch attempted for password reset OTP (SMS disabled in config).");
            }
        }

        session(['reset_mobile_no' => $mobileNo]);

        Notification::make()
            ->title('OTP Dispatched')
            ->body('If this number is registered, an OTP has been sent.')
            ->success()
            ->send();

        $this->redirect('/admin/verify-otp');
    }
}
