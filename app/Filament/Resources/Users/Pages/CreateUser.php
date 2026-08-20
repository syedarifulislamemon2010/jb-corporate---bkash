<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Helper\SMSGenerateHelper;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;
        $plainPassword = $this->data['password'] ?? '';
        $mobile = $user->mobile_no ?? null;

        if (!empty($mobile) && !empty($plainPassword)) {
            try {
                // Send Type 1 SMS: Account Created with temporary password
                SMSGenerateHelper::generate(
                    mobile: $mobile,
                    password: $plainPassword,
                    type: 1
                );
                Log::info("User Creation SMS sent to {$mobile} for user {$user->name}");
            } catch (\Throwable $e) {
                Log::error("Failed to send User Creation SMS: " . $e->getMessage());
            }
        }
    }
}
