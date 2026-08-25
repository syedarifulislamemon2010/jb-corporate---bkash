<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class SetNewPassword extends SimplePage implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'set-new-password';

    protected string $view = 'filament.pages.auth.set-new-password';

    public ?string $mobile_no = '';
    public ?string $password = '';
    public ?string $password_confirmation = '';

    public function getHeading(): string
    {
        return 'Set New Password';
    }

    public function getSubheading(): ?string
    {
        return 'Create a new secure password for your account (minimum 8 characters).';
    }

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
        }
    }

    public function setNewPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $mobileNo = trim($this->mobile_no);
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
            'password' => Hash::make($this->password),
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
