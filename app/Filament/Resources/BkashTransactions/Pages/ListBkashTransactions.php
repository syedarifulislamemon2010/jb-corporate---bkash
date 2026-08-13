<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use App\Models\BkashTransaction;
use App\Services\ExcelExportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;


class ListBkashTransactions extends ListRecords
{
    protected static string $resource = BkashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Transaction'),

            Action::make('upload_excel')
                ->label('Upload bKash Excel File')
                ->icon('heroicon-o-document-arrow-up')
                ->color('primary')
                ->url(BkashTransactionResource::getUrl('upload')),

            Action::make('download_csv')
                ->label('Download for Cross-Check')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $transactions = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
                        ->orderBy('create_date', 'desc')
                        ->get();

                    $fileName = 'bkash_pending_checker_' . now()->format('Ymd_His') . '.csv';

                    return ExcelExportService::exportTransactionsCsv($transactions, $fileName);
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Transmissions'),
            'a2a' => Tab::make('Account to Account (A2A) - Janata Bank PLC.')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'A2A')),
            'beftn' => Tab::make('BEFTN')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'BEFTN')),
            'rtgs' => Tab::make('RTGS')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'RTGS')),
        ];
    }
}