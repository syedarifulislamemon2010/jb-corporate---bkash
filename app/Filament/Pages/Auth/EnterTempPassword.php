<?php

namespace App\Filament\Pages\Auth;

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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

/**
 * @property-read Schema $form
 */
class EnterTempPassword extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    protected static ?string $slug = 'enter-temp-password';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public ?string $mobile_no = null;

    public function mount(): void
    {
        $this->mobile_no = session('reset_verified_mobile', '');

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
                $this->getTempPasswordFormComponent(),
            ]);
    }

    protected function getTempPasswordFormComponent(): Component
    {
        return TextInput::make('temp_password')
            ->label('Temporary Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->autofocus();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Enter Temporary Password';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Enter Temporary Password';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Enter the temporary password received via SMS to continue.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('verifyTempPassword')
                ->label('Verify')
                ->submit('verifyTempPassword'),
        ];
    }

    public function startOverAction(): Action
    {
        return Action::make('startOver')
            ->link()
            ->label('Start Over')
            ->url('/admin/forgot-password');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('verifyTempPassword')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->fullWidth(true)
                            ->key('form-actions'),
                        Actions::make([$this->startOverAction()])
                            ->alignment(Alignment::Center)
                            ->fullWidth(true)
                            ->key('extra-actions'),
                    ]),
            ]);
    }

    public function verifyTempPassword(): void
    {
        $data = $this->form->getState();
        $enteredTempPassword = trim($data['temp_password'] ?? '');

        $mobileNo = trim($this->mobile_no ?? '');
        $lockoutKey = 'verify_temp_pwd_lockout:' . $mobileNo;

        // Security: Lockout after 5 failed attempts for 15 minutes
        if (RateLimiter::tooManyAttempts($lockoutKey, 5)) {
            $minutes = ceil(RateLimiter::availableIn($lockoutKey) / 60);
            Notification::make()
                ->title('Account Temporarily Locked')
                ->body("Too many failed attempts. Please try again in {$minutes} minutes.")
                ->danger()
                ->send();

            return;
        }

        $cachedTempPassword = Cache::get("temp_password_{$mobileNo}");

        if (empty($cachedTempPassword) || $cachedTempPassword !== $enteredTempPassword) {
            RateLimiter::hit($lockoutKey, 900); // 15-minute window

            Notification::make()
                ->title('Verification Failed')
                ->body('Invalid or expired temporary password.')
                ->danger()
                ->send();

            return;
        }

        // Valid -> clear rate limiter and temp password cache
        RateLimiter::clear($lockoutKey);
        Cache::forget("temp_password_{$mobileNo}");

        $user = User::where('mobile_no', $mobileNo)->first();

        if (!$user) {
            Notification::make()
                ->title('User Not Found')
                ->body('No user account associated with this number.')
                ->danger()
                ->send();

            return;
        }

        // Generate reset token for SetNewPassword step
        $resetToken = Str::random(40);
        Cache::put("reset_token_{$mobileNo}", $resetToken, now()->addMinutes(10));

        session([
            'reset_token' => $resetToken,
        ]);

        Notification::make()
            ->title('Temporary Password Verified')
            ->body('Please create your new password.')
            ->success()
            ->send();

        $this->redirect('/admin/set-new-password');
    }
}
