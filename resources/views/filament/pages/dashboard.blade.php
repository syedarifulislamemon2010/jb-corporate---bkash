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
        <div class="db-flex-between" style="flex-wrap: wrap; gap: 1rem; padding-bottom: 0.5rem;">
            <div class="db-flex-gap-3" style="flex-wrap: wrap;">
                <div class="db-badge-pill {{ $lastSynced['is_delayed'] ? 'db-badge-danger' : 'db-badge-success' }} db-flex-gap-2" role="status" aria-label="Last synchronization status">
                    <span style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; {{ $lastSynced['is_delayed'] ? 'background-color: #dc2626;' : 'background-color: #10b981;' }}" aria-hidden="true"></span>
                    <span class="db-tabular">Last synced: {{ $lastSynced['formatted'] }}</span>
                </div>
            </div>

            <div class="db-flex-gap-3" style="align-items: center; flex-wrap: wrap;">
                <!-- Auto-refresh Toggle Switch / Checkbox (Default: ON) -->
                <label class="db-flex-gap-2" style="cursor: pointer; font-size: 0.8125rem; font-weight: 600; color: #64748b; user-select: none; align-items: center;" aria-label="Toggle auto-refresh">
                    <input 
                        type="checkbox" 
                        wire:model.live="autoRefresh" 
                        style="cursor: pointer; width: 1rem; height: 1rem; border-radius: 0.25rem; accent-color: #0284c7;"
                    />
                    <span style="display: inline-flex; align-items: center; gap: 0.375rem;">
                        <span>Auto-refresh (15s)</span>
                        @if ($autoRefresh)
                            <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #10b981; display: inline-block;" title="Auto-refresh active"></span>
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
                <div style="padding: 0.625rem; border-radius: 0.75rem; background: rgba(245,158,11,0.2); color: #d97706; flex-shrink: 0;" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-amber-500" />
                </div>
                <div style="flex: 1; min-width: 0;">
                    <p style="font-size: 0.95rem; font-weight: 700; margin: 0;">
                        <span class="db-tabular">{{ $urgency['total'] }}</span> {{ Str::plural('file', $urgency['total']) }} need your action today: <span class="db-tabular">{{ $urgency['pending_checker'] }}</span> pending checker, <span class="db-tabular">{{ $urgency['pending_auth1'] }}</span> 1st auth, <span class="db-tabular">{{ $urgency['pending_auth2'] }}</span> 2nd auth
                    </p>
                    <p style="font-size: 0.75rem; opacity: 0.85; margin: 0.125rem 0 0 0;">
                        Please review and clear pending files across all 3 tiers to complete automated CBS settlement.
                    </p>
                </div>
            </div>
        @endif

        <!-- 3. ACTION ROW CARDS (3-Tier Action-Required Pipeline) -->
        <div class="db-grid-3" role="region" aria-label="Pending approval queues">

            <!-- Card 1: Pending Checker (Tier 1 Action Required) -->
            @if ($actionStats['pending_checker']['files'] > 0)
                <a href="{{ $actionStats['pending_checker']['url'] }}" class="db-card-stage-checker" aria-label="View and verify pending checker files">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2" style="color: var(--color-stage-checker);">
                            <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5" style="color: var(--color-stage-checker);" aria-hidden="true" />
                            <span class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-stage-checker);">Pending checker</span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div class="db-text-val db-tabular">
                            {{ $actionStats['pending_checker']['files'] }}
                        </div>
                        <div class="db-text-sub db-tabular" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $actionStats['pending_checker']['trns'] }} {{ Str::plural('transaction', $actionStats['pending_checker']['trns']) }}</div>
                        <div class="db-link-action" style="margin-top: 0.5rem; color: var(--color-stage-checker);">
                            {{ $actionStats['pending_checker']['action_label'] }}
                        </div>
                    </div>
                </a>
            @else
                <div class="db-card-zero" style="opacity: 0.55;">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2" style="color: #94a3b8;">
                            <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 text-slate-400" aria-hidden="true" />
                            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Pending checker</span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div class="db-text-val db-tabular" style="color: #94a3b8 !important;">0</div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">{{ $actionStats['pending_checker']['empty_label'] }}</div>
                    </div>
                </div>
            @endif

            <!-- Card 2: Pending 1st Authorization (Tier 2 Action Required) -->
            @if ($actionStats['pending_auth1']['files'] > 0)
                <a href="{{ $actionStats['pending_auth1']['url'] }}" class="db-card-stage-auth1" aria-label="View and approve pending 1st authorization transactions">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2" style="color: var(--color-stage-auth1);">
                            <x-filament::icon icon="heroicon-o-key" class="w-5 h-5" style="color: var(--color-stage-auth1);" aria-hidden="true" />
                            <span class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-stage-auth1);">Pending 1st auth</span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div class="db-text-val db-tabular">
                            {{ $actionStats['pending_auth1']['files'] }}
                        </div>
                        <div class="db-text-sub db-tabular" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $actionStats['pending_auth1']['trns'] }} {{ Str::plural('transaction', $actionStats['pending_auth1']['trns']) }}</div>
                        <div class="db-link-action" style="margin-top: 0.5rem; color: var(--color-stage-auth1);">
                            {{ $actionStats['pending_auth1']['action_label'] }}
                        </div>
                    </div>
                </a>
            @else
                <div class="db-card-zero" style="opacity: 0.55;">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2" style="color: #94a3b8;">
                            <x-filament::icon icon="heroicon-o-key" class="w-5 h-5 text-slate-400" aria-hidden="true" />
                            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Pending 1st auth</span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div class="db-text-val db-tabular" style="color: #94a3b8 !important;">0</div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">{{ $actionStats['pending_auth1']['empty_label'] }}</div>
                    </div>
                </div>
            @endif

            <!-- Card 3: Pending Final Confirmation (Tier 3 Action Required) -->
            @if ($actionStats['pending_auth2']['files'] > 0)
                <a href="{{ $actionStats['pending_auth2']['url'] }}" class="db-card-stage-auth2" aria-label="View and confirm pending 2nd authorization transactions">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2" style="color: var(--color-stage-auth2);">
                            <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-5 h-5" style="color: var(--color-stage-auth2);" aria-hidden="true" />
                            <span class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-stage-auth2);">Pending 2nd auth</span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div class="db-text-val db-tabular">
                            {{ $actionStats['pending_auth2']['files'] }}
                        </div>
                        <div class="db-text-sub db-tabular" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $actionStats['pending_auth2']['trns'] }} {{ Str::plural('transaction', $actionStats['pending_auth2']['trns']) }}</div>
                        <div class="db-link-action" style="margin-top: 0.5rem; color: var(--color-stage-auth2);">
                            {{ $actionStats['pending_auth2']['action_label'] }}
                        </div>
                    </div>
                </a>
            @else
                <div class="db-card-zero" style="opacity: 0.55;">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2" style="color: #94a3b8;">
                            <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-5 h-5 text-slate-400" aria-hidden="true" />
                            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Pending 2nd auth</span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div class="db-text-val db-tabular" style="color: #94a3b8 !important;">0</div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">{{ $actionStats['pending_auth2']['empty_label'] }}</div>
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
                    <div class="{{ $channelCardClass }}" role="article" aria-label="{{ $channel }} payment mode status">
                        <div class="db-flex-between" style="margin-bottom: 0.75rem;">
                            <div class="db-flex-gap-2">
                                <x-filament::icon :icon="$channelIcon" class="w-5 h-5" style="color: {{ $channelColor }};" aria-hidden="true" />
                                <span class="db-text-heading" style="font-size: 1rem; font-weight: 800;">{{ $channel }} Payment Mode</span>
                            </div>
                            <span class="db-badge-sm db-badge-success">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <div class="db-card-inner db-channel-cols">
                            <div>
                                <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 500;">Checker</div>
                                <div class="db-tabular" style="font-size: 1rem; font-weight: 700; margin-top: 0.125rem; {{ $info['pending_checker'] > 0 ? 'color: #d97706;' : 'color: #cbd5e1;' }}">{{ $info['pending_checker'] }}</div>
                            </div>
                            <div class="db-channel-col-border">
                                <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 500;">Auth</div>
                                <div class="db-tabular" style="font-size: 1rem; font-weight: 700; margin-top: 0.125rem; {{ $info['pending_auth'] > 0 ? 'color: var(--color-signature-accent);' : 'color: #cbd5e1;' }}">{{ $info['pending_auth'] }}</div>
                            </div>
                            <div>
                                <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 500;">Settled</div>
                                <div class="db-tabular" style="font-size: 1rem; font-weight: 700; margin-top: 0.125rem; {{ $info['settled_today'] > 0 ? 'color: #059669;' : 'color: #cbd5e1;' }}">{{ $info['settled_today'] }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="{{ $channelCardClass }}" style="opacity: 0.6;" role="article" aria-label="{{ $channel }} payment mode coming soon">
                        <div class="db-flex-between" style="margin-bottom: 0.75rem;">
                            <div class="db-flex-gap-2">
                                <x-filament::icon :icon="$channelIcon" class="w-5 h-5" style="color: {{ $channelColor }};" aria-hidden="true" />
                                <span class="db-text-sub" style="font-size: 1rem; font-weight: 800;">{{ $channel }} Payment Mode</span>
                            </div>
                            <span class="db-badge-sm" style="background: rgba(148,163,184,0.15); border: 1px solid rgba(148,163,184,0.3); color: #64748b;">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <p class="db-text-sub" style="font-size: 0.75rem; font-style: italic; margin-top: 0.5rem;">
                            Channel integration pre-configured for rollout phase.
                        </p>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- 5. EXCEPTIONS ROW (Tier 3 Informational) -->
        <a href="/admin/bkash-reports" class="{{ $exceptions['is_clean'] ? 'db-card' : 'db-card-danger' }}" style="text-decoration: none;" aria-label="View failed transactions report">
            <div class="db-flex-between">
                <div class="db-flex-gap-3">
                    <div style="padding: 0.5rem; border-radius: 0.75rem; {{ $exceptions['is_clean'] ? 'background: rgba(16,185,129,0.15); color: #059669;' : 'background: rgba(244,63,94,0.15); color: #e11d48;' }}" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                            Failed / Partial Transactions Today
                        </div>
                        <div class="db-text-heading db-tabular" style="font-size: 0.875rem; font-weight: 600; margin-top: 0.125rem;">
                            {{ $exceptions['headline'] }}
                        </div>
                    </div>
                </div>
                <div class="db-link-action" style="font-weight: 700;">
                    View Report →
                </div>
            </div>
        </a>

        <!-- 6. BALANCE ROW (Tier 1 Hero TCSA + Tier 3 Operational Balance) -->
        <div class="db-grid-2-1">

            <!-- TCSA Live Balance Card (TIER 1: SIGNATURE HERO CARD) -->
            <div class="db-card-hero" role="region" aria-label="TCSA Live Balance">
                <div class="db-flex-between" style="margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 class="db-text-sub" style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; color: var(--color-signature-accent);">TCSA live balance</h3>
                        <p class="db-text-sub db-tabular" style="font-size: 0.75rem; font-weight: 500; margin: 0.125rem 0 0 0;">
                            {{ $balances['tcsa']['account'] ?? '0100202707747' }} · {{ $balances['tcsa']['label'] ?? 'Pool Account' }}
                        </p>
                    </div>
                    <span class="db-tabular db-badge-sm db-badge-info">
                        Value date: {{ $balances['tcsa']['value_date'] ?? now()->format('d M Y') }}
                    </span>
                </div>

                <div style="margin-top: 1rem; margin-bottom: 0.5rem;">
                    <div class="db-text-val db-tabular" style="font-size: 2.25rem; letter-spacing: -0.03em;">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['tcsa']['balance'] ?? 0) }}
                    </div>
                </div>

                <!-- Settled Today Summary -->
                <div style="margin-top: 1.25rem; padding-top: 0.75rem; border-top: 1px solid rgba(148,163,184,0.15);">
                    <div class="db-flex-between">
                        <div class="db-flex-gap-2">
                            <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4 text-emerald-500" aria-hidden="true" />
                            <span class="db-text-sub" style="font-size: 0.75rem; font-weight: 600;">Settled today</span>
                        </div>
                        <div class="db-flex-gap-3">
                            <span class="db-tabular db-text-heading" style="font-size: 0.875rem; font-weight: 700;">
                                {{ $actionStats['settled_today']['count'] }} {{ Str::plural('txn', $actionStats['settled_today']['count']) }}
                            </span>
                            @if ($actionStats['settled_today']['amount'] > 0)
                                <span class="db-tabular" style="font-size: 0.875rem; font-weight: 700; color: #059669;">
                                    BDT {{ \App\Models\BkashTransaction::formatBdtAmount($actionStats['settled_today']['amount']) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Balance Card (Tier 3 Informational) -->
            <div class="db-card" style="display: flex; flex-direction: column; justify-content: space-between;" role="region" aria-label="Operational Balance">
                <div>
                    <h3 class="db-text-sub" style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Operational balance</h3>
                    <p class="db-text-sub db-tabular" style="font-size: 0.75rem; font-weight: 500; margin: 0.125rem 0 0 0;">
                        {{ $balances['ops']['account'] ?? '0100224107522' }}
                    </p>
                    <div class="db-text-val db-tabular" style="margin-top: 1.25rem; font-size: 1.75rem; letter-spacing: -0.025em;">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['ops']['balance'] ?? 0) }}
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <span class="db-tabular db-badge-sm db-badge-success">
                        Value date: {{ $balances['ops']['value_date'] ?? now()->timezone('Asia/Dhaka')->format('d M Y') }}
                    </span>
                </div>
            </div>

        </div>

        <!-- 7. MT940 STATEMENT STATUS STRIP (Tier 3 Informational) -->
        <div class="db-strip" role="region" aria-label="MT940 SFTP delivery status">
            <div class="db-flex-gap-2" style="font-weight: 700;">
                <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-sky-600" aria-hidden="true" />
                <span>MT940 SFTP Delivery Status:</span>
            </div>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; font-size: 0.75rem;">
                @foreach ($mt940 as $stmt)
                    <div class="db-flex-gap-2 db-tabular">
                        <span>{{ $stmt['account'] }}:</span>
                        <span class="db-text-heading" style="font-weight: 600;">{{ $stmt['timestamp'] }}</span>
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
        <div class="db-card" role="region" aria-label="Recent activity log">
            <div class="db-flex-between" style="margin-bottom: 1rem;">
                <h3 class="db-text-sub" style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                    Recent activity
                </h3>
                @if (!empty($activities))
                    <a href="/admin/bkash-transactions" class="db-link-action" style="font-size: 0.75rem; text-decoration: none;">View all activity →</a>
                @endif
            </div>

            @if (empty($activities))
                <div class="db-flex-gap-2 db-text-sub" style="padding: 1.5rem 0; justify-content: center; font-size: 0.875rem;">
                    <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-gray-400" aria-hidden="true" />
                    <span>No recent activity yet — activity will appear here as files are processed through the pipeline.</span>
                </div>
            @else
                <div class="db-activity-list" role="feed" aria-label="Activity items">
                    @foreach (array_slice($activities, 0, 5) as $act)
                        <div class="db-activity-item" role="article">
                            <div class="db-flex-gap-3">
                                <x-filament::icon :icon="$act['icon']" class="w-4 h-4 {{ $act['color'] }} flex-shrink-0" aria-hidden="true" />
                                <span class="db-text-heading" style="font-size: 0.8125rem; font-weight: 500;">
                                    {{ $act['title'] }}
                                </span>
                            </div>
                            <span class="db-text-sub db-tabular" style="font-size: 0.75rem; font-weight: 600; white-space: nowrap;">
                                {{ $act['time'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
                @if (count($activities) > 5)
                    <div style="text-align: center; padding-top: 0.75rem; border-top: 1px solid rgba(148,163,184,0.15);">
                        <a href="/admin/bkash-transactions" class="db-link-action" style="font-size: 0.75rem; text-decoration: none;">
                            +{{ count($activities) - 5 }} more — view full history →
                        </a>
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-filament-panels::page>
