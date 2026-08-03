<?php

namespace App\Services;

use App\Models\BkashTransaction;
use App\Models\NotificationOutbox;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
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

        // Attempt immediate dispatch asynchronously or log outbox
        Log::info("Notification Outbox Created [{$eventType}]: {$fileName}");

        return $outbox;
    }
}
