<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentShield::enforcePolicies();

        Gate::define('viewLogViewer', function ($user = null) {
            if (! $user) {
                return false;
            }

            if (app()->environment('local')) {
                return true;
            }

            return $user->hasAnyRole(['super_admin', 'Admin', 'admin']);
        });
    }
}
