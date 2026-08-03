<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
        ];
    }
}