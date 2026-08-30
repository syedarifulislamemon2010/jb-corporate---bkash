<?php

namespace App\Filament\Pages;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\EftReturn;
use App\Models\NotificationOutbox;
use Filament\Pages\Page;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Dashboard extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'bKash Settlement Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    /**
     * Whether auto-refresh polling (15s interval) is enabled (default: true).
     */
    public bool $autoRefresh = true;

    /**
     * Refresh dashboard data (called by wire:poll.15s or manual Refresh button).
     * Livewire triggers a full re-render of computed properties on invocation.
     */
    public function refreshData(): void
    {
        // Livewire re-renders the component and updates all statistics dynamically.
    }

    /**
     * Compute "Last synced" time dynamically from the latest activity across key tables.
     */
    public function getLastSynced(): array
    {
        $timestamps = [
            BkashTransactionBatch::latest('created_at')->value('created_at'),
            BkashTransaction::latest('created_at')->value('created_at'),
            NotificationOutbox::latest('created_at')->value('created_at'),
        ];

        $validTimestamps = array_filter($timestamps);

        if (empty($validTimestamps)) {
            $latestSyncDate = Carbon::now()->timezone('Asia/Dhaka');
        } else {
            $latestSyncDate = collect($validTimestamps)
                ->map(fn($ts) => Carbon::parse($ts)->timezone('Asia/Dhaka'))
                ->max();
        }

        $diffInMinutes = Carbon::now()->timezone('Asia/Dhaka')->diffInMinutes($latestSyncDate);

        return [
            'raw'        => $latestSyncDate,
            'diff'       => $latestSyncDate->diffForHumans(),
            'formatted'  => $latestSyncDate->format('d M Y, h:i:s A'),
            'is_delayed' => $diffInMinutes >= 20,
        ];
    }

    /**
     * Get Urgency Banner counts (3-tier pipeline).
     */
    public function getUrgencyBanner(): ?array
    {
        $pendingCheckerFiles = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
            ->distinct('batch_id')
            ->count('batch_id');
        $pendingAuth1Files   = BkashTransaction::where('status_id', BkashTransaction::STATUS_CHECKED)
            ->distinct('batch_id')
            ->count('batch_id');
        $pendingAuth2Files   = BkashTransaction::where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED)
            ->distinct('batch_id')
            ->count('batch_id');

        $totalUrgent = $pendingCheckerFiles + $pendingAuth1Files + $pendingAuth2Files;
        if ($totalUrgent <= 0) {
            return null;
        }

        return [
            'total'           => $totalUrgent,
            'pending_checker' => $pendingCheckerFiles,
            'pending_auth1'   => $pendingAuth1Files,
            'pending_auth2'   => $pendingAuth2Files,
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
            $pendingChecker = BkashTransaction::where('transaction_type', $channel)
                ->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
            $pendingAuth = BkashTransaction::where('transaction_type', $channel)
                ->whereIn('status_id', [BkashTransaction::STATUS_CHECKED, BkashTransaction::STATUS_AUTH_1_APPROVED])->count();
            $settledToday = BkashTransaction::where('transaction_type', $channel)
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
     * Get Action Row stats (file-level counts for 3-tier pipeline derived from live transactions).
     */
    public function getActionStats(): array
    {
        $pendingCheckerFiles = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
            ->distinct('batch_id')
            ->count('batch_id');
        $pendingCheckerTrns  = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();

        $pendingAuth1Files = BkashTransaction::where('status_id', BkashTransaction::STATUS_CHECKED)
            ->distinct('batch_id')
            ->count('batch_id');
        $pendingAuth1Trns  = BkashTransaction::where('status_id', BkashTransaction::STATUS_CHECKED)->count();

        $pendingAuth2Files = BkashTransaction::where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED)
            ->distinct('batch_id')
            ->count('batch_id');
        $pendingAuth2Trns  = BkashTransaction::where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED)->count();

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
                'files'        => $pendingCheckerFiles,
                'trns'         => $pendingCheckerTrns,
                'url'          => '/admin/bkash-transactions',
                'empty_label'  => 'All clear — no files awaiting check',
                'action_label' => 'Verify & check files →',
            ],
            'pending_auth1' => [
                'files'        => $pendingAuth1Files,
                'trns'         => $pendingAuth1Trns,
                'url'          => '/admin/bkash-transaction-authorizations',
                'empty_label'  => 'All clear — no files awaiting 1st auth',
                'action_label' => '1st Authorizer approval →',
            ],
            'pending_auth2' => [
                'files'        => $pendingAuth2Files,
                'trns'         => $pendingAuth2Trns,
                'url'          => '/admin/bkash-transaction-confirmations',
                'empty_label'  => 'All clear — no files awaiting final auth',
                'action_label' => 'Final confirmation & settle →',
            ],
            'settled_today' => [
                'amount'       => $settledTodayAmount,
                'count'        => $settledTodayCount,
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
                'is_clean'    => true,
                'headline'    => 'Clean run today — 0 failed records',
                'subtext'     => 'All ingested files processed without format or routing errors.',
                'action_label'=> null,
                'action_url'  => null,
            ];
        }

        return [
            'count'       => $failedCount,
            'is_clean'    => false,
            'headline'    => "{$failedCount} " . Str::plural('transaction', $failedCount) . " flagged today",
            'subtext'     => 'Invalid routing, missing account details, or duplicate records require review.',
            'action_label'=> 'View Error Report →',
            'action_url'  => '/admin/bkash-reports',
        ];
    }

    /**
     * Get Account Balances.
     */
    public function getBalances(): array
    {
        $todayValueDate = Carbon::now()->timezone('Asia/Dhaka')->format('d M Y');

        return [
            'tcsa' => [
                'name'         => 'bKash Settlement Account (TCSA)',
                'label'        => 'Pool Account',
                'account'      => '0100202707747',
                'balance'      => $this->calculateBalance('0100202707747'),
                'is_low'       => false,
                'badge'        => 'Main Pool',
                'badge_color'  => 'info',
                'value_date'   => $todayValueDate,
            ],
            'ops' => [
                'name'         => 'Janata Operational Account',
                'label'        => 'Operational Reserve',
                'account'      => '0100224107522',
                'balance'      => $this->calculateBalance('0100224107522'),
                'is_low'       => false,
                'badge'        => 'Ops Reserve',
                'badge_color'  => 'success',
                'value_date'   => $todayValueDate,
                'change_pct'   => '0.0',
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
            $latestLog = null;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('mt940_delivery_logs')) {
                    $latestLog = \App\Models\Mt940DeliveryLog::where('account_no', $accNo)
                        ->latest('delivered_at')
                        ->first();
                }
            } catch (\Throwable $e) {
                $latestLog = null;
            }

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
                'STAGE_2_CHECKED' => "Checked by {$n->actor_name} — pending 1st authorization ({$n->file_name})",
                'STAGE_3_AUTH1'   => "Authorized by {$n->actor_name} (1st Auth) — pending final authorization ({$n->file_name})",
                'STAGE_4_AUTH2'   => "Authorized by {$n->actor_name} (2nd Auth) — finally authorized ({$n->file_name})",
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
            // NOTE: 'credit_account_no' DB column actually stores the TCSA/Operational 
            // (source/debit) account number from the Excel "Debit Account" column — 
            // naming is inverted from its literal meaning but used consistently across 
            // the codebase (parser, dashboard balance calc, reports). Do NOT rename 
            // without updating all dependent code.
            $totalDebited = (float) BkashTransaction::where('credit_account_no', $accountNumber)
                ->whereIn('status_id', [
                    BkashTransaction::STATUS_FINAL_AUTHORIZED,
                    BkashTransaction::STATUS_CBS_SUCCESS,
                ])
                ->sum('amount');

            $balances = config('bkash.initial_balances', []);
            $initialBalance = (float) ($balances[$accountNumber] ?? 0.00);

            return max(0.0, $initialBalance - $totalDebited);
        } catch (\Throwable $e) {
            return 0.00;
        }
    }
}
