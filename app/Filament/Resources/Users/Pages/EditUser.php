<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Helper\SMSGenerateHelper;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $plainPassword = $this->data['password'] ?? '';
        $mobile = $user->mobile_no ?? null;

        // If password was updated, send Type 2 Password Reset SMS
        if (!empty($mobile) && !empty($plainPassword)) {
            try {
                SMSGenerateHelper::generate(
                    mobile: $mobile,
                    password: $plainPassword,
                    type: 2
                );
                Log::info("Password Reset SMS sent to {$mobile} for user {$user->name}");
            } catch (\Throwable $e) {
                Log::error("Failed to send Password Reset SMS: " . $e->getMessage());
            }
        }
    }
}
