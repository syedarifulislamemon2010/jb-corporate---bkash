<x-filament-panels::page>
    @php
        $lastSynced   = $this->getLastSynced();
        $urgency      = $this->getUrgencyBanner();
        $channelStats = $this->getChannelStats();
        $actionStats  = $this->getActionStats();
        $exceptions   = $this->getExceptionsStat();
        $balances     = $this->getBalances();
        $mt940        = $this->getMt940Status();
        $activities   = $this->getRecentActivities();
    @endphp

    <div class="db-container" @if ($autoRefresh) wire:poll.15s="refreshData" @endif>

        <!-- 1. HEADER ROW: Sync Status + Auto-refresh Toggle + Refresh Button -->
        <div class="db-header-row">
            <div class="db-flex-gap-3">
                <div class="db-badge-pill {{ $lastSynced['is_delayed'] ? 'db-badge-danger' : 'db-badge-success' }} db-flex-gap-2" role="status" aria-label="Last synchronization status">
                    <span class="db-sync-dot {{ $lastSynced['is_delayed'] ? 'db-sync-dot-delayed' : 'db-sync-dot-ok' }}" aria-hidden="true"></span>
                    <span class="db-tabular">Last synced: {{ $lastSynced['formatted'] }}</span>
                </div>
            </div>

            <div class="db-flex-gap-3 db-flex-center-wrap">
                <!-- Auto-refresh Toggle Switch / Checkbox (Default: ON) -->
                <label class="db-autorefresh-label" aria-label="Toggle auto-refresh">
                    <input 
                        type="checkbox" 
                        wire:model.live="autoRefresh" 
                        class="db-autorefresh-checkbox"
                        aria-label="Enable or disable 15-second dashboard auto-refresh"
                    />
                    <span class="db-autorefresh-badge">
                        <span>Auto-refresh (15s)</span>
                        @if ($autoRefresh)
                            <span class="db-pulse-dot-sm" title="Auto-refresh active" aria-hidden="true"></span>
                        @endif
                    </span>
                </label>

                <!-- Manual Force-Refresh Button -->
                <button 
                    wire:click="refreshData"
                    wire:loading.attr="disabled"
                    type="button"
                    class="db-btn-refresh"
                    aria-label="Refresh dashboard data"
                    title="Refresh dashboard data now"
                >
                    <x-filament::icon 
                        icon="heroicon-o-arrow-path" 
                        class="w-4 h-4 text-slate-400" 
                        wire:loading.class="animate-spin"
                        aria-hidden="true" 
                    />
                    <span wire:loading.remove>Refresh</span>
                    <span wire:loading>Refreshing...</span>
                </button>
            </div>
        </div>

        <!-- 2. URGENCY ACTION BANNER (Conditional) -->
        @if ($urgency)
            <div class="db-banner-warning" role="alert" aria-label="Pending files requiring action">
                <div class="db-urgency-icon-box" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-amber-500" />
                </div>
                <div class="db-urgency-content">
                    <p class="db-urgency-title">
                        <span class="db-tabular">{{ $urgency['total'] }}</span> {{ Str::plural('file', $urgency['total']) }} need your action today: <span class="db-tabular">{{ $urgency['pending_checker'] }}</span> pending checker, <span class="db-tabular">{{ $urgency['pending_auth1'] }}</span> 1st auth, <span class="db-tabular">{{ $urgency['pending_auth2'] }}</span> 2nd auth
                    </p>
                    <p class="db-urgency-desc">
                        Please review and clear pending files across all 3 tiers to complete automated CBS settlement.
                    </p>
                </div>
            </div>
        @endif

        <!-- 3. ACTION ROW CARDS (3-Tier Action-Required Pipeline) -->
        <div class="db-grid-3" role="region" aria-label="Pending approval queues">

            <!-- Card 1: Pending Checker (Tier 1 Action Required) -->
            @if ($actionStats['pending_checker']['files'] > 0)
                <a href="{{ $actionStats['pending_checker']['url'] }}" class="db-card-stage-checker" aria-label="View and verify pending checker files ({{ $actionStats['pending_checker']['files'] }} files, {{ $actionStats['pending_checker']['trns'] }} transactions)">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2 db-stage-checker-theme">
                            <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 db-stage-checker-theme" aria-hidden="true" />
                            <span class="db-text-sub db-stage-header db-stage-checker-theme">Pending checker</span>
                        </div>
                    </div>
                    <div class="db-stage-body">
                        <div class="db-text-val db-tabular">
                            {{ $actionStats['pending_checker']['files'] }}
                        </div>
                        <div class="db-text-sub db-tabular db-stage-sub">{{ $actionStats['pending_checker']['trns'] }} {{ Str::plural('transaction', $actionStats['pending_checker']['trns']) }}</div>
                        <div class="db-link-action db-stage-action db-stage-checker-theme">
                            {{ $actionStats['pending_checker']['action_label'] }}
                        </div>
                    </div>
                </a>
            @else
                <div class="db-card-zero db-card-zero-dimmed" role="region" aria-label="Pending checker: queue is empty">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2 db-stage-empty-muted">
                            <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 text-slate-400" aria-hidden="true" />
                            <span class="db-stage-header db-stage-empty-muted">Pending checker</span>
                        </div>
                    </div>
                    <div class="db-stage-body">
                        <div class="db-text-val db-tabular db-stage-empty-muted">0</div>
                        <div class="db-stage-empty-muted db-stage-sub">{{ $actionStats['pending_checker']['empty_label'] }}</div>
                    </div>
                </div>
            @endif

            <!-- Card 2: Pending 1st Authorization (Tier 2 Action Required) -->
            @if ($actionStats['pending_auth1']['files'] > 0)
                <a href="{{ $actionStats['pending_auth1']['url'] }}" class="db-card-stage-auth1" aria-label="View and approve pending 1st authorization transactions ({{ $actionStats['pending_auth1']['files'] }} files, {{ $actionStats['pending_auth1']['trns'] }} transactions)">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2 db-stage-auth1-theme">
                            <x-filament::icon icon="heroicon-o-key" class="w-5 h-5 db-stage-auth1-theme" aria-hidden="true" />
                            <span class="db-text-sub db-stage-header db-stage-auth1-theme">Pending 1st auth</span>
                        </div>
                    </div>
                    <div class="db-stage-body">
                        <div class="db-text-val db-tabular">
                            {{ $actionStats['pending_auth1']['files'] }}
                        </div>
                        <div class="db-text-sub db-tabular db-stage-sub">{{ $actionStats['pending_auth1']['trns'] }} {{ Str::plural('transaction', $actionStats['pending_auth1']['trns']) }}</div>
                        <div class="db-link-action db-stage-action db-stage-auth1-theme">
                            {{ $actionStats['pending_auth1']['action_label'] }}
                        </div>
                    </div>
                </a>
            @else
                <div class="db-card-zero db-card-zero-dimmed" role="region" aria-label="Pending 1st authorization: queue is empty">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2 db-stage-empty-muted">
                            <x-filament::icon icon="heroicon-o-key" class="w-5 h-5 text-slate-400" aria-hidden="true" />
                            <span class="db-stage-header db-stage-empty-muted">Pending 1st auth</span>
                        </div>
                    </div>
                    <div class="db-stage-body">
                        <div class="db-text-val db-tabular db-stage-empty-muted">0</div>
                        <div class="db-stage-empty-muted db-stage-sub">{{ $actionStats['pending_auth1']['empty_label'] }}</div>
                    </div>
                </div>
            @endif

            <!-- Card 3: Pending Final Confirmation (Tier 3 Action Required) -->
            @if ($actionStats['pending_auth2']['files'] > 0)
                <a href="{{ $actionStats['pending_auth2']['url'] }}" class="db-card-stage-auth2" aria-label="View and confirm pending 2nd authorization transactions ({{ $actionStats['pending_auth2']['files'] }} files, {{ $actionStats['pending_auth2']['trns'] }} transactions)">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2 db-stage-auth2-theme">
                            <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-5 h-5 db-stage-auth2-theme" aria-hidden="true" />
                            <span class="db-text-sub db-stage-header db-stage-auth2-theme">Pending 2nd auth</span>
                        </div>
                    </div>
                    <div class="db-stage-body">
                        <div class="db-text-val db-tabular">
                            {{ $actionStats['pending_auth2']['files'] }}
                        </div>
                        <div class="db-text-sub db-tabular db-stage-sub">{{ $actionStats['pending_auth2']['trns'] }} {{ Str::plural('transaction', $actionStats['pending_auth2']['trns']) }}</div>
                        <div class="db-link-action db-stage-action db-stage-auth2-theme">
                            {{ $actionStats['pending_auth2']['action_label'] }}
                        </div>
                    </div>
                </a>
            @else
                <div class="db-card-zero db-card-zero-dimmed" role="region" aria-label="Pending 2nd authorization: queue is empty">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2 db-stage-empty-muted">
                            <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-5 h-5 text-slate-400" aria-hidden="true" />
                            <span class="db-stage-header db-stage-empty-muted">Pending 2nd auth</span>
                        </div>
                    </div>
                    <div class="db-stage-body">
                        <div class="db-text-val db-tabular db-stage-empty-muted">0</div>
                        <div class="db-stage-empty-muted db-stage-sub">{{ $actionStats['pending_auth2']['empty_label'] }}</div>
                    </div>
                </div>
            @endif

        </div>

        <!-- 4. PHASED ROLLOUT CHANNEL ROW (Tier 3 Informational with Channel Identity Palette) -->
        <div class="db-grid-3" role="region" aria-label="Payment channels status">
            @foreach ($channelStats as $channel => $info)
                @php
                    $channelCardClass = match($channel) {
                        'A2A'   => 'db-card-channel-a2a',
                        'BEFTN' => 'db-card-channel-beftn',
                        'RTGS'  => 'db-card-channel-rtgs',
                        default => 'db-card',
                    };
                    $channelIcon = match($channel) {
                        'A2A'   => 'heroicon-o-arrows-right-left',
                        'BEFTN' => 'heroicon-o-building-library',
                        'RTGS'  => 'heroicon-o-bolt',
                        default => 'heroicon-o-credit-card',
                    };
                    $channelColor = match($channel) {
                        'A2A'   => 'var(--color-channel-a2a)',
                        'BEFTN' => 'var(--color-channel-beftn)',
                        'RTGS'  => 'var(--color-channel-rtgs)',
                        default => 'var(--color-secondary-ink)',
                    };
                @endphp
                @if ($info['is_live'])
                    <div class="{{ $channelCardClass }}" role="article" aria-label="{{ $channel }} payment mode status: Active">
                        <div class="db-channel-header">
                            <div class="db-flex-gap-2">
                                <x-filament::icon :icon="$channelIcon" class="w-5 h-5" style="color: {{ $channelColor }};" aria-hidden="true" />
                                <span class="db-text-heading db-channel-title">{{ $channel }} Payment Mode</span>
                            </div>
                            <span class="db-badge-sm db-badge-success">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <div class="db-card-inner db-channel-cols">
                            <div>
                                <div class="db-text-sub db-channel-col-label">Checker</div>
                                <div class="db-tabular db-channel-col-val {{ $info['pending_checker'] > 0 ? 'db-channel-val-active' : 'db-channel-val-zero' }}">{{ $info['pending_checker'] }}</div>
                            </div>
                            <div class="db-channel-col-border">
                                <div class="db-text-sub db-channel-col-label">Auth</div>
                                <div class="db-tabular db-channel-col-val {{ $info['pending_auth'] > 0 ? 'db-channel-val-auth' : 'db-channel-val-zero' }}">{{ $info['pending_auth'] }}</div>
                            </div>
                            <div>
                                <div class="db-text-sub db-channel-col-label">Settled</div>
                                <div class="db-tabular db-channel-col-val {{ $info['settled_today'] > 0 ? 'db-channel-val-settled' : 'db-channel-val-zero' }}">{{ $info['settled_today'] }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="{{ $channelCardClass }} db-card-zero-dimmed" role="article" aria-label="{{ $channel }} payment mode status: {{ $info['label'] }}">
                        <div class="db-channel-header">
                            <div class="db-flex-gap-2">
                                <x-filament::icon :icon="$channelIcon" class="w-5 h-5" style="color: {{ $channelColor }};" aria-hidden="true" />
                                <span class="db-text-sub db-channel-title">{{ $channel }} Payment Mode</span>
                            </div>
                            <span class="db-badge-sm db-channel-inactive-badge">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <p class="db-text-sub db-channel-inactive-desc">
                            Channel integration pre-configured for rollout phase.
                        </p>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- 5. EXCEPTIONS ROW (Tier 3 Informational) -->
        <a href="/admin/bkash-reports" class="{{ $exceptions['is_clean'] ? 'db-card' : 'db-card-danger' }} db-exception-link" aria-label="View failed and partial transactions exception report">
            <div class="db-flex-between">
                <div class="db-flex-gap-3">
                    <div class="{{ $exceptions['is_clean'] ? 'db-exception-icon-ok' : 'db-exception-icon-err' }}" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="db-text-sub db-exception-label">
                            Failed / Partial Transactions Today
                        </div>
                        <div class="db-text-heading db-tabular db-exception-headline">
                            {{ $exceptions['headline'] }}
                        </div>
                    </div>
                </div>
                <div class="db-link-action db-exception-action">
                    View Report →
                </div>
            </div>
        </a>

        <!-- 6. BALANCE ROW (Tier 1 Hero TCSA + Tier 3 Operational Balance) -->
        <div class="db-grid-2-1">

            <!-- TCSA Live Balance Card (TIER 1: SIGNATURE HERO CARD) -->
            <div class="db-card-hero" role="region" aria-label="TCSA Live Settlement Pool Account Balance">
                <div class="db-balance-header">
                    <div>
                        <h3 class="db-text-sub db-tcsa-title">TCSA live balance</h3>
                        <p class="db-text-sub db-tabular db-tcsa-sub">
                            {{ $balances['tcsa']['account'] ?? '0100202707747' }} · {{ $balances['tcsa']['label'] ?? 'Pool Account' }}
                        </p>
                    </div>
                    <span class="db-tabular db-badge-sm db-badge-info">
                        Value date: {{ $balances['tcsa']['value_date'] ?? now()->format('d M Y') }}
                    </span>
                </div>

                <div class="db-tcsa-amount">
                    <div class="db-text-val db-tabular">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['tcsa']['balance'] ?? 0) }}
                    </div>
                </div>

                <!-- Settled Today Summary -->
                <div class="db-settled-summary">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2">
                            <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4 text-emerald-500" aria-hidden="true" />
                            <span class="db-text-sub db-settled-label">Settled today</span>
                        </div>
                        <div class="db-flex-gap-3">
                            <span class="db-tabular db-text-heading db-settled-count">
                                {{ $actionStats['settled_today']['count'] }} {{ Str::plural('txn', $actionStats['settled_today']['count']) }}
                            </span>
                            @if ($actionStats['settled_today']['amount'] > 0)
                                <span class="db-tabular db-settled-amount">
                                    BDT {{ \App\Models\BkashTransaction::formatBdtAmount($actionStats['settled_today']['amount']) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Balance Card (Tier 3 Informational) -->
            <div class="db-card db-ops-card" role="region" aria-label="Operational Account Balance">
                <div>
                    <h3 class="db-text-sub db-ops-title">Operational balance</h3>
                    <p class="db-text-sub db-tabular db-tcsa-sub">
                        {{ $balances['ops']['account'] ?? '0100224107522' }}
                    </p>
                    <div class="db-text-val db-tabular db-ops-amount">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['ops']['balance'] ?? 0) }}
                    </div>
                </div>

                <div class="db-ops-footer">
                    <span class="db-tabular db-badge-sm db-badge-success">
                        Value date: {{ $balances['ops']['value_date'] ?? now()->timezone('Asia/Dhaka')->format('d M Y') }}
                    </span>
                </div>
            </div>

        </div>

        <!-- 7. MT940 STATEMENT STATUS STRIP (Tier 3 Informational) -->
        <div class="db-strip" role="region" aria-label="MT940 SFTP statement delivery status">
            <div class="db-flex-gap-2 db-mt940-title">
                <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-sky-600" aria-hidden="true" />
                <span>MT940 SFTP Delivery Status:</span>
            </div>
            <div class="db-mt940-list">
                @foreach ($mt940 as $stmt)
                    <div class="db-flex-gap-2 db-tabular">
                        <span>{{ $stmt['account'] }}:</span>
                        <span class="db-text-heading db-font-semibold">{{ $stmt['timestamp'] }}</span>
                        @if ($stmt['is_ok'])
                            <span class="db-badge-square-sm db-badge-success">
                                {{ $stmt['status'] }}
                            </span>
                        @else
                            <span class="db-badge-square-sm db-badge-warning">
                                {{ $stmt['status'] }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 8. RECENT ACTIVITY FEED (Tier 3 Informational with Tabular Timestamps) -->
        <div class="db-card" role="region" aria-label="Recent transaction pipeline activity feed">
            <div class="db-flex-between db-activity-header">
                <h3 class="db-text-sub db-ops-title">
                    Recent activity
                </h3>
                @if (!empty($activities))
                    <a href="/admin/bkash-transactions" class="db-link-action db-exception-link db-link-xs" aria-label="View all transactions activity">View all activity →</a>
                @endif
            </div>

            @if (empty($activities))
                <div class="db-flex-gap-2 db-text-sub db-activity-empty">
                    <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-gray-400" aria-hidden="true" />
                    <span>No recent activity yet — activity will appear here as files are processed through the pipeline.</span>
                </div>
            @else
                <div class="db-activity-list" role="feed" aria-label="Recent activity items">
                    @foreach (array_slice($activities, 0, 5) as $act)
                        <div class="db-activity-item" role="article" aria-label="Activity item: {{ $act['title'] }} at {{ $act['time'] }}">
                            <div class="db-flex-gap-3">
                                <x-filament::icon :icon="$act['icon']" class="w-4 h-4 {{ $act['color'] }} flex-shrink-0" aria-hidden="true" />
                                <span class="db-text-heading db-activity-title-text">
                                    {{ $act['title'] }}
                                </span>
                            </div>
                            <span class="db-text-sub db-tabular db-activity-time">
                                {{ $act['time'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
                @if (count($activities) > 5)
                    <div class="db-activity-more">
                        <a href="/admin/bkash-transactions" class="db-link-action db-exception-link db-link-xs" aria-label="View {{ count($activities) - 5 }} more activity records">
                            +{{ count($activities) - 5 }} more — view full history →
                        </a>
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-filament-panels::page>
