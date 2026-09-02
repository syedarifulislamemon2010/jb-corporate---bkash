<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Helper\SMSGenerateHelper;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['account_status'] = 'temp_password_issued';

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $plainPassword = $this->data['password'] ?? '';
        $mobile = $user->mobile_no ?? null;
        $email = $user->email ?? null;

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

        if (!empty($email) && !empty($plainPassword) && config('bkash.email_enabled', true)) {
            try {
                $subject = "Welcome to JB Nikash Corporate Portal - Your Temporary Credentials";
                $messageBody = "Dear {$user->name},\n\n" .
                    "Your user account has been created in JB Nikash Solution (Janata Bank Corporate Portal).\n\n" .
                    "Login ID / Email: {$user->email}\n" .
                    "Temporary Password: {$plainPassword}\n\n" .
                    "Please log in at " . url('/admin/login') . " to set your permanent password upon first login.\n\n" .
                    "Best Regards,\nJanata Bank PLC";

                Mail::raw($messageBody, function ($message) use ($email, $subject) {
                    $message->to($email)
                        ->subject($subject)
                        ->from(
                            config('bkash.email_from_address', config('mail.from.address', 'no-reply@jb.com.bd')),
                            config('bkash.email_from_name', config('mail.from.name', 'Janata Bank PLC'))
                        );
                });
                Log::info("User Creation Email sent to {$email} for user {$user->name}");
            } catch (\Throwable $e) {
                Log::error("Failed to send User Creation Email: " . $e->getMessage());
            }
        }

        Notification::make()
            ->title('User Created Successfully')
            ->body("User {$user->name} has been created. A temporary password was issued, and they must change it upon first login.")
            ->success()
            ->send();
    }
}
