<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordResetOnFirstLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->account_status === 'temp_password_issued') {
            // Allow access to the password change page
            if ($request->is('admin/force-password-change*') || $request->routeIs('filament.admin.pages.force-password-change')) {
                return $next($request);
            }

            // Allow logout
            if ($request->is('admin/logout*') || $request->routeIs('filament.admin.auth.logout')) {
                return $next($request);
            }

            // Allow Livewire requests
            if ($request->is('livewire/*')) {
                return $next($request);
            }

            return redirect()->to('/admin/force-password-change');
        }

        return $next($request);
    }
}
