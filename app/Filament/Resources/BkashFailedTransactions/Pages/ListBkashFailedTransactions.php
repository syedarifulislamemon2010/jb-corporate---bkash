<?php

namespace App\Filament\Resources\BkashFailedTransactions\Pages;

use App\Filament\Resources\BkashFailedTransactions\BkashFailedTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListBkashFailedTransactions extends ListRecords
{
    protected static string $resource = BkashFailedTransactionResource::class;
}
