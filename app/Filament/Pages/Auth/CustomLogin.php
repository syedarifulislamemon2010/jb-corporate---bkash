<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Html;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class CustomLogin extends BaseLogin
{
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }

    protected function getRememberFormComponent(): Component
    {
        return Flex::make([
            Checkbox::make('remember')
                ->label(__('filament-panels::auth/pages/login.form.remember.label')),
            Html::make(new HtmlString(Blade::render('<x-filament::link href="/admin/forgot-password" tabindex="-1">Forgot password?</x-filament::link>'))),
        ])
            ->alignment(Alignment::Between)
            ->verticalAlignment(VerticalAlignment::Center);
    }
}
