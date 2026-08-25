<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class CustomLogin extends BaseLogin
{
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->hint(new HtmlString(Blade::render('<x-filament::link href="/admin/forgot-password" tabindex="-1">Forgot password?</x-filament::link>')))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }
}
