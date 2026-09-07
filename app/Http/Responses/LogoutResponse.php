<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $loginUrl = Filament::hasLogin() ? Filament::getLoginUrl() : Filament::getUrl();
        $redirectUrl = $loginUrl . (str_contains($loginUrl, '?') ? '&' : '?') . 'logged_out=1';

        return redirect()->to($redirectUrl);
    }
}
