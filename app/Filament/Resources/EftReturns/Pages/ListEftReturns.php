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
            Action::make('download_today')
                ->label('Download Today')
                ->icon('heroicon-o-calendar')
                ->color('primary')
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
                ->label('Download This Week')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->action(function () {
                    $records = EftReturn::query()
                        ->whereBetween('execution_date', [now()->startOfWeek(), now()->endOfWeek()])
                        ->orderBy('id', 'desc')
                        ->get();

                    $fileName = 'EFT_Return_Report_Week_' . now()->format('Ymd') . '.xlsx';
                    return ExcelExportService::exportEftReturnsReportXlsx($records, $fileName);
                }),

            Action::make('download_this_month')
                ->label('Download This Month')
                ->icon('heroicon-o-table-cells')
                ->color('warning')
                ->action(function () {
                    $records = EftReturn::query()
                        ->whereBetween('execution_date', [now()->startOfMonth(), now()->endOfMonth()])
                        ->orderBy('id', 'desc')
                        ->get();

                    $fileName = 'EFT_Return_Report_Month_' . now()->format('Ym') . '.xlsx';
                    return ExcelExportService::exportEftReturnsReportXlsx($records, $fileName);
                }),

            Action::make('download_this_year')
                ->label('Download This Year')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $records = EftReturn::query()
                        ->whereBetween('execution_date', [now()->startOfYear(), now()->endOfYear()])
                        ->orderBy('id', 'desc')
                        ->get();

                    $fileName = 'EFT_Return_Report_Year_' . now()->format('Y') . '.xlsx';
                    return ExcelExportService::exportEftReturnsReportXlsx($records, $fileName);
                }),
        ];
    }
}
