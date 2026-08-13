<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'bKash Settlement Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    /**
     * Override getWidgets to ONLY show our custom StatsOverview.
     * This removes the default AccountWidget and FilamentInfoWidget.
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
        ];
    }

    /**
     * Full-width layout for a premium dashboard experience.
     */
    public function getColumns(): int | array
    {
        return 1;
    }
}
