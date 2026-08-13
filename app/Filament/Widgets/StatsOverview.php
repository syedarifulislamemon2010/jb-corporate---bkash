<?php

namespace App\Filament\Widgets;

use App\Models\BkashTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Live Balance - In production, replace with CBS API call
        $tcsaBalance = $this->fetchAccountBalance('0100202707747');
        $opsBalance  = $this->fetchAccountBalance('0100224107522');

        $todaySettledVolume = (float) BkashTransaction::whereIn('status_id', [
            BkashTransaction::STATUS_FINAL_AUTHORIZED,
            BkashTransaction::STATUS_CBS_SUCCESS
        ])->whereDate('updated_at', today())->sum('amount');

        $pendingChecker = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
        $pendingAuth = BkashTransaction::whereIn('status_id', [
            BkashTransaction::STATUS_CHECKED,
            BkashTransaction::STATUS_AUTH_1_APPROVED
        ])->count();

        return [
            Stat::make('TCSA Live Balance (0100202707747)', 'BDT ' . BkashTransaction::formatBdtAmount($tcsaBalance))
                ->description('Trust Cum Settlement Account')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Operational Acc Balance (0100224107522)', 'BDT ' . BkashTransaction::formatBdtAmount($opsBalance))
                ->description('Operational Account')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),

            Stat::make('Pending Checker', $pendingChecker)
                ->description('Awaiting Checker verification')
                ->descriptionIcon('heroicon-m-clock')
                ->url('/admin/bkash-transactions')
                ->color('warning'),

            Stat::make('Pending Authorization', $pendingAuth)
                ->description('Awaiting Dual Approval (Auth 1 & 2)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->url('/admin/bkash-transaction-authorizations')
                ->color('info'),

            Stat::make('Today Settled Volume', 'BDT ' . BkashTransaction::formatBdtAmount($todaySettledVolume))
                ->description('Processed today')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }

    /**
     * Fetch account balance from CBS API.
     * TODO: Replace with actual CBS API integration when available.
     */
    private function fetchAccountBalance(string $accountNumber): float
    {
        try {
            // Production: Call CBS/Core Banking API here
            // Example:
            // $response = Http::timeout(5)->get(config('cbs.api_url') . '/balance/' . $accountNumber);
            // return (float) $response->json('balance', 0);

            // Placeholder: Calculate from transaction history
            $totalDebited = (float) BkashTransaction::where('credit_account_no', $accountNumber)
                ->whereIn('status_id', [
                    BkashTransaction::STATUS_FINAL_AUTHORIZED,
                    BkashTransaction::STATUS_CBS_SUCCESS,
                ])
                ->sum('amount');

            $balances = config('bkash.initial_balances', []);
            $initialBalance = (float) ($balances[$accountNumber] ?? 0.00);

            return $initialBalance - $totalDebited;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch balance for {$accountNumber}: " . $e->getMessage());
            return 0.00;
        }
    }
}