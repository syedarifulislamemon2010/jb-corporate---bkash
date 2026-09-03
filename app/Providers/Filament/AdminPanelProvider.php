<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Pages\Dashboard as CustomDashboard;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->passwordReset(false)
            ->brandName('Janata Bank PLC.')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('favicon.svg'))
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->spa()
            ->font('Plus Jakarta Sans')
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->globalSearchKeybindings(['command+k', 'ctrl+k'])
            ->colors([
                'primary' => Color::Sky,
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info'    => Color::Blue,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Roles & Permissions')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible(true)
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Transaction Pipeline')
                    ->icon('heroicon-o-arrows-right-left')
                    ->collapsible(true)
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Audits & Reports')
                    ->icon('heroicon-o-chart-bar-square')
                    ->collapsible(true)
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Administration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(true)
                    ->collapsed(true),
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label(fn () => (auth()->user()?->getRawOriginal('organization') ?: 'Janata Bank PLC.') . ' · ' . (auth()->user()?->roles?->first()?->name ?? 'User'))
                    ->icon('heroicon-o-building-office-2')
                    ->url('#'),
            ])
            ->navigationItems([
                NavigationItem::make('Log Viewer')
                    ->url('/admin/log-viewer', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-command-line')
                    ->group('Administration')
                    ->sort(10),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                CustomDashboard::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::FOOTER,
                fn () => view('filament.custom-footer')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn () => view('filament.custom-styles')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.components.sidebar-footer-profile')
            )
            ->widgets([])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Roles & Permissions')
                    ->navigationLabel('Roles & Permissions')
                    ->navigationIcon('heroicon-o-identification')
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ]),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
