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

    <div class="space-y-6">

        <!-- 1. HEADER ROW: Title + Sync Status + Refresh -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    bKash settlement dashboard
                </h1>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $lastSynced['is_delayed'] ? 'bg-rose-500/10 text-rose-500 border border-rose-500/30' : 'bg-slate-500/10 text-slate-400 border border-slate-700/40' }}">
                    <span class="w-2 h-2 rounded-full {{ $lastSynced['is_delayed'] ? 'bg-rose-500 animate-ping' : 'bg-emerald-400 animate-pulse' }}"></span>
                    <span>Last synced: {{ $lastSynced['formatted'] }}</span>
                </div>
            </div>

            <button 
                onclick="window.location.reload()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 active:bg-slate-900 border border-slate-700 rounded-xl transition duration-150 shadow-sm"
            >
                <x-heroicon-o-arrow-path class="w-4 h-4 text-slate-400" />
                <span>Refresh</span>
            </button>
        </div>

        <!-- 2. URGENCY ACTION BANNER (Conditional) -->
        @if ($urgency)
            <div class="relative overflow-hidden rounded-2xl bg-amber-500/10 border border-amber-500/30 p-4 sm:p-5 flex items-center gap-4 text-amber-500 shadow-sm">
                <div class="p-2.5 rounded-xl bg-amber-500/20 text-amber-400 flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm sm:text-base font-bold tracking-tight text-amber-400">
                        {{ $urgency['total'] }} {{ Str::plural('file', $urgency['total']) }} need your action today: {{ $urgency['pending_checker'] }} checker {{ Str::plural('verification', $urgency['pending_checker']) }}, {{ $urgency['pending_auth'] }} dual {{ Str::plural('approval', $urgency['pending_auth']) }}
                    </p>
                    <p class="text-xs text-amber-500/80 mt-0.5">
                        Please review and clear pending files to complete automated CBS settlement.
                    </p>
                </div>
            </div>
        @endif

        <!-- 3. ACTION ROW CARDS (File-level pending & Settled Today) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            <!-- Card 1: Pending Checker -->
            <a 
                href="{{ $actionStats['pending_checker']['url'] }}"
                class="group relative block p-5 rounded-2xl bg-slate-900/60 dark:bg-slate-900 border border-amber-500/40 hover:border-amber-500/80 transition duration-200 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-amber-400">
                        <x-heroicon-o-shield-check class="w-5 h-5" />
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Pending checker</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-white group-hover:text-amber-400 transition duration-150">
                        {{ $actionStats['pending_checker']['files'] }}
                    </div>
                    <div class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-sky-400 group-hover:underline">
                        <span>Awaiting verification →</span>
                    </div>
                </div>
            </a>

            <!-- Card 2: Pending Authorization -->
            <a 
                href="{{ $actionStats['pending_auth']['url'] }}"
                class="group relative block p-5 rounded-2xl bg-slate-900/60 dark:bg-slate-900 border border-amber-500/40 hover:border-amber-500/80 transition duration-200 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-amber-400">
                        <x-heroicon-o-key class="w-5 h-5" />
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Pending authorization</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-white group-hover:text-amber-400 transition duration-150">
                        {{ $actionStats['pending_auth']['files'] }}
                    </div>
                    <div class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-sky-400 group-hover:underline">
                        <span>Dual approval (auth 1 and 2) →</span>
                    </div>
                </div>
            </a>

            <!-- Card 3: Settled Today -->
            <div class="p-5 rounded-2xl bg-slate-900/60 dark:bg-slate-900 border border-slate-800 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-emerald-400">
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Settled today</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($actionStats['settled_today']['amount']) }}
                    </div>
                    <div class="mt-1.5 text-xs font-medium text-slate-400">
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach ($channelStats as $channel => $info)
                @if ($info['is_live'])
                    <div class="p-5 rounded-2xl bg-slate-900/80 dark:bg-slate-900 border border-slate-700/60 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-base font-extrabold text-white">{{ $channel }} Payment Mode</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center py-2 bg-slate-950/40 rounded-xl border border-slate-800/80">
                            <div>
                                <div class="text-xs text-slate-400 font-medium">Checker</div>
                                <div class="text-base font-bold text-amber-400 mt-0.5">{{ $info['pending_checker'] }}</div>
                            </div>
                            <div class="border-x border-slate-800/80">
                                <div class="text-xs text-slate-400 font-medium">Auth</div>
                                <div class="text-base font-bold text-sky-400 mt-0.5">{{ $info['pending_auth'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400 font-medium">Settled</div>
                                <div class="text-base font-bold text-emerald-400 mt-0.5">{{ $info['settled_today'] }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-5 rounded-2xl bg-slate-900/30 dark:bg-slate-950/40 border border-slate-800/50 opacity-60">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-base font-extrabold text-slate-400">{{ $channel }} Payment Mode</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-800 text-slate-400 border border-slate-700">
                                {{ $info['label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 italic mt-2">
                            Channel integration pre-configured for rollout phase.
                        </p>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- 5. EXCEPTIONS ROW (Failed / Partial Transactions) -->
        <a 
            href="/admin/bkash-failed-transactions"
            class="group block p-4 sm:p-5 rounded-2xl bg-slate-900/60 dark:bg-slate-900 border {{ $exceptions['is_clean'] ? 'border-slate-800' : 'border-rose-500/40 hover:border-rose-500/80' }} transition duration-200 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl {{ $exceptions['is_clean'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-300">
                            Failed / Partial Transactions Today
                        </div>
                        <div class="text-sm font-semibold {{ $exceptions['is_clean'] ? 'text-emerald-400' : 'text-slate-300' }} mt-0.5">
                            {{ $exceptions['description'] }}
                        </div>
                    </div>
                </div>
                <div class="text-xs font-bold text-sky-400 group-hover:underline">
                    View Report →
                </div>
            </div>
        </a>

        <!-- 6. BALANCE ROW (TCSA + Operational Accounts) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- TCSA Live Balance Card (2 Cols) -->
            <div class="lg:col-span-2 p-6 rounded-2xl bg-slate-900/60 dark:bg-slate-900 border border-slate-800 shadow-sm relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wide">TCSA live balance</h3>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">
                            {{ $balances['tcsa']['account'] }} · {{ $balances['tcsa']['label'] }}
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                        Value date: {{ $balances['tcsa']['value_date'] }}
                    </span>
                </div>

                <div class="mt-4 mb-2">
                    <div class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['tcsa']['balance']) }}
                    </div>
                </div>

                <!-- Smooth Green Sparkline -->
                <div class="mt-4 pt-2">
                    <svg class="w-full h-16 text-emerald-500" viewBox="0 0 300 40" fill="none" preserveAspectRatio="none">
                        <path d="M 0 30 Q 50 25, 100 28 T 200 15 T 300 10" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" />
                    </svg>
                </div>
            </div>

            <!-- Operational Balance Card (1 Col) -->
            <div class="p-6 rounded-2xl bg-slate-900/60 dark:bg-slate-900 border border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wide">Operational balance</h3>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">
                        {{ $balances['ops']['account'] }}
                    </p>
                    <div class="mt-5 text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        BDT {{ \App\Models\BkashTransaction::formatBdtAmount($balances['ops']['balance']) }}
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-1.5 text-xs font-bold text-emerald-400">
                    <x-heroicon-m-arrow-trending-up class="w-4 h-4" />
                    <span>↗ {{ $balances['ops']['change_pct'] }}% vs yesterday</span>
                </div>
            </div>

        </div>

        <!-- 7. MT940 STATEMENT STATUS STRIP -->
        <div class="p-4 rounded-2xl bg-slate-900/40 dark:bg-slate-900/60 border border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 text-slate-300 font-bold">
                <x-heroicon-o-document-text class="w-4 h-4 text-sky-400" />
                <span>MT940 SFTP Delivery Status:</span>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-slate-400">
                @foreach ($mt940 as $stmt)
                    <div class="flex items-center gap-2">
                        <span>{{ $stmt['account'] }}:</span>
                        <span class="text-slate-200 font-semibold">{{ $stmt['timestamp'] }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ $stmt['status'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 8. RECENT ACTIVITY FEED (4-Stage Notification Vocabulary) -->
        <div class="p-6 rounded-2xl bg-slate-900/60 dark:bg-slate-900 border border-slate-800 shadow-sm">
            <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wide mb-4">
                Recent activity
            </h3>

            <div class="divide-y divide-slate-800/80">
                @foreach ($activities as $act)
                    <div class="py-3.5 flex items-center justify-between gap-4 first:pt-0 last:pb-0">
                        <div class="flex items-center gap-3">
                            <x-dynamic-component :component="$act['icon']" class="w-4 h-4 {{ $act['color'] }} flex-shrink-0" />
                            <span class="text-xs sm:text-sm font-medium text-slate-200">
                                {{ $act['title'] }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 font-semibold whitespace-nowrap">
                            {{ $act['time'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-panels::page>
