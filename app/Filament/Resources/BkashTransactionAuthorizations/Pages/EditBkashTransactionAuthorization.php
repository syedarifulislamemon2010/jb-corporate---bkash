<?php

namespace App\Filament\Resources\BkashTransactionAuthorizations\Pages;

use App\Filament\Resources\BkashTransactionAuthorizations\BkashTransactionAuthorizationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBkashTransactionAuthorization extends EditRecord
{
    protected static string $resource = BkashTransactionAuthorizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
