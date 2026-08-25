<?php

namespace App\Filament\Pages\Auth;

use App\Helper\SMSGenerateHelper;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

/**
 * @property-read Schema $form
 */
class VerifyOtp extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    protected static ?string $slug = 'verify-otp';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public ?string $mobile_no = null;

    public function mount(): void
    {
        $this->mobile_no = session('reset_mobile_no', request()->query('mobile', ''));

        if (empty($this->mobile_no)) {
            $this->redirect('/admin/forgot-password');

            return;
        }

        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getOtpFormComponent(),
            ]);
    }

    protected function getOtpFormComponent(): Component
    {
        return TextInput::make('otp')
            ->label('6-Digit Verification OTP')
            ->placeholder('Enter 6-digit OTP')
            ->numeric()
            ->length(6)
            ->required()
            ->autofocus();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Verify OTP';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Verify OTP';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Enter the 6-digit OTP sent to your mobile number.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('verifyOtp')
                ->label('Verify OTP')
                ->submit('verifyOtp'),
        ];
    }

    public function resendAction(): Action
    {
        return Action::make('resendOtp')
            ->link()
            ->label('Resend OTP')
            ->action('resendOtp');
    }

    public function changeNumberAction(): Action
    {
        return Action::make('changeNumber')
            ->link()
            ->label('Change Number')
            ->url('/admin/forgot-password');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('verifyOtp')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->fullWidth(true)
                            ->key('form-actions'),
                        Actions::make([$this->resendAction(), $this->changeNumberAction()])
                            ->alignment(Alignment::Between)
                            ->fullWidth(true)
                            ->key('extra-actions'),
                    ]),
            ]);
    }

    public function verifyOtp(): void
    {
        $data = $this->form->getState();
        $otp  = trim($data['otp'] ?? '');

        $mobileNo = trim($this->mobile_no ?? '');
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

        if (empty($cachedOtp) || $cachedOtp !== $otp) {
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

        // Generate temporary password (DO NOT update user password in DB yet)
        $tempPassword = Str::random(10);
        Cache::put("temp_password_{$mobileNo}", $tempPassword, now()->addMinutes(10));

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

        session([
            'reset_verified_mobile' => $mobileNo,
        ]);

        Notification::make()
            ->title('OTP Verified')
            ->body('A temporary password has been sent to your mobile. Please enter it to continue.')
            ->success()
            ->send();

        $this->redirect('/admin/enter-temp-password');
    }

    public function resendOtp(): void
    {
        $mobileNo = trim($this->mobile_no ?? '');
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
