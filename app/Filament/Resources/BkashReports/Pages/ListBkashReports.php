<?php

namespace App\Filament\Resources\BkashReports\Pages;

use App\Filament\Resources\BkashReports\BkashReportsResource;
use App\Models\BkashTransaction;
use App\Services\ExcelExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBkashReports extends ListRecords
{
    protected static string $resource = BkashReportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Download Report (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $transactions = BkashTransaction::query()
                        ->orderBy('create_date', 'desc')
                        ->limit(10000)
                        ->get();

                    $fileName = 'bkash_transaction_report_' . now()->format('Ymd_His') . '.csv';

                    return ExcelExportService::exportTransactionsCsv($transactions, $fileName);
                }),

            Action::make('export_daily')
                ->label('Today\'s Report')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->action(function () {
                    $transactions = BkashTransaction::query()
                        ->whereDate('create_date', today())
                        ->orderBy('create_date', 'desc')
                        ->get();

                    $fileName = 'bkash_daily_report_' . now()->format('Ymd') . '.csv';

                    return ExcelExportService::exportTransactionsCsv($transactions, $fileName);
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Transactions'),
            'a2a' => Tab::make('Account to Account (A2A)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'A2A')),
            'beftn' => Tab::make('BEFTN')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'BEFTN')),
            'rtgs' => Tab::make('RTGS')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'RTGS')),
        ];
    }
}
