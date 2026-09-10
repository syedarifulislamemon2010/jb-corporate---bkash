<?php

namespace App\Services;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\NotificationOutbox;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Check whether an organization string belongs to Janata Bank.
     */
    public static function isJanataBank(?string $org): bool
    {
        if (blank($org)) {
            return true; // default organization is Janata Bank
        }
        $lower = strtolower($org);
        return str_contains($lower, 'janata') || str_contains($lower, 'jb');
    }

    /**
     * Check whether an organization string belongs to bKash.
     */
    public static function isBkash(?string $org): bool
    {
        if (blank($org)) {
            return false;
        }
        return str_contains(strtolower($org), 10);
    }

    /**
     * Scope query to users within the same institution/organization.
     * Guarantees strict cross-organization isolation between Janata Bank PLC. and bKash.
     */
    public static function scopeOrganizationUsers(Builder $query, mixed $organization): Builder
    {
        $orgStr = is_string($organization) ? $organization : '';

        if (static::isBkash($orgStr)) {
            // bKash organization users only — Janata Bank users excluded
            return $query->where('organization', 10)
                         ->where('organization', '!=', 1);
        }

        // Otherwise: Janata Bank organization users only — bKash users excluded
        return $query->where(function ($q) use ($organization, $orgStr) {
            $q->where('organization', 1)
              ->orWhereNull('organization');

            if (!empty($orgStr)) {
                $q->orWhere('organization', $orgStr);
            }
        })->where(function ($q) {
            $q->where('organization', 'not like', '%bkash%')
              ->orWhereNull('organization');
        });
    }

    /**
     * Send Database Notifications to users in the same organization excluding the sender, optionally filtered by role.
     */
    public static function sendOrganizationDatabaseNotification(
        string $title,
        string $body,
        ?User $senderUser = null,
        array $roleNames = [],
        string $icon = 'heroicon-o-bell',
        string $color = 'info',
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $category = null
    ): void {
        $sender = $senderUser ?? Auth::user();
        $query = User::query();

        if ($sender) {
            // Exclude the actor who performed the action
            $query->where('id', '!=', $sender->id);
            $org = $sender->getRawOriginal('organization') ?: 1;
        } else {
            $org = 1;
        }

        // Strictly isolate by organization (Janata Bank vs bKash)
        static::scopeOrganizationUsers($query, $org);

        // Filter by role if explicitly provided, otherwise all organization users receive it
        if (!empty($roleNames)) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', $roleNames));
        }

        $recipients = $query->get();

        if ($recipients->isNotEmpty()) {
            $notification = Notification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->color($color);

            if ($category) {
                $notification->viewData(['category' => $category]);
            }

            if ($actionUrl && $actionLabel) {
                $notification->actions([
                    Action::make('view')
                        ->label($actionLabel)
                        ->url($actionUrl)
                        ->button()
                        ->size('xs')
                        ->color($color),
                ]);
            }

            foreach ($recipients as $recipient) {
                try {
                    $recipient->notifyNow($notification->toDatabase());
                } catch (\Throwable $e) {
                    Log::error("Failed to write database notification for User #{$recipient->id}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Dispatch Stage 1: SFTP / Upload File Ingested -> Pending Checker
     */
    public static function dispatchStage1(
        string $fileName,
        int $totalTrn,
        float $totalAmount,
        ?User $senderUser = null,
        array $recipientRoles = []
    ): NotificationOutbox
    {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);
        $uploadTimeStr   = Carbon::now()->timezone('Asia/Dhaka')->format('d M Y, h:i A');
        $todayFilesCount = BkashTransactionBatch::whereDate('create_date', Carbon::today())->count();
        if ($todayFilesCount === 0) {
            $todayFilesCount = 1;
        }

        $body = "Dear Sir/Madam,\n"
              . "File Name: \"{$fileName}\"\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator).\n"
              . "File is pending for Checker. Please Check this file.\n"
              . "Thank you\n"
              . "Best Regards,\n"
              . "JANATA BANK\n\n"
              . "Upload Time: {$uploadTimeStr}\n"
              . "Total Files Uploaded Today: {$todayFilesCount}";

        static::sendOrganizationDatabaseNotification(
            "New bKash Settlement File: {$fileName}",
            "Uploaded at {$uploadTimeStr} | Total Trn: {$totalTrn}, Amount: BDT {$formattedAmount} (File #{$todayFilesCount} today). Pending Authorization.",
            $senderUser,
            $recipientRoles,
            'heroicon-o-arrow-down-tray',
            'warning',
            '/admin/bkash-transactions',
            'Check File →',
            'checker'
        );

        return static::createOutbox('STAGE_1_SFTP', $fileName, $totalTrn, $totalAmount, null, 'ALL_AUTHORIZERS', $body, $senderUser, $recipientRoles);
    }

    /**
     * Dispatch Stage 2: Checked by Checker -> Pending Authorization
     * By default notifies all Janata Bank users (excluding actor), including Authorizer 1 & 2.
     */
    public static function dispatchStage2(
        string $fileName,
        int $totalTrn,
        float $totalAmount,
        string $authorizerName,
        ?User $senderUser = null,
        array $recipientRoles = []
    ): NotificationOutbox {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $body = "Dear Sir/Madam,\n"
              . "File Name: \"{$fileName}\"\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator)\n"
              . "is checked by \"{$authorizerName}\" (Checker name) & is pending for further Authorization/Approval.\n"
              . "Thank you\n"
              . "JANATA BANK";

        static::sendOrganizationDatabaseNotification(
            "Transactions Checked by {$authorizerName}",
            "File: {$fileName} | Total Trn: {$totalTrn}, Amount: BDT {$formattedAmount}. Pending Confirmation.",
            $senderUser,
            $recipientRoles,
            'heroicon-o-shield-check',
            'info',
            '/admin/bkash-transaction-authorizations',
            '1st Authorizer Approval →',
            'authorizer_1'
        );

        return static::createOutbox('STAGE_2_CHECKED', $fileName, $totalTrn, $totalAmount, $authorizerName, 'ALL_CONFIRMERS', $body, $senderUser, $recipientRoles);
    }

    /**
     * Dispatch Stage 3: Authorized by 1st Authorizer -> Pending Further Authorization/Approval
     * By default notifies all Janata Bank users (excluding actor), including Checkers & Authorizer 2.
     */
    public static function dispatchStage3(
        string $fileName,
        int $totalTrn,
        float $totalAmount,
        string $authorizerName1,
        ?User $senderUser = null,
        array $recipientRoles = []
    ): NotificationOutbox {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $body = "Dear Sir/Madam,\n"
              . "File Name: \"{$fileName}\"\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator)\n"
              . "is Authorized by \"{$authorizerName1}\" (First Authorizer's name) & is pending for further Authorization/Approval or final authorization.\n"
              . "Thank you\n"
              . "JANATA BANK";

        static::sendOrganizationDatabaseNotification(
            "Transactions 1st Authorized by {$authorizerName1}",
            "File: {$fileName} | Total Trn: {$totalTrn}, Amount: BDT {$formattedAmount}. Pending Final Authorization.",
            $senderUser,
            $recipientRoles,
            'heroicon-o-key',
            'primary',
            '/admin/bkash-transaction-confirmations',
            'Final Confirmation →',
            'authorizer_2'
        );

        return static::createOutbox('STAGE_3_AUTH1', $fileName, $totalTrn, $totalAmount, $authorizerName1, 'ALL_AUTHORIZERS_2', $body, $senderUser, $recipientRoles);
    }

    /**
     * Dispatch Stage 4: Authorized by 2nd Authorizer -> Finally Authorized
     * By default notifies all Janata Bank users (excluding actor), including Checkers & Authorizer 1.
     */
    public static function dispatchStage4(
        string $fileName,
        int $totalTrn,
        float $totalAmount,
        string $confirmerName,
        ?User $senderUser = null,
        array $recipientRoles = []
    ): NotificationOutbox {
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $body = "Dear Sir/Madam,\n"
              . "File Name: \"{$fileName}\"\n"
              . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator)\n"
              . "is Authorized by \"{$confirmerName}\" (Second Authorizer's name) & is finally authorized.\n"
              . "Thank you\n"
              . "JANATA BANK";

        static::sendOrganizationDatabaseNotification(
            "Final Confirmation Completed by {$confirmerName}",
            "File: {$fileName} | Total Trn: {$totalTrn}, Amount: BDT {$formattedAmount}. Settled.",
            $senderUser,
            $recipientRoles,
            'heroicon-o-check-badge',
            'success',
            '/admin/bkash-transactions',
            'View Transactions →',
            'authorizer_2'
        );

        return static::createOutbox('STAGE_4_AUTH2', $fileName, $totalTrn, $totalAmount, $confirmerName, 'ALL_USERS', $body, $senderUser, $recipientRoles);
    }

    /**
     * Dispatch workflow stage notification dynamically from transaction record and actor.
     */
    public static function dispatchWorkflowNotification(
        int $stage,
        BkashTransaction $transaction,
        string $actorName,
        int|string|null $actorId = null
    ): ?NotificationOutbox {
        $fileName = $transaction->file_name ?? 'Batch';
        $batch = BkashTransactionBatch::where('file_name', $fileName)->first();
        $totalTrn = $batch?->total_transactions ?? BkashTransaction::where('file_name', $fileName)->count();
        $totalAmount = (float) ($batch?->total_amount ?? BkashTransaction::where('file_name', $fileName)->sum('amount'));
        $actor = $actorId ? User::find($actorId) : Auth::user();

        return match ($stage) {
            1 => static::dispatchStage1($fileName, $totalTrn, $totalAmount, $actor),
            2 => static::dispatchStage2($fileName, $totalTrn, $totalAmount, $actorName, $actor),
            3 => static::dispatchStage3($fileName, $totalTrn, $totalAmount, $actorName, $actor),
            4 => static::dispatchStage4($fileName, $totalTrn, $totalAmount, $actorName, $actor),
            default => null,
        };
    }

    private static function createOutbox(
        string $eventType,
        string $fileName,
        int $totalTrn,
        float $totalAmount,
        ?string $actorName,
        string $recipientGroup,
        string $messageText,
        ?User $senderUser = null,
        array $roleNames = []
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

        $excludeUserId = $senderUser?->id;
        $organization  = $senderUser?->getRawOriginal('organization') ?? $senderUser?->organization_id;

        // Send actual email notifications (scoped to org & roles, excluding actor)
        static::sendActualEmails($outbox, $recipientGroup, $messageText, $fileName, $eventType, $organization, $excludeUserId, $roleNames);

        // Send SMS notifications using exact registered bank template types (14 to 17)
        static::sendActualSms($outbox, $recipientGroup, $eventType, $fileName, $totalTrn, BkashTransaction::formatBdtAmount($totalAmount), $actorName, $organization, $excludeUserId, $roleNames);

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
        string $eventType,
        mixed $organization = null,
        ?int $excludeUserId = null,
        array $roleNames = []
    ): void {
        if (!config('bkash.email_enabled', true)) {
            return;
        }

        try {
            $recipients = static::getRecipientEmails($organization, $excludeUserId, $roleNames);

            if (empty($recipients)) {
                Log::warning("No email recipients found for group: {$recipientGroup} (org: {$organization}, excluded: {$excludeUserId})");
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
        ?string $actorName = null,
        mixed $organization = null,
        ?int $excludeUserId = null,
        array $roleNames = []
    ): void {
        if (!config('bkash.sms_enabled', true)) {
            Log::info('SMS sending is disabled. Skipping SMS dispatch.');
            return;
        }

        try {
            $phones = static::getRecipientPhones($organization, $excludeUserId, $roleNames);

            if (empty($phones)) {
                Log::info("No recipient phone numbers found for group [{$recipientGroup}]. Skipping SMS.");
                return;
            }

            // Map event type to bank template type
            $templateType = match ($eventType) {
                'STAGE_1_SFTP'    => 14,
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
     * Get recipient email addresses with organization scoping, actor exclusion, and role filtering.
     */
    public static function getRecipientEmails(mixed $organization = null, ?int $excludeUserId = null, array $roleNames = []): array
    {
        $query = User::query()->whereNotNull('email');
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        if (!empty($organization)) {
            $query->where(function ($q) use ($organization) {
                $q->where('organization', $organization);
            });
        }
        if (!empty($roleNames)) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', $roleNames));
        }
        return $query->pluck('email')->filter()->unique()->toArray();
    }

    /**
     * Get recipient phone numbers with organization scoping, actor exclusion, and role filtering.
     */
    public static function getRecipientPhones(mixed $organization = null, ?int $excludeUserId = null, array $roleNames = []): array
    {
        $query = User::query();
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        if (!empty($organization)) {
            $query->where(function ($q) use ($organization) {
                $q->where('organization', $organization);
            });
        }
        if (!empty($roleNames)) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', $roleNames));
        }

        $phones = $query->whereNotNull('mobile_no')->pluck('mobile_no')->toArray();
        return array_values(array_unique(array_filter($phones)));
    }
}
