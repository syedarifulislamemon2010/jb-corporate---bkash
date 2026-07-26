<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBkashTransaction extends CreateRecord
{
    protected static string $resource = BkashTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']  = auth()->id();
        $data['status_id']   = 1;
        $data['create_date'] = now();

        return $data;
    }
}