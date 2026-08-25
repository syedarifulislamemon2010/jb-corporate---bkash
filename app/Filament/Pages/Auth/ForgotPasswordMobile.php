<?php

namespace App\Filament\Pages\Auth;

use App\Helper\SMSGenerateHelper;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @property-read Schema $form
 */
class ForgotPasswordMobile extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    protected static ?string $slug = 'forgot-password';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mobile_no' => session('reset_mobile_no', ''),
        ]);
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
                $this->getMobileNumberFormComponent(),
            ]);
    }

    protected function getMobileNumberFormComponent(): Component
    {
        return TextInput::make('mobile_no')
            ->label('Registered Mobile Number')
            ->placeholder('e.g. 01712345678')
            ->tel()
            ->required()
            ->autofocus();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Forgot Password';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Forgot Password';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Enter your registered mobile number to receive a password reset OTP.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('sendOtp')
                ->label('Send OTP')
                ->submit('sendOtp'),
        ];
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Back to login')
            ->icon('heroicon-o-arrow-left')
            ->url('/admin/login');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('sendOtp')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->fullWidth(true)
                            ->key('form-actions'),
                        Actions::make([$this->loginAction()])
                            ->alignment(Alignment::Center)
                            ->fullWidth(true)
                            ->key('login-action'),
                    ]),
            ]);
    }

    public function sendOtp(): void
    {
        $data = $this->form->getState();
        $mobileNo = trim($data['mobile_no'] ?? '');

        if (empty($mobileNo)) {
            return;
        }

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

        // Security: Check if user exists (only mobile_no column)
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
