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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @property-read Schema $form
 */
class ForcePasswordChange extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;

    protected static ?string $slug = 'force-password-change';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            $this->redirect('/admin/login');
            return;
        }

        if ($user->account_status !== 'temp_password_issued') {
            $this->redirect('/admin');
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
                $this->getCurrentPasswordFormComponent(),
                $this->getNewPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('current_password')
            ->label('Temporary Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->autofocus();
    }

    protected function getNewPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('New Permanent Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->minLength(8)
            ->same('password_confirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('password_confirmation')
            ->label('Confirm Permanent Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Change Temporary Password';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Change Temporary Password';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'For security compliance, you must set a new permanent password before proceeding to the corporate portal.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('changePassword')
                ->label('Set New Password & Continue')
                ->submit('changePassword'),
            Action::make('logout')
                ->label('Cancel & Sign Out')
                ->color('gray')
                ->action('signOut'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('changePassword')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->fullWidth(true)
                            ->key('form-actions'),
                    ]),
            ]);
    }

    public function changePassword(): void
    {
        $user = Auth::user();

        if (!$user) {
            $this->redirect('/admin/login');
            return;
        }

        $data = $this->form->getState();
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['password'] ?? '';

        if (!Hash::check($currentPassword, $user->password)) {
            Notification::make()
                ->title('Invalid Current Password')
                ->body('The temporary password you entered is incorrect. Please check the credentials received.')
                ->danger()
                ->send();

            return;
        }

        if (Hash::check($newPassword, $user->password)) {
            Notification::make()
                ->title('Password Must Be Different')
                ->body('Your new permanent password must be different from your temporary password.')
                ->warning()
                ->send();

            return;
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'account_status' => 'active',
        ]);

        Notification::make()
            ->title('Password Changed Successfully')
            ->body('Your permanent password has been set. Welcome to JB Nikash Corporate Portal!')
            ->success()
            ->send();

        $this->redirect('/admin');
    }

    public function signOut(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/admin/login');
    }
}
