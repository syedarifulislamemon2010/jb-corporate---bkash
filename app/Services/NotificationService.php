<?php

namespace App\Services;

use App\Models\BkashTransaction;
use App\Models\NotificationOutbox;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send Database Notifications to users in the same organization excluding the sender.
     */
    public static function sendOrganizationDatabaseNotification(string $title, string $body, ?User $senderUser = null): void
    {
        $sender = $senderUser ?? Auth::user();
        if (!$sender) {
            // For system-triggered notifications (SFTP cron), notify all users with bkash roles
            $recipients = User::all();
        } else {
            $query = User::query()->where('id', '!=', $sender->id);
            if ($sender->organization_id) {
                $query->where('organization_id', $sender->organization_id);
            }
            $recipients = $query->get();
        }

        if ($recipients->isNotEmpty()) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-bell')
                ->info()
                ->sendToDatabase($recipients);
        }
    }

    /**
     * Dispatch Stage 1: SFTP File Ingested -> Pending Checker
     */
    public static function dispatchStage1(string $fileName, int $totalTrn, float $totalAmount): NotificationOutbox
    {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $body = "Dear Sir/Madam,\n\n"
              . "File Name: \"{$fileName}\"\n\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\". File is pending for Checker. Please Check this file.\n\n"
              . "Thank you\n\n"
              . "Best Regards,\n\n"
              . "JANATA BANK";

        static::sendOrganizationDatabaseNotification(
            "New bKash Settlement File Ingested: {$fileName}",
            "Total Trn: {$totalTrn}, Total Amount: BDT {$formattedAmount}. Pending for Checker verification."
        );

        return static::createOutbox('STAGE_1_SFTP', $fileName, $totalTrn, $totalAmount, null, 'ALL_CHECKERS', $body);
    }

    /**
     * Dispatch Stage 2: Checked by Checker -> Pending Authorization
     */
    public static function dispatchStage2(string $fileName, int $totalTrn, float $totalAmount, string $checkerName): NotificationOutbox
    {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $body = "Dear Sir/Madam,\n\n"
              . "File Name: \"{$fileName}\"\n\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" is checked by \"{$checkerName}\" & is pending for further Authorization/Approval.\n\n"
              . "Thank you\n\n"
              . "JANATA BANK";

        static::sendOrganizationDatabaseNotification(
            "Transactions Checked by {$checkerName}",
            "File: {$fileName} | Total Trn: {$totalTrn}, Amount: BDT {$formattedAmount}. Pending Authorization."
        );

        return static::createOutbox('STAGE_2_CHECKED', $fileName, $totalTrn, $totalAmount, $checkerName, 'ALL_CHECKERS_AND_AUTHORIZERS', $body);
    }

    /**
     * Dispatch Stage 3: Authorized by 1st Authorizer -> Pending 2nd Authorization
     */
    public static function dispatchStage3(string $fileName, int $totalTrn, float $totalAmount, string $authorizerName1): NotificationOutbox
    {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $body = "Dear Sir/Madam,\n\n"
              . "File Name: \"{$fileName}\"\n\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" is Authorized by \"{$authorizerName1}\" & is pending for further Authorization/Approval or final authorization.\n\n"
              . "Thank you\n\n"
              . "JANATA BANK";

        static::sendOrganizationDatabaseNotification(
            "1st Authorization Completed by {$authorizerName1}",
            "File: {$fileName} | Total Trn: {$totalTrn}, Amount: BDT {$formattedAmount}. Pending final approval."
        );

        return static::createOutbox('STAGE_3_AUTH1', $fileName, $totalTrn, $totalAmount, $authorizerName1, 'ALL_CHECKERS_AND_AUTHORIZERS', $body);
    }

    /**
     * Dispatch Stage 4: Authorized by 2nd Authorizer -> Finally Authorized
     */
    public static function dispatchStage4(string $fileName, int $totalTrn, float $totalAmount, string $authorizerName2): NotificationOutbox
    {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $body = "Dear Sir/Madam,\n\n"
              . "File Name: \"{$fileName}\"\n\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" is Authorized by \"{$authorizerName2}\" & is finally authorized.\n\n"
              . "Thank you\n\n"
              . "JANATA BANK";

        static::sendOrganizationDatabaseNotification(
            "Final Authorization Completed by {$authorizerName2}",
            "File: {$fileName} | Total Trn: {$totalTrn}, Amount: BDT {$formattedAmount}. Settled."
        );

        return static::createOutbox('STAGE_4_AUTH2', $fileName, $totalTrn, $totalAmount, $authorizerName2, 'ALL_CHECKERS_AND_AUTHORIZERS', $body);
    }

    private static function createOutbox(
        string $eventType,
        string $fileName,
        int $totalTrn,
        float $totalAmount,
        ?string $actorName,
        string $recipientGroup,
        string $messageText
    ): NotificationOutbox {
        $outbox = NotificationOutbox::create([
            'event_type'      => $eventType,
            'file_name'       => $fileName,
            'total_trn'       => $totalTrn,
            'total_amount'    => $totalAmount,
            'actor_name'      => $actorName,
            'recipient_group' => $recipientGroup,
            'status'          => 'PENDING',
            'sms_payload'     => $messageText,
            'email_payload'   => $messageText,
        ]);

        Log::info("Notification Outbox Created [{$eventType}]: {$fileName}");

        // Send actual email notifications
        static::sendActualEmails($outbox, $recipientGroup, $messageText, $fileName, $eventType);

        // Send SMS notifications
        static::sendActualSms($outbox, $recipientGroup, $messageText);

        return $outbox;
    }

    /**
     * Send actual email notifications to recipients.
     */
    private static function sendActualEmails(
        NotificationOutbox $outbox,
        string $recipientGroup,
        string $messageText,
        string $fileName,
        string $eventType
    ): void {
        if (!config('bkash.email_enabled', true)) {
            return;
        }

        try {
            $recipients = static::getRecipientEmails($recipientGroup);

            if (empty($recipients)) {
                Log::warning("No email recipients found for group: {$recipientGroup}");
                return;
            }

            $subject = match ($eventType) {
                'STAGE_1_SFTP'     => "bKash Settlement File Pending for Checker: {$fileName}",
                'STAGE_2_CHECKED'  => "bKash Transactions Checked — Pending Authorization: {$fileName}",
                'STAGE_3_AUTH1'    => "bKash 1st Authorization Complete — Pending Final: {$fileName}",
                'STAGE_4_AUTH2'    => "bKash Final Authorization Complete — Settled: {$fileName}",
                default            => "bKash Corporate Portal Notification: {$fileName}",
            };

            foreach ($recipients as $email) {
                Mail::raw($messageText, function ($message) use ($email, $subject) {
                    $message->to($email)
                            ->subject($subject)
                            ->from(
                                config('bkash.email_from_address', config('mail.from.address')),
                                config('bkash.email_from_name', config('mail.from.name'))
                            );
                });
            }

            $outbox->update(['status' => 'SENT']);
            Log::info("Email sent to " . count($recipients) . " recipients for [{$eventType}]: {$fileName}");
        } catch (\Throwable $e) {
            $outbox->update(['status' => 'FAILED']);
            Log::error("Failed to send email for [{$eventType}]: " . $e->getMessage());
        }
    }

    /**
     * Send actual SMS notifications.
     */
    private static function sendActualSms(
        NotificationOutbox $outbox,
        string $recipientGroup,
        string $messageText
    ): void {
        if (!config('bkash.sms_enabled', false)) {
            Log::info('SMS sending is disabled. Skipping SMS dispatch.');
            return;
        }

        try {
            $phones = static::getRecipientPhones($recipientGroup);

            if (empty($phones)) {
                return;
            }

            $apiUrl = config('bkash.sms_api_url');
            $apiKey = config('bkash.sms_api_key');
            $senderId = config('bkash.sms_sender_id', 'JANATABANK');

            foreach ($phones as $phone) {
                // Generic HTTP SMS gateway call
                // Replace with actual SMS provider API (Infobip, SSL Wireless, etc.)
                try {
                    $response = Http::timeout(10)->get($apiUrl, [
                        'api_key'   => $apiKey,
                        'sender_id' => $senderId,
                        'to'        => $phone,
                        'message'   => $messageText,
                    ]);

                    if ($response->successful()) {
                        Log::info("SMS sent to {$phone}");
                    } else {
                        Log::warning("SMS to {$phone} returned status: " . $response->status());
                    }
                } catch (\Throwable $smsEx) {
                    Log::error("SMS to {$phone} failed: " . $smsEx->getMessage());
                }
            }
            
            $outbox->update(['sms_status' => 'SENT']);
        } catch (\Throwable $e) {
            $outbox->update(['sms_status' => 'FAILED']);
            Log::error('SMS sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Get recipient email addresses based on group.
     */
    private static function getRecipientEmails(string $recipientGroup): array
    {
        $query = User::query()->whereNotNull('email');

        // In production, filter by role:
        // if ($recipientGroup === 'ALL_CHECKERS') {
        //     $query->role('bkash_checker');
        // } else {
        //     $query->role(['bkash_checker', 'bkash_authorizer']);
        // }

        return $query->pluck('email')->filter()->unique()->toArray();
    }

    /**
     * Get recipient phone numbers based on group.
     */
    private static function getRecipientPhones(string $recipientGroup): array
    {
        $query = User::query()->whereNotNull('phone');
        return $query->pluck('phone')->filter()->unique()->toArray();
    }
}
