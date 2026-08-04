<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
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
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Transmissions'),
            'a2a' => Tab::make('Account to Account (A2A)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'A2A')),
            'beftn' => Tab::make('BEFTN Pipeline')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'BEFTN')),
            'rtgs' => Tab::make('RTGS Premium')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'RTGS')),
        ];
    }
}