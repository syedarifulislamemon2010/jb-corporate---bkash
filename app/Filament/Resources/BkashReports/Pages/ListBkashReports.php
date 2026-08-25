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
            \Filament\Actions\ActionGroup::make([
                Action::make('download_today')
                    ->label('Today')
                    ->icon('heroicon-o-calendar')
                    ->action(function () {
                        $transactions = BkashTransaction::query()
                            ->whereDate('create_date', today())
                            ->orderBy('row_sequence', 'asc')
                            ->orderBy('id', 'asc')
                            ->get();

                        $fileName = 'Transaction_Report_Today_' . now()->format('Ymd') . '.xlsx';
                        return ExcelExportService::exportCheckerReportXlsx($transactions, $fileName);
                    }),

                Action::make('download_this_week')
                    ->label('This Week')
                    ->icon('heroicon-o-calendar-days')
                    ->action(function () {
                        $transactions = BkashTransaction::query()
                            ->whereBetween('create_date', [now()->startOfWeek(), now()->endOfWeek()])
                            ->orderBy('row_sequence', 'asc')
                            ->orderBy('id', 'asc')
                            ->get();

                        $fileName = 'Transaction_Report_Week_' . now()->format('Ymd') . '.xlsx';
                        return ExcelExportService::exportCheckerReportXlsx($transactions, $fileName);
                    }),

                Action::make('download_this_month')
                    ->label('This Month')
                    ->icon('heroicon-o-table-cells')
                    ->action(function () {
                        $transactions = BkashTransaction::query()
                            ->whereBetween('create_date', [now()->startOfMonth(), now()->endOfMonth()])
                            ->orderBy('row_sequence', 'asc')
                            ->orderBy('id', 'asc')
                            ->get();

                        $fileName = 'Transaction_Report_Month_' . now()->format('Ym') . '.xlsx';
                        return ExcelExportService::exportCheckerReportXlsx($transactions, $fileName);
                    }),

                Action::make('download_this_year')
                    ->label('This Year')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $transactions = BkashTransaction::query()
                            ->whereBetween('create_date', [now()->startOfYear(), now()->endOfYear()])
                            ->orderBy('row_sequence', 'asc')
                            ->orderBy('id', 'asc')
                            ->get();

                        $fileName = 'Transaction_Report_Year_' . now()->format('Y') . '.xlsx';
                        return ExcelExportService::exportCheckerReportXlsx($transactions, $fileName);
                    }),
            ])
            ->label('Download Report')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('primary')
            ->button(),
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
