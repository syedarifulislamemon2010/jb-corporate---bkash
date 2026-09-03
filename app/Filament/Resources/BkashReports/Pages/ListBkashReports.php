<?php

namespace App\Filament\Resources\BkashReports\Pages;

use App\Filament\Resources\BkashReports\BkashReportsResource;
use App\Models\BkashTransaction;
use App\Services\ExcelExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBkashReports extends ListRecords
{
    protected static string $resource = BkashReportsResource::class;

    protected string $view = 'filament.resources.bkash-reports.pages.list-bkash-reports';

    public ?string $activeTab = 'all';

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        if ($this->activeTab === 'a2a') {
            $query->where('transaction_type', 'A2A');
        } elseif ($this->activeTab === 'beftn') {
            $query->where('transaction_type', 'BEFTN');
        } elseif ($this->activeTab === 'rtgs') {
            $query->where('transaction_type', 'RTGS');
        }

        return $query;
    }

    public function getTabs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                Action::make('download_today')
                    ->label('Today')
                    ->icon('heroicon-o-calendar')
                    ->tooltip('Download Excel report for today')
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
                    ->tooltip('Download Excel report for this week')
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
                    ->tooltip('Download Excel report for this month')
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
                    ->tooltip('Download Excel report for this year')
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
            ->tooltip('Download transaction reports by time period')
            ->color('primary')
            ->button(),
        ];
    }
}