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

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- 1. HEADER ROW: Title + Sync Status + Refresh (Single Instance) -->
        <div class="db-flex-between" style="flex-wrap: wrap; gap: 1rem; padding-bottom: 0.5rem;">
            <div class="db-flex-gap-3" style="flex-wrap: wrap;">
                <h1 class="db-text-heading" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.025em; margin: 0;">
                    bKash settlement dashboard
                </h1>
                <div class="db-flex-gap-2" style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; {{ $lastSynced['is_delayed'] ? 'background: rgba(239,68,68,0.15); color: #dc2626; border: 1px solid rgba(239,68,68,0.4);' : 'background: rgba(16,185,129,0.15); color: #059669; border: 1px solid rgba(16,185,129,0.4);' }}">
                    <span style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; {{ $lastSynced['is_delayed'] ? 'background-color: #dc2626;' : 'background-color: #10b981;' }}"></span>
                    <span class="db-tabular">Last synced: {{ $lastSynced['formatted'] }}</span>
                </div>
            </div>

            <button 
                onclick="window.location.reload()"
                class="db-btn-refresh"
            >
                <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 text-slate-400" />
                <span>Refresh</span>
            </button>
        </div>

        <!-- 2. URGENCY ACTION BANNER (Conditional) -->
        @if ($urgency)
            <div class="db-banner-warning">
                <div style="padding: 0.625rem; border-radius: 0.75rem; background: rgba(245,158,11,0.2); color: #d97706; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-amber-500" />
                </div>
                <div style="flex: 1; min-width: 0;">
                    <p style="font-size: 0.95rem; font-weight: 700; margin: 0;">
                        <span class="db-tabular">{{ $urgency['total'] }}</span> {{ Str::plural('file', $urgency['total']) }} need your action today: <span class="db-tabular">{{ $urgency['pending_checker'] }}</span> checker {{ Str::plural('verification', $urgency['pending_checker']) }}, <span class="db-tabular">{{ $urgency['pending_auth'] }}</span> dual {{ Str::plural('approval', $urgency['pending_auth']) }}
                    </p>
                    <p style="font-size: 0.75rem; opacity: 0.85; margin: 0.125rem 0 0 0;">
                        Please review and clear pending files to complete automated CBS settlement.
                    </p>
                </div>
            </div>
        @endif

        <!-- 3. ACTION ROW CARDS (Tier 2 Action-Required & Tier 3/Zero-State) -->
        <div class="db-grid-3">

            <!-- Card 1: Pending Checker (Tier 2 Action Required) -->
            <a href="{{ $actionStats['pending_checker']['url'] }}" class="db-card-warning">
                <div class="db-flex-between">
                    <div class="db-flex-gap-2" style="color: #d97706;">
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 text-amber-500" />
                        <span class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Pending checker</span>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <div class="db-text-val db-tabular">
                        {{ $actionStats['pending_checker']['files'] }}
                    </div>
                    <div class="db-link-action" style="margin-top: 0.5rem;">
                        Awaiting verification →
                    </div>
                </div>
            </a>

            <!-- Card 2: Pending Authorization (Tier 2 Action Required) -->
            <a href="{{ $actionStats['pending_auth']['url'] }}" class="db-card-warning">
                <div class="db-flex-between">
                    <div class="db-flex-gap-2" style="color: #d97706;">
                        <x-filament::icon icon="heroicon-o-key" class="w-5 h-5 text-amber-500" />
                        <span class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Pending authorization</span>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <div class="db-text-val db-tabular">
                        {{ $actionStats['pending_auth']['files'] }}
                    </div>
                    <div class="db-link-action" style="margin-top: 0.5rem;">
                        Dual approval (auth 1 and 2) →
                    </div>
                </div>
            </a>

            <!-- Card 3: Settled Today (Tier 3 with Zero-State treatment if 0) -->
            <div class="{{ $actionStats['settled_today']['amount'] > 0 ? 'db-card' : 'db-card-zero' }}">
                <div class="db-flex-between">
                    <div class="db-flex-gap-2" style="color: #059669;">
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-emerald-500" />
                        <span class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Settled today</span>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <div class="db-text-val db-tabular">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($actionStats['settled_today']['amount']) }}
                    </div>
                    <div class="db-text-sub" style="margin-top: 0.375rem; font-size: 0.75rem; font-weight: 500;">
                        @if ($actionStats['settled_today']['count'] > 0)
                            <span class="db-tabular">{{ $actionStats['settled_today']['count'] }}</span> {{ Str::plural('transaction', $actionStats['settled_today']['count']) }} settled today
                        @else
                            No transactions processed yet
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <!-- 4. PHASED ROLLOUT CHANNEL ROW (Tier 3 Informational) -->
        <div class="db-grid-3">
            @foreach ($channelStats as $channel => $info)
                @if ($info['is_live'])
                    <div class="db-card">
                        <div class="db-flex-between" style="margin-bottom: 0.75rem;">
                            <span class="db-text-heading" style="font-size: 1rem; font-weight: 800;">{{ $channel }} Payment Mode</span>
                            <span style="padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; background: rgba(16,185,129,0.15); color: #059669; border: 1px solid rgba(16,185,129,0.35);">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <div class="db-card-inner" style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; padding: 0.5rem 0; border-radius: 0.75rem;">
                            <div>
                                <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 500;">Checker</div>
                                <div class="db-tabular" style="font-size: 1rem; font-weight: 700; color: #d97706; margin-top: 0.125rem;">{{ $info['pending_checker'] }}</div>
                            </div>
                            <div style="border-left: 1px solid rgba(148,163,184,0.2); border-right: 1px solid rgba(148,163,184,0.2);">
                                <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 500;">Auth</div>
                                <div class="db-tabular" style="font-size: 1rem; font-weight: 700; color: var(--color-signature-accent); margin-top: 0.125rem;">{{ $info['pending_auth'] }}</div>
                            </div>
                            <div>
                                <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 500;">Settled</div>
                                <div class="db-tabular" style="font-size: 1rem; font-weight: 700; color: #059669; margin-top: 0.125rem;">{{ $info['settled_today'] }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="db-card" style="opacity: 0.6;">
                        <div class="db-flex-between" style="margin-bottom: 0.75rem;">
                            <span class="db-text-sub" style="font-size: 1rem; font-weight: 800;">{{ $channel }} Payment Mode</span>
                            <span class="db-text-sub" style="padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; background: rgba(148,163,184,0.15); border: 1px solid rgba(148,163,184,0.3);">
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
        <a href="/admin/bkash-failed-transactions" class="{{ $exceptions['is_clean'] ? 'db-card' : 'db-card-danger' }}" style="text-decoration: none;">
            <div class="db-flex-between">
                <div class="db-flex-gap-3">
                    <div style="padding: 0.5rem; border-radius: 0.75rem; {{ $exceptions['is_clean'] ? 'background: rgba(16,185,129,0.15); color: #059669;' : 'background: rgba(244,63,94,0.15); color: #e11d48;' }}">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="db-text-sub" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                            Failed / Partial Transactions Today
                        </div>
                        <div class="db-text-heading db-tabular" style="font-size: 0.875rem; font-weight: 600; margin-top: 0.125rem;">
                            {{ $exceptions['description'] }}
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
            <div class="db-card-hero">
                <div class="db-flex-between" style="margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 class="db-text-sub" style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; color: var(--color-signature-accent);">TCSA live balance</h3>
                        <p class="db-text-sub db-tabular" style="font-size: 0.75rem; font-weight: 500; margin: 0.125rem 0 0 0;">
                            {{ $balances['tcsa']['account'] }} · {{ $balances['tcsa']['label'] }}
                        </p>
                    </div>
                    <span class="db-tabular" style="padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 700; background: rgba(74,111,165,0.15); color: var(--color-signature-accent); border: 1px solid rgba(74,111,165,0.3);">
                        Value date: {{ $balances['tcsa']['value_date'] }}
                    </span>
                </div>

                <div style="margin-top: 1rem; margin-bottom: 0.5rem;">
                    <div class="db-text-val db-tabular" style="font-size: 2.25rem; letter-spacing: -0.03em;">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['tcsa']['balance']) }}
                    </div>
                </div>

                <!-- Smooth Green Sparkline -->
                <div style="margin-top: 1rem; padding-top: 0.5rem;">
                    <svg style="width: 100%; height: 64px; color: #10b981;" viewBox="0 0 300 40" fill="none" preserveAspectRatio="none">
                        <path d="M 0 30 Q 50 25, 100 28 T 200 15 T 300 10" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" />
                    </svg>
                </div>
            </div>

            <!-- Operational Balance Card (Tier 3 Informational) -->
            <div class="db-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 class="db-text-sub" style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Operational balance</h3>
                    <p class="db-text-sub db-tabular" style="font-size: 0.75rem; font-weight: 500; margin: 0.125rem 0 0 0;">
                        {{ $balances['ops']['account'] }}
                    </p>
                    <div class="db-text-val db-tabular" style="margin-top: 1.25rem; font-size: 1.75rem; letter-spacing: -0.025em;">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['ops']['balance']) }}
                    </div>
                </div>

                <div class="db-flex-gap-2 db-tabular" style="margin-top: 1.5rem; font-size: 0.75rem; font-weight: 700; color: #059669;">
                    <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-4 h-4 text-emerald-600" />
                    <span>↗ {{ $balances['ops']['change_pct'] }}% vs yesterday</span>
                </div>
            </div>

        </div>

        <!-- 7. MT940 STATEMENT STATUS STRIP (Tier 3 Informational) -->
        <div class="db-strip">
            <div class="db-flex-gap-2" style="font-weight: 700;">
                <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-sky-600" />
                <span>MT940 SFTP Delivery Status:</span>
            </div>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; font-size: 0.75rem;">
                @foreach ($mt940 as $stmt)
                    <div class="db-flex-gap-2 db-tabular">
                        <span>{{ $stmt['account'] }}:</span>
                        <span class="db-text-heading" style="font-weight: 600;">{{ $stmt['timestamp'] }}</span>
                        @if ($stmt['is_ok'])
                            <span style="padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.625rem; font-weight: 700; background: rgba(16,185,129,0.15); color: #059669; border: 1px solid rgba(16,185,129,0.35);">
                                {{ $stmt['status'] }}
                            </span>
                        @else
                            <span style="padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.625rem; font-weight: 700; background: rgba(245,158,11,0.15); color: #d97706; border: 1px solid rgba(245,158,11,0.35);">
                                {{ $stmt['status'] }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 8. RECENT ACTIVITY FEED (Tier 3 Informational with Tabular Timestamps) -->
        <div class="db-card">
            <h3 class="db-text-sub" style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 1rem 0;">
                Recent activity
            </h3>

            @if (empty($activities))
                <div class="db-flex-gap-2 db-text-sub" style="padding: 1.5rem 0; justify-content: center; font-size: 0.875rem;">
                    <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 text-gray-400" />
                    <span>No recent activity yet</span>
                </div>
            @else
                <div style="display: flex; flex-direction: column;">
                    @foreach ($activities as $index => $act)
                        <div class="db-flex-between" style="padding: 0.875rem 0; {{ $index > 0 ? 'border-top: 1px solid rgba(148,163,184,0.2);' : '' }}">
                            <div class="db-flex-gap-3">
                                <x-filament::icon :icon="$act['icon']" class="w-4 h-4 {{ $act['color'] }} flex-shrink-0" />
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
            @endif
        </div>

    </div>
</x-filament-panels::page>
