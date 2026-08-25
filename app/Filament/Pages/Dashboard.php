<?php

namespace App\Filament\Pages;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\EftReturn;
use App\Models\NotificationOutbox;
use Carbon\Carbon;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Log;

class Dashboard extends BaseDashboard
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $title = '';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.dashboard';

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return '';
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function getFooterWidgets(): array
    {
        return [];
    }

    public function getColumns(): int | array
    {
        return 1;
    }

    /**
     * Get SFTP Last Synced status & timestamp with date/month/year/time(h:m:s).
     */
    public function getLastSynced(): array
    {
        $latestBatchDate = BkashTransactionBatch::latest('created_at')->value('created_at');
        $latestTxnDate   = BkashTransaction::latest('created_at')->value('created_at');
        $latestNotifDate = NotificationOutbox::latest('created_at')->value('created_at');

        $latestSyncDate = collect([$latestBatchDate, $latestTxnDate, $latestNotifDate])
            ->filter()
            ->map(fn ($d) => $d instanceof Carbon ? $d : Carbon::parse($d))
            ->max();

        if (!$latestSyncDate) {
            return [
                'formatted'  => 'No sync yet',
                'is_delayed' => false,
            ];
        }

        $diffInMinutes = (int) $latestSyncDate->diffInMinutes(now());

        return [
            'formatted'  => $latestSyncDate->format('d M Y, h:i:s A'),
            'is_delayed' => $diffInMinutes >= 20,
        ];
    }

    /**
     * Get Urgency Banner counts.
     */
    public function getUrgencyBanner(): ?array
    {
        $pendingCheckerFiles = BkashTransactionBatch::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
        $pendingAuthFiles    = BkashTransactionBatch::whereIn('status_id', [
            BkashTransaction::STATUS_CHECKED,
            BkashTransaction::STATUS_AUTH_1_APPROVED,
        ])->count();

        $totalUrgent = $pendingCheckerFiles + $pendingAuthFiles;
        if ($totalUrgent <= 0) {
            return null;
        }

        return [
            'total'           => $totalUrgent,
            'pending_checker' => $pendingCheckerFiles,
            'pending_auth'    => $pendingAuthFiles,
        ];
    }

    /**
     * Get Channel breakdown stats for phased rollout (A2A, BEFTN, RTGS).
     */
    public function getChannelStats(): array
    {
        $enabled = config('bkash.enabled_channels', ['A2A', 'BEFTN', 'RTGS']);
        $channels = ['A2A', 'BEFTN', 'RTGS'];
        $stats = [];

        foreach ($channels as $channel) {
            $pendingChecker = BkashTransactionBatch::where('transaction_type', $channel)
                ->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
            $pendingAuth = BkashTransactionBatch::where('transaction_type', $channel)
                ->whereIn('status_id', [BkashTransaction::STATUS_CHECKED, BkashTransaction::STATUS_AUTH_1_APPROVED])->count();
            $settledToday = BkashTransactionBatch::where('transaction_type', $channel)
                ->whereIn('status_id', [BkashTransaction::STATUS_FINAL_AUTHORIZED, BkashTransaction::STATUS_CBS_SUCCESS])
                ->whereDate('updated_at', today())->count();

            $stats[$channel] = [
                'is_live'         => in_array($channel, $enabled),
                'pending_checker' => $pendingChecker,
                'pending_auth'    => $pendingAuth,
                'settled_today'   => $settledToday,
                'label'           => match($channel) {
                    'A2A'   => 'Live',
                    'BEFTN' => 'Live',
                    'RTGS'  => 'Live',
                },
            ];
        }

        return $stats;
    }

    /**
     * Get Action Row stats (file-level counts).
     */
    public function getActionStats(): array
    {
        $pendingCheckerFiles = BkashTransactionBatch::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
        $pendingCheckerTrns  = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();

        $pendingAuth1Files = BkashTransactionBatch::where('status_id', BkashTransaction::STATUS_CHECKED)->count();
        $pendingAuth2Files = BkashTransactionBatch::where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED)->count();
        $totalPendingAuth  = $pendingAuth1Files + $pendingAuth2Files;

        $settledTodayAmount = (float) BkashTransaction::whereIn('status_id', [
            BkashTransaction::STATUS_FINAL_AUTHORIZED,
            BkashTransaction::STATUS_CBS_SUCCESS,
        ])->whereDate('updated_at', today())->sum('amount');

        $settledTodayCount = BkashTransaction::whereIn('status_id', [
            BkashTransaction::STATUS_FINAL_AUTHORIZED,
            BkashTransaction::STATUS_CBS_SUCCESS,
        ])->whereDate('updated_at', today())->count();

        return [
            'pending_checker' => [
                'files'       => $pendingCheckerFiles,
                'trns'        => $pendingCheckerTrns,
                'url'         => '/admin/bkash-transactions',
            ],
            'pending_auth' => [
                'files'       => $totalPendingAuth,
                'auth1_files' => $pendingAuth1Files,
                'auth2_files' => $pendingAuth2Files,
                'url'         => '/admin/bkash-transaction-authorizations',
            ],
            'settled_today' => [
                'amount'      => $settledTodayAmount,
                'count'       => $settledTodayCount,
            ],
        ];
    }

    /**
     * Get Exceptions stat (failed / partial transactions today).
     */
    public function getExceptionsStat(): array
    {
        $failedCount = BkashFailedTransaction::whereDate('created_at', today())->count();

        if ($failedCount === 0) {
            return [
                'count'       => 0,
                'description' => 'No failed transactions today',
                'is_clean'    => true,
            ];
        }

        $a2aFailed   = BkashFailedTransaction::where('transaction_type', 'A2A')->whereDate('created_at', today())->count();
        $beftnFailed = BkashFailedTransaction::where('transaction_type', 'BEFTN')->whereDate('created_at', today())->count();
        $rtgsFailed  = BkashFailedTransaction::where('transaction_type', 'RTGS')->whereDate('created_at', today())->count();

        $parts = [];
        if ($a2aFailed > 0) $parts[] = "{$a2aFailed} A2A";
        if ($beftnFailed > 0) $parts[] = "{$beftnFailed} BEFTN";
        if ($rtgsFailed > 0) $parts[] = "{$rtgsFailed} RTGS";

        return [
            'count'       => $failedCount,
            'description' => implode(' · ', $parts) ?: "{$failedCount} failed today",
            'is_clean'    => false,
        ];
    }

    /**
     * Get TCSA and Operational Account Balances.
     */
    public function getBalances(): array
    {
        $tcsaAccount = '0100202707747';
        $opsAccount  = '0100224107522';

        $tcsaBalance = $this->calculateBalance($tcsaAccount);
        $opsBalance  = $this->calculateBalance($opsAccount);

        return [
            'tcsa' => [
                'account'    => $tcsaAccount,
                'label'      => 'Trust cum settlement account',
                'balance'    => $tcsaBalance,
                'value_date' => Carbon::now()->format('d M Y'),
                'sparkline'  => [35, 42, 40, 50, 48, 55, 60, 64],
            ],
            'ops' => [
                'account'    => $opsAccount,
                'label'      => 'Operational account',
                'balance'    => $opsBalance,
                'change_pct' => 2.1,
            ],
        ];
    }

    /**
     * Get MT940 statement delivery status per account from actual delivery logs.
     */
    public function getMt940Status(): array
    {
        $accounts = [
            '0100202707747' => '0100202707747 (TCSA)',
            '0100224107522' => '0100224107522 (Ops)',
        ];

        $statuses = [];

        foreach ($accounts as $accNo => $label) {
            $latestLog = \App\Models\Mt940DeliveryLog::where('account_no', $accNo)
                ->latest('delivered_at')
                ->first();

            if ($latestLog) {
                $statuses[] = [
                    'account'   => $label,
                    'timestamp' => $latestLog->delivered_at ? $latestLog->delivered_at->format('h:i A') : $latestLog->created_at->format('h:i A'),
                    'status'    => $latestLog->status,
                    'is_ok'     => (bool) $latestLog->is_ok,
                ];
            } else {
                $statuses[] = [
                    'account'   => $label,
                    'timestamp' => 'Pending',
                    'status'    => 'Pending first delivery',
                    'is_ok'     => false,
                ];
            }
        }

        return $statuses;
    }

    /**
     * Get Recent Activities matching 4-stage notification vocabulary.
     */
    public function getRecentActivities(): array
    {
        $activities = [];

        // 1. Fetch outbox notifications
        $notifications = NotificationOutbox::latest()->take(6)->get();
        foreach ($notifications as $n) {
            $stageLabel = match ($n->event_type) {
                'STAGE_1_SFTP'    => "File received — pending checker ({$n->file_name})",
                'STAGE_2_CHECKED' => "Checked by {$n->actor_name} — pending authorization ({$n->file_name})",
                'STAGE_3_AUTH1'   => "Authorized by {$n->actor_name} (Auth 1) — pending final authorization ({$n->file_name})",
                'STAGE_4_AUTH2'   => "Authorized by {$n->actor_name} (Auth 2) — finally authorized ({$n->file_name})",
                default           => "Notification sent for {$n->file_name}",
            };

            $icon = match ($n->event_type) {
                'STAGE_1_SFTP'    => 'heroicon-o-inbox-arrow-down',
                'STAGE_2_CHECKED' => 'heroicon-o-shield-check',
                'STAGE_3_AUTH1'   => 'heroicon-o-key',
                'STAGE_4_AUTH2'   => 'heroicon-o-check-badge',
                default           => 'heroicon-o-bell',
            };

            $color = match ($n->event_type) {
                'STAGE_4_AUTH2' => 'text-emerald-500 dark:text-emerald-400',
                default         => 'text-sky-500 dark:text-sky-400',
            };

            $activities[] = [
                'title'     => $stageLabel,
                'time'      => $n->created_at->format('d M Y, h:i:s A'),
                'icon'      => $icon,
                'color'     => $color,
            ];
        }

        // 2. Fetch EFT Returns
        $eftReturns = EftReturn::latest()->take(2)->get();
        foreach ($eftReturns as $eft) {
            $activities[] = [
                'title' => "EFT return processed, ref {$eft->reference_id}",
                'time'  => $eft->created_at ? $eft->created_at->format('d M Y, h:i:s A') : Carbon::now()->format('d M Y, h:i:s A'),
                'icon'  => 'heroicon-o-arrow-uturn-left',
                'color' => 'text-amber-500 dark:text-amber-400',
            ];
        }

        return array_slice($activities, 0, 8);
    }

    private function calculateBalance(string $accountNumber): float
    {
        try {
            $totalDebited = (float) BkashTransaction::where('credit_account_no', $accountNumber)
                ->whereIn('status_id', [
                    BkashTransaction::STATUS_FINAL_AUTHORIZED,
                    BkashTransaction::STATUS_CBS_SUCCESS,
                ])
                ->sum('amount');

            $balances = config('bkash.initial_balances', []);
            $initialBalance = (float) ($balances[$accountNumber] ?? 0.00);

            return max(0, $initialBalance - $totalDebited);
        } catch (\Throwable $e) {
            Log::error("Failed to calculate balance for {$accountNumber}: " . $e->getMessage());
            return 0.00;
        }
    }
}
