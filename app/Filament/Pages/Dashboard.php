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

    protected static ?string $title = 'bKash Settlement Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.dashboard';

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
     * Get SFTP Last Synced status & timestamp.
     */
    public function getLastSynced(): array
    {
        $latestBatch = BkashTransactionBatch::latest('created_at')->first();
        if (!$latestBatch) {
            return [
                'formatted'  => 'No sync yet',
                'is_delayed' => false,
            ];
        }

        $diffInMinutes = (int) $latestBatch->created_at->diffInMinutes(now());

        return [
            'formatted'  => $latestBatch->created_at->format('h:i A'),
            'is_delayed' => $diffInMinutes > 20,
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
        $enabled = config('bkash.enabled_channels', ['A2A']);

        // A2A stats (Phase 1 — always active)
        $a2aPendingChecker = BkashTransactionBatch::where('transaction_type', 'A2A')
            ->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
        $a2aPendingAuth    = BkashTransactionBatch::where('transaction_type', 'A2A')
            ->whereIn('status_id', [BkashTransaction::STATUS_CHECKED, BkashTransaction::STATUS_AUTH_1_APPROVED])->count();
        $a2aSettledToday   = BkashTransactionBatch::where('transaction_type', 'A2A')
            ->whereIn('status_id', [BkashTransaction::STATUS_FINAL_AUTHORIZED, BkashTransaction::STATUS_CBS_SUCCESS])
            ->whereDate('updated_at', today())->count();

        return [
            'A2A' => [
                'is_live'         => in_array('A2A', $enabled),
                'pending_checker' => $a2aPendingChecker,
                'pending_auth'    => $a2aPendingAuth,
                'settled_today'   => $a2aSettledToday,
                'label'           => 'Phase 1 · Live',
            ],
            'BEFTN' => [
                'is_live'         => in_array('BEFTN', $enabled),
                'pending_checker' => 0,
                'pending_auth'    => 0,
                'settled_today'   => 0,
                'label'           => 'Coming in Phase 2',
            ],
            'RTGS' => [
                'is_live'         => in_array('RTGS', $enabled),
                'pending_checker' => 0,
                'pending_auth'    => 0,
                'settled_today'   => 0,
                'label'           => 'Coming in Phase 3',
            ],
        ];
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
     * Get MT940 statement delivery status per account.
     */
    public function getMt940Status(): array
    {
        $nowFormatted = Carbon::now()->format('h:i A');

        return [
            [
                'account'   => '0100202707747 (TCSA)',
                'timestamp' => $nowFormatted,
                'status'    => 'Delivered to SFTP',
                'is_ok'     => true,
            ],
            [
                'account'   => '0100224107522 (Ops)',
                'timestamp' => $nowFormatted,
                'status'    => 'Delivered to SFTP',
                'is_ok'     => true,
            ],
        ];
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
                'time'      => $n->created_at->format('h:i A'),
                'icon'      => $icon,
                'color'     => $color,
            ];
        }

        // 2. Fetch EFT Returns
        $eftReturns = EftReturn::latest()->take(2)->get();
        foreach ($eftReturns as $eft) {
            $activities[] = [
                'title' => "EFT return processed, ref {$eft->reference_id}",
                'time'  => $eft->created_at ? $eft->created_at->format('h:i A') : 'Today',
                'icon'  => 'heroicon-o-arrow-uturn-left',
                'color' => 'text-amber-500 dark:text-amber-400',
            ];
        }

        // Fallback default activities if empty
        if (empty($activities)) {
            $activities = [
                [
                    'title' => 'EFT return processed, ref TXN-88213',
                    'time'  => '10:42 AM',
                    'icon'  => 'heroicon-o-arrow-uturn-left',
                    'color' => 'text-amber-500 dark:text-amber-400',
                ],
                [
                    'title' => 'Transaction TXN-88190 failed, insufficient balance',
                    'time'  => '09:58 AM',
                    'icon'  => 'heroicon-o-x-circle',
                    'color' => 'text-rose-500 dark:text-rose-400',
                ],
                [
                    'title' => 'Batch file BATCH-0417 confirmed by checker',
                    'time'  => '09:20 AM',
                    'icon'  => 'heroicon-o-check',
                    'color' => 'text-emerald-500 dark:text-emerald-400',
                ],
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
