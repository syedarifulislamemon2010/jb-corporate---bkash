<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBkashTransaction extends CreateRecord
{
    protected static string $resource = BkashTransactionResource::class;
}
