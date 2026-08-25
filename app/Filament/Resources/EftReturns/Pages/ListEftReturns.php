<?php

namespace App\Filament\Resources\EftReturns\Pages;

use App\Filament\Resources\EftReturns\EftReturnResource;
use App\Models\EftReturn;
use App\Services\ExcelExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListEftReturns extends ListRecords
{
    protected static string $resource = EftReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                Action::make('download_today')
                    ->label('Today')
                    ->icon('heroicon-o-calendar')
                    ->action(function () {
                        $records = EftReturn::query()
                            ->whereDate('execution_date', today())
                            ->orWhereDate('created_at', today())
                            ->orderBy('id', 'desc')
                            ->get();

                        $fileName = 'EFT_Return_Report_Today_' . now()->format('Ymd') . '.xlsx';
                        return ExcelExportService::exportEftReturnsReportXlsx($records, $fileName);
                    }),

                Action::make('download_this_week')
                    ->label('This Week')
                    ->icon('heroicon-o-calendar-days')
                    ->action(function () {
                        $records = EftReturn::query()
                            ->whereBetween('execution_date', [now()->startOfWeek(), now()->endOfWeek()])
                            ->orderBy('id', 'desc')
                            ->get();

                        $fileName = 'EFT_Return_Report_Week_' . now()->format('Ymd') . '.xlsx';
                        return ExcelExportService::exportEftReturnsReportXlsx($records, $fileName);
                    }),

                Action::make('download_this_month')
                    ->label('This Month')
                    ->icon('heroicon-o-table-cells')
                    ->action(function () {
                        $records = EftReturn::query()
                            ->whereBetween('execution_date', [now()->startOfMonth(), now()->endOfMonth()])
                            ->orderBy('id', 'desc')
                            ->get();

                        $fileName = 'EFT_Return_Report_Month_' . now()->format('Ym') . '.xlsx';
                        return ExcelExportService::exportEftReturnsReportXlsx($records, $fileName);
                    }),

                Action::make('download_this_year')
                    ->label('This Year')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $records = EftReturn::query()
                            ->whereBetween('execution_date', [now()->startOfYear(), now()->endOfYear()])
                            ->orderBy('id', 'desc')
                            ->get();

                        $fileName = 'EFT_Return_Report_Year_' . now()->format('Y') . '.xlsx';
                        return ExcelExportService::exportEftReturnsReportXlsx($records, $fileName);
                    }),
            ])
            ->label('Download Report')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('primary')
            ->button(),
        ];
    }
}
