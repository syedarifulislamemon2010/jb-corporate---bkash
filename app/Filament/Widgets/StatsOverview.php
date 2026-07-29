<?php

namespace App\Filament\Widgets;

use App\Models\BkashTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending Authorizations', BkashTransaction::where('status_id', 1001)->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),

            Stat::make('Today Confirmed Volume', 'BDT ' . number_format(BkashTransaction::where('status_id', 1003)->whereDate('confirmed_at', today())->sum('amount'), 2))
                ->description('Successful transactions today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Transactions Today', BkashTransaction::whereDate('created_at', today())->count())
                ->description('All processed types')
                ->color('info'),
        ];
    }
}