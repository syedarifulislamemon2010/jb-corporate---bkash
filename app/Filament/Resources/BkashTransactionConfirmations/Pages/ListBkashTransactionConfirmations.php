<?php

namespace App\Filament\Resources\BkashTransactionConfirmations\Pages;

use App\Filament\Resources\BkashTransactionConfirmations\BkashTransactionConfirmationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBkashTransactionConfirmations extends ListRecords
{
    protected static string $resource = BkashTransactionConfirmationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Transactions'),
            'a2a' => Tab::make('Account to Account (A2A) - Janata Bank PLC.')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'A2A')),
            'beftn' => Tab::make('BEFTN')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'BEFTN')),
            'rtgs' => Tab::make('RTGS')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'RTGS')),
        ];
    }
}