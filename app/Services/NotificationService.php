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

        // Send SMS notifications using exact registered bank template types (14 to 17)
        static::sendActualSms($outbox, $recipientGroup, $eventType, $fileName, $totalTrn, BkashTransaction::formatBdtAmount($totalAmount), $actorName);

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
     * Send actual SMS notifications via Janata Bank SMS API Gateway.
     */
    private static function sendActualSms(
        NotificationOutbox $outbox,
        string $recipientGroup,
        string $eventType,
        string $fileName,
        int $totalTrn,
        string $formattedAmount,
        ?string $actorName = null
    ): void {
        if (!config('bkash.sms_enabled', true)) {
            Log::info('SMS sending is disabled. Skipping SMS dispatch.');
            return;
        }

        try {
            $phones = static::getRecipientPhones($recipientGroup);

            if (empty($phones)) {
                Log::info("No recipient phone numbers found for group [{$recipientGroup}]. Skipping SMS.");
                return;
            }

            // Map event type to bank template type
            $templateType = match ($eventType) {
                'STAGE_1_SFTP'  => 14,
                'STAGE_2_CHECKED' => 15,
                'STAGE_3_AUTH1'   => 16,
                'STAGE_4_AUTH2'   => 17,
                default           => 14,
            };

            foreach ($phones as $phone) {
                try {
                    $response = \App\Helper\SMSGenerateHelper::generate(
                        mobile: $phone,
                        password: (string) ($actorName ?? ''),
                        type: $templateType,
                        account: $fileName,
                        bankbic: (string) $totalTrn,
                        amount: $formattedAmount
                    );
                    Log::info("SMS Gateway: Dispatched Template [Type {$templateType}] to {$phone}");
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
        return $query->pluck('email')->filter()->unique()->toArray();
    }

    /**
     * Get recipient phone numbers based on group (checks mobile_no first, then phone).
     */
    private static function getRecipientPhones(string $recipientGroup): array
    {
        $phones = User::query()->whereNotNull('mobile_no')->pluck('mobile_no')->toArray();
        if (empty($phones)) {
            $phones = User::query()->whereNotNull('phone')->pluck('phone')->toArray();
        }
        return array_values(array_unique(array_filter($phones)));
    }
}
