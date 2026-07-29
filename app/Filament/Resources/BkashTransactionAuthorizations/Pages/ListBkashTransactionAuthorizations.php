<?php

namespace App\Filament\Resources\BkashTransactionAuthorizations\Pages;

use App\Filament\Resources\BkashTransactionAuthorizations\BkashTransactionAuthorizationResource;
use Filament\Resources\Pages\ListRecords;

class ListBkashTransactionAuthorizations extends ListRecords
{
    protected static string $resource = BkashTransactionAuthorizationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}