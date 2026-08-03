<?php

namespace App\Filament\Widgets;

use App\Models\BkashTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Mock / Live CBS Account Balances for bKash Accounts
        $tcsaBalance = 542000000.50; // Trust Cum Settlement Acc 0100202707747
        $opsBalance  = 18500000.00;  // Operational Acc 0100224107522

        $todaySettledVolume = (float)BkashTransaction::whereIn('status_id', [
            BkashTransaction::STATUS_FINAL_AUTHORIZED,
            BkashTransaction::STATUS_CBS_SUCCESS
        ])->whereDate('updated_at', today())->sum('amount');

        return [
            Stat::make('TCSA Live Balance (0100202707747)', 'BDT ' . BkashTransaction::formatBdtAmount($tcsaBalance))
                ->description('Trust Cum Settlement Account')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Operational Acc Balance (0100224107522)', 'BDT ' . BkashTransaction::formatBdtAmount($opsBalance))
                ->description('Operational Account')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),

            Stat::make('Pending Checker', BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count())
                ->description('Awaiting Checker verification')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Pending Authorizations (Auth 1 & 2)', BkashTransaction::whereIn('status_id', [
                BkashTransaction::STATUS_CHECKED,
                BkashTransaction::STATUS_AUTH_1_APPROVED
            ])->count())
                ->description('Awaiting Dual Approval')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Today Settled Volume', 'BDT ' . BkashTransaction::formatBdtAmount($todaySettledVolume))
                ->description('Processed today')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}