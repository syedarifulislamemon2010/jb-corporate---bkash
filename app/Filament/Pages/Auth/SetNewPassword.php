<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;

/**
 * @property-read Schema $form
 */
class SetNewPassword extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;

    protected static ?string $slug = 'set-new-password';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public ?string $mobile_no = null;

    public function mount(): void
    {
        $this->mobile_no = session('reset_verified_mobile', '');
        $sessionToken    = session('reset_token', '');
        $cachedToken     = Cache::get("reset_token_{$this->mobile_no}");

        // Security: Ensure valid verified token exists to prevent direct URL bypass
        if (empty($this->mobile_no) || empty($sessionToken) || $sessionToken !== $cachedToken) {
            Notification::make()
                ->title('Session Expired')
                ->body('Your password reset session has expired or is invalid. Please start again.')
                ->danger()
                ->send();

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
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('New Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->minLength(8)
            ->same('password_confirmation')
            ->autofocus();
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('password_confirmation')
            ->label('Confirm New Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Set New Password';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Set New Password';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Create a new secure password for your account (minimum 8 characters).';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('setNewPassword')
                ->label('Reset Password')
                ->submit('setNewPassword'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('setNewPassword')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->fullWidth(true)
                            ->key('form-actions'),
                    ]),
            ]);
    }

    public function setNewPassword(): void
    {
        $data = $this->form->getState();
        $password = $data['password'] ?? '';

        $mobileNo = trim($this->mobile_no ?? '');
        $user = User::where('mobile_no', $mobileNo)->first();

        if (!$user) {
            Notification::make()
                ->title('User Not Found')
                ->body('Unable to locate user account.')
                ->danger()
                ->send();

            return;
        }

        // Update to new user-provided password
        $user->update([
            'password' => Hash::make($password),
        ]);

        // Invalidate token and session keys
        Cache::forget("reset_token_{$mobileNo}");
        session()->forget(['reset_mobile_no', 'reset_verified_mobile', 'reset_token']);

        // Auto-login user
        Auth::login($user);

        Notification::make()
            ->title('Password Reset Successful')
            ->body('Your password has been changed successfully. Welcome to JB Corporate!')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
