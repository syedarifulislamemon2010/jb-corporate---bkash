<?php

namespace App\Filament\Resources\BkashTransactionConfirmations\Pages;

use App\Filament\Resources\BkashTransactionConfirmations\BkashTransactionConfirmationResource;
use Filament\Resources\Pages\ListRecords;

class ListBkashTransactionConfirmations extends ListRecords
{
    protected static string $resource = BkashTransactionConfirmationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}