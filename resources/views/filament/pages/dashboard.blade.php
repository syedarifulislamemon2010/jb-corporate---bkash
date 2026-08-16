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

        <!-- 1. HEADER ROW: Title + Sync Status + Refresh -->
        <div class="db-flex-between" style="flex-wrap: wrap; gap: 1rem; padding-bottom: 0.5rem;">
            <div class="db-flex-gap-3">
                <h1 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.025em; color: #ffffff; margin: 0;">
                    bKash settlement dashboard
                </h1>
                <div class="db-flex-gap-2" style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; {{ $lastSynced['is_delayed'] ? 'background: rgba(244,63,94,0.1); color: #f43f5e; border: 1px solid rgba(244,63,94,0.3);' : 'background: rgba(100,116,139,0.1); color: #94a3b8; border: 1px solid rgba(51,65,85,0.4);' }}">
                    <span style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; {{ $lastSynced['is_delayed'] ? 'background-color: #f43f5e;' : 'background-color: #34d399;' }}"></span>
                    <span>Last synced: {{ $lastSynced['formatted'] }}</span>
                </div>
            </div>

            <button 
                onclick="window.location.reload()"
                class="db-flex-gap-2"
                style="padding: 0.5rem 1rem; font-size: 0.75rem; font-weight: 700; color: #e2e8f0; background-color: #1e293b; border: 1px solid #334155; border-radius: 0.75rem; cursor: pointer; transition: background 0.15s ease;"
            >
                <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 text-slate-400" />
                <span>Refresh</span>
            </button>
        </div>

        <!-- 2. URGENCY ACTION BANNER (Conditional) -->
        @if ($urgency)
            <div class="db-banner-warning">
                <div style="padding: 0.625rem; border-radius: 0.75rem; background: rgba(245,158,11,0.2); color: #fbbf24; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-amber-400" />
                </div>
                <div style="flex: 1; min-width: 0;">
                    <p style="font-size: 0.95rem; font-weight: 700; color: #fbbf24; margin: 0;">
                        {{ $urgency['total'] }} {{ Str::plural('file', $urgency['total']) }} need your action today: {{ $urgency['pending_checker'] }} checker {{ Str::plural('verification', $urgency['pending_checker']) }}, {{ $urgency['pending_auth'] }} dual {{ Str::plural('approval', $urgency['pending_auth']) }}
                    </p>
                    <p style="font-size: 0.75rem; color: rgba(245,158,11,0.8); margin: 0.125rem 0 0 0;">
                        Please review and clear pending files to complete automated CBS settlement.
                    </p>
                </div>
            </div>
        @endif

        <!-- 3. ACTION ROW CARDS (File-level pending & Settled Today) -->
        <div class="db-grid-3">

            <!-- Card 1: Pending Checker -->
            <a href="{{ $actionStats['pending_checker']['url'] }}" class="db-card-warning">
                <div class="db-flex-between">
                    <div class="db-flex-gap-2" style="color: #fbbf24;">
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 text-amber-400" />
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #cbd5e1;">Pending checker</span>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <div class="db-text-val">
                        {{ $actionStats['pending_checker']['files'] }}
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.75rem; font-weight: 600; color: #38bdf8;">
                        Awaiting verification →
                    </div>
                </div>
            </a>

            <!-- Card 2: Pending Authorization -->
            <a href="{{ $actionStats['pending_auth']['url'] }}" class="db-card-warning">
                <div class="db-flex-between">
                    <div class="db-flex-gap-2" style="color: #fbbf24;">
                        <x-filament::icon icon="heroicon-o-key" class="w-5 h-5 text-amber-400" />
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #cbd5e1;">Pending authorization</span>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <div class="db-text-val">
                        {{ $actionStats['pending_auth']['files'] }}
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.75rem; font-weight: 600; color: #38bdf8;">
                        Dual approval (auth 1 and 2) →
                    </div>
                </div>
            </a>

            <!-- Card 3: Settled Today -->
            <div class="db-card">
                <div class="db-flex-between">
                    <div class="db-flex-gap-2" style="color: #34d399;">
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-emerald-400" />
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #cbd5e1;">Settled today</span>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <div class="db-text-val">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($actionStats['settled_today']['amount']) }}
                    </div>
                    <div style="margin-top: 0.375rem; font-size: 0.75rem; font-weight: 500; color: #94a3b8;">
                        @if ($actionStats['settled_today']['count'] > 0)
                            {{ $actionStats['settled_today']['count'] }} {{ Str::plural('transaction', $actionStats['settled_today']['count']) }} settled today
                        @else
                            No transactions processed yet
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <!-- 4. PHASED ROLLOUT CHANNEL ROW (A2A / BEFTN / RTGS) -->
        <div class="db-grid-3">
            @foreach ($channelStats as $channel => $info)
                @if ($info['is_live'])
                    <div class="db-card">
                        <div class="db-flex-between" style="margin-bottom: 0.75rem;">
                            <span style="font-size: 1rem; font-weight: 800; color: #ffffff;">{{ $channel }} Payment Mode</span>
                            <span style="padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.3);">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; padding: 0.5rem 0; background: rgba(2,6,23,0.4); border-radius: 0.75rem; border: 1px solid rgba(30,41,59,0.8);">
                            <div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">Checker</div>
                                <div style="font-size: 1rem; font-weight: 700; color: #fbbf24; margin-top: 0.125rem;">{{ $info['pending_checker'] }}</div>
                            </div>
                            <div style="border-left: 1px solid rgba(30,41,59,0.8); border-right: 1px solid rgba(30,41,59,0.8);">
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">Auth</div>
                                <div style="font-size: 1rem; font-weight: 700; color: #38bdf8; margin-top: 0.125rem;">{{ $info['pending_auth'] }}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">Settled</div>
                                <div style="font-size: 1rem; font-weight: 700; color: #34d399; margin-top: 0.125rem;">{{ $info['settled_today'] }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="db-card" style="opacity: 0.5;">
                        <div class="db-flex-between" style="margin-bottom: 0.75rem;">
                            <span style="font-size: 1rem; font-weight: 800; color: #94a3b8;">{{ $channel }} Payment Mode</span>
                            <span style="padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; background: #1e293b; color: #94a3b8; border: 1px solid #334155;">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <p style="font-size: 0.75rem; color: #64748b; font-style: italic; margin-top: 0.5rem;">
                            Channel integration pre-configured for rollout phase.
                        </p>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- 5. EXCEPTIONS ROW (Failed / Partial Transactions) -->
        <a href="/admin/bkash-failed-transactions" class="{{ $exceptions['is_clean'] ? 'db-card' : 'db-card-danger' }}" style="text-decoration: none;">
            <div class="db-flex-between">
                <div class="db-flex-gap-3">
                    <div style="padding: 0.5rem; border-radius: 0.75rem; {{ $exceptions['is_clean'] ? 'background: rgba(16,185,129,0.1); color: #34d399;' : 'background: rgba(244,63,94,0.1); color: #fb7185;' }}">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #cbd5e1;">
                            Failed / Partial Transactions Today
                        </div>
                        <div style="font-size: 0.875rem; font-weight: 600; color: {{ $exceptions['is_clean'] ? '#34d399' : '#cbd5e1' }}; margin-top: 0.125rem;">
                            {{ $exceptions['description'] }}
                        </div>
                    </div>
                </div>
                <div style="font-size: 0.75rem; font-weight: 700; color: #38bdf8;">
                    View Report →
                </div>
            </div>
        </a>

        <!-- 6. BALANCE ROW (TCSA + Operational Accounts) -->
        <div class="db-grid-2-1">

            <!-- TCSA Live Balance Card (2 Cols) -->
            <div class="db-card" style="position: relative; overflow: hidden;">
                <div class="db-flex-between" style="margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 style="font-size: 0.875rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">TCSA live balance</h3>
                        <p style="font-size: 0.75rem; color: #94a3b8; font-weight: 500; margin: 0.125rem 0 0 0;">
                            {{ $balances['tcsa']['account'] }} · {{ $balances['tcsa']['label'] }}
                        </p>
                    </div>
                    <span style="padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 700; background: rgba(56,189,248,0.1); color: #38bdf8; border: 1px solid rgba(56,189,248,0.2);">
                        Value date: {{ $balances['tcsa']['value_date'] }}
                    </span>
                </div>

                <div style="margin-top: 1rem; margin-bottom: 0.5rem;">
                    <div style="font-size: 2rem; font-weight: 800; color: #ffffff; letter-spacing: -0.025em;">
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

            <!-- Operational Balance Card (1 Col) -->
            <div class="db-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 0.875rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Operational balance</h3>
                    <p style="font-size: 0.75rem; color: #94a3b8; font-weight: 500; margin: 0.125rem 0 0 0;">
                        {{ $balances['ops']['account'] }}
                    </p>
                    <div style="margin-top: 1.25rem; font-size: 1.75rem; font-weight: 800; color: #ffffff; letter-spacing: -0.025em;">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['ops']['balance']) }}
                    </div>
                </div>

                <div class="db-flex-gap-2" style="margin-top: 1.5rem; font-size: 0.75rem; font-weight: 700; color: #34d399;">
                    <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-4 h-4 text-emerald-400" />
                    <span>↗ {{ $balances['ops']['change_pct'] }}% vs yesterday</span>
                </div>
            </div>

        </div>

        <!-- 7. MT940 STATEMENT STATUS STRIP -->
        <div class="db-strip">
            <div class="db-flex-gap-2" style="font-weight: 700; color: #cbd5e1;">
                <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-sky-400" />
                <span>MT940 SFTP Delivery Status:</span>
            </div>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; color: #94a3b8; font-size: 0.75rem;">
                @foreach ($mt940 as $stmt)
                    <div class="db-flex-gap-2">
                        <span>{{ $stmt['account'] }}:</span>
                        <span style="color: #e2e8f0; font-weight: 600;">{{ $stmt['timestamp'] }}</span>
                        <span style="padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.625rem; font-weight: 700; background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2);">
                            {{ $stmt['status'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 8. RECENT ACTIVITY FEED (4-Stage Notification Vocabulary) -->
        <div class="db-card">
            <h3 style="font-size: 0.875rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 1rem 0;">
                Recent activity
            </h3>

            <div style="display: flex; flex-direction: column;">
                @foreach ($activities as $index => $act)
                    <div class="db-flex-between" style="padding: 0.875rem 0; {{ $index > 0 ? 'border-top: 1px solid rgba(30,41,59,0.8);' : '' }}">
                        <div class="db-flex-gap-3">
                            <x-filament::icon :icon="$act['icon']" class="w-4 h-4 {{ $act['color'] }} flex-shrink-0" />
                            <span style="font-size: 0.8125rem; font-weight: 500; color: #e2e8f0;">
                                {{ $act['title'] }}
                            </span>
                        </div>
                        <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; white-space: nowrap;">
                            {{ $act['time'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-panels::page>
