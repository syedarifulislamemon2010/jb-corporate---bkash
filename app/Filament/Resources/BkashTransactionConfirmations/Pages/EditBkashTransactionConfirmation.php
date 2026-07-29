<?php

namespace App\Filament\Resources\BkashTransactionConfirmations\Pages;

use App\Filament\Resources\BkashTransactionConfirmations\BkashTransactionConfirmationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBkashTransactionConfirmation extends EditRecord
{
    protected static string $resource = BkashTransactionConfirmationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
