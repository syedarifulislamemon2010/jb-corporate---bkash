<x-filament-panels::page>
    <div class="space-y-4">
        <!-- Tabs & Filters Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-800 pb-3">
            <div class="flex items-center gap-1.5 flex-wrap">
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'all')"
                    @class([
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                        'bg-primary-600 text-white shadow-sm' => $activeChannel === 'all',
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $activeChannel !== 'all',
                    ])
                >
                    All Transmissions
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'a2a')"
                    @class([
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                        'bg-primary-600 text-white shadow-sm' => $activeChannel === 'a2a',
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $activeChannel !== 'a2a',
                    ])
                >
                    Account to Account (A2A) - Janata Bank PLC.
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'beftn')"
                    @class([
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                        'bg-primary-600 text-white shadow-sm' => $activeChannel === 'beftn',
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $activeChannel !== 'beftn',
                    ])
                >
                    BEFTN
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'rtgs')"
                    @class([
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                        'bg-primary-600 text-white shadow-sm' => $activeChannel === 'rtgs',
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $activeChannel !== 'rtgs',
                    ])
                >
                    RTGS
                </button>
            </div>

            <!-- Search input -->
            <div class="flex items-center gap-2">
                <div class="relative min-w-[240px]">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search File Name, Channel..."
                        class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 pl-8 text-gray-900 dark:text-gray-100 shadow-xs focus:ring-1 focus:ring-primary-500"
                    />
                    <svg style="width: 14px; height: 14px; position: absolute; left: 10px; top: 8px;" class="text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Bulk Action Toolbar (appears when batches are selected) -->
        @if(count($selectedBatches) > 0)
            <div class="flex items-center justify-between bg-primary-50 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800 rounded-lg p-2.5 px-4 shadow-sm animate-fade-in">
                <div class="flex items-center gap-2 text-xs text-primary-900 dark:text-primary-200 font-semibold">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-600 text-white text-[11px] font-bold">
                        {{ count($selectedBatches) }}
                    </span>
                    <span>batch file(s) selected</span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="checkSelectedBatches"
                        wire:confirm="Are you sure you want to verify all transactions in the selected batch file(s) as Checker?"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Check Selected Batch Files</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedBatches', [])"
                        class="px-2.5 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                    >
                        Deselect All
                    </button>
                </div>
            </div>
        @endif

        @php
            $batches = $this->getBatches();
        @endphp

        <!-- Master Batch Files Table -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <table class="w-full text-left text-xs text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-800/75 border-b border-gray-200 dark:border-gray-800 text-[11px] uppercase font-bold text-gray-600 dark:text-gray-300 tracking-wider">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center">
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                class="rounded border-gray-300 dark:border-gray-700 text-primary-600 focus:ring-primary-500"
                            />
                        </th>
                        <th class="w-12 px-3 py-3">#</th>
                        <th class="px-4 py-3">File Name</th>
                        <th class="px-3 py-3">Channel</th>
                        <th class="px-3 py-3 text-center">Total Transaction</th>
                        <th class="px-3 py-3 text-center">Success Transaction</th>
                        <th class="px-3 py-3 text-center">Failed Transaction</th>
                        <th class="px-4 py-3 text-right">Amount BDT</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($batches as $batch)
                        @php
                            $txns = $batch->getBatchTransactions();
                            $totalCount = $batch->total_data ?: $txns->count();
                            $successCount = $txns->whereIn('status_id', [1001, 1002, 1003, 1004, 1006])->count();
                            $failedCount = $txns->whereIn('status_id', [1007, 9000])->count() + $batch->failedTransactions->count();
                            $batchAmount = (float) ($batch->total_amount ?: $txns->sum('amount'));
                            $downloadUrl = route('admin.bkash.download-batch', ['file' => $batch->file_name]);
                        @endphp
                        <tr
                            x-data="{ open: false }"
                            class="hover:bg-gray-50/75 dark:hover:bg-gray-800/40 transition-colors"
                        >
                            <!-- Master File Row -->
                            <td class="px-3 py-3 text-center" onclick="event.stopPropagation()">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedBatches"
                                    value="{{ $batch->id }}"
                                    class="rounded border-gray-300 dark:border-gray-700 text-primary-600 focus:ring-primary-500"
                                />
                            </td>
                            <td class="px-3 py-3 font-semibold text-gray-500 dark:text-gray-400">
                                {{ $loop->iteration }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-primary-600 dark:text-primary-400">
                                <div class="flex items-center gap-2 cursor-pointer" @click="open = !open">
                                    <button
                                        type="button"
                                        class="p-0.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                        :aria-expanded="open"
                                    >
                                        <svg style="width: 14px; height: 14px; min-width: 14px; transition: transform 0.2s;" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                    <span class="hover:underline">{{ $batch->file_name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                @if($batch->transaction_type === 'A2A')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-300 dark:border-sky-800">A2A</span>
                                @elseif($batch->transaction_type === 'BEFTN')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-300 dark:border-purple-800">BEFTN</span>
                                @elseif($batch->transaction_type === 'RTGS')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">RTGS</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">{{ $batch->transaction_type }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-bold text-gray-800 dark:text-gray-200">
                                {{ number_format($totalCount) }}
                            </td>
                            <td class="px-3 py-3 text-center font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($successCount) }}
                            </td>
                            <td class="px-3 py-3 text-center font-bold {{ $failedCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ number_format($failedCount) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                BDT {{ number_format($batchAmount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                                <a
                                    href="{{ $downloadUrl }}"
                                    target="_blank"
                                    title="Download original batch file"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md shadow-xs text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200 transition dark:bg-primary-950/60 dark:text-primary-300 dark:border-primary-800 dark:hover:bg-primary-900"
                                >
                                    <svg style="width: 14px; height: 14px; min-width: 14px; display: inline-block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <span>Download</span>
                                </a>
                            </td>
                        </tr>

                        <!-- Expandable Sub-Table Row (Child Transactions) -->
                        <tr x-show="open" x-cloak class="bg-gray-50/75 dark:bg-gray-900/60 border-t border-b border-gray-200 dark:border-gray-800">
                            <td colspan="9" class="p-3 pl-8">
                                <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-xs">
                                    <div class="px-3 py-2 bg-gray-100/70 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                        <span class="font-bold text-[11px] uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                            Transactions breakdown for {{ $batch->file_name }} ({{ $txns->count() }} items)
                                        </span>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                            No select options for individual transaction rows
                                        </span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs text-left">
                                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-bold uppercase text-[10px] border-b border-gray-200 dark:border-gray-700">
                                                <tr>
                                                    <th class="px-3 py-2 w-10 text-center">#</th>
                                                    <th class="px-3 py-2">Ref No</th>
                                                    <th class="px-3 py-2">Channel</th>
                                                    <th class="px-3 py-2">Source Account (TCSA/Ops)</th>
                                                    <th class="px-3 py-2">Beneficiary Name</th>
                                                    <th class="px-3 py-2">Beneficiary Account</th>
                                                    <th class="px-3 py-2 text-right">Amount (BDT)</th>
                                                    <th class="px-3 py-2 text-right">Routing Number</th>
                                                    <th class="px-3 py-2">Bank Name</th>
                                                    <th class="px-3 py-2">Txn ID</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-normal">
                                                @forelse($txns as $txn)
                                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                                        <td class="px-3 py-2 text-center text-gray-500">{{ $loop->iteration }}</td>
                                                        <td class="px-3 py-2 font-semibold text-primary-600 dark:text-primary-400">{{ $txn->reference_id }}</td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                                                {{ $txn->transaction_type }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $txn->source_account_no ?? '0100202707747' }}</td>
                                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $txn->debit_account_title }}</td>
                                                        <td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $txn->beneficiary_account_no }}</td>
                                                        <td class="px-3 py-2 text-right font-bold font-mono text-gray-900 dark:text-gray-100">{{ number_format($txn->amount, 2) }}</td>
                                                        <td class="px-3 py-2 text-right font-mono text-gray-600 dark:text-gray-400">{{ $txn->credit_routing ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $txn->credit_bank ?? '-' }}</td>
                                                        <td class="px-3 py-2 font-mono text-gray-600 dark:text-gray-400">{{ $txn->txn_id }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="10" class="px-3 py-3 text-center text-gray-400">
                                                            No transactions found for this batch.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg style="width: 32px; height: 32px;" class="text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="font-semibold text-sm">All Caught Up!</p>
                                    <p class="text-xs text-gray-400">No files are currently pending Checker verification.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400 px-1 flex items-center justify-between">
            <span>Showing {{ $batches->count() }} batch file(s) pending Checker verification</span>
            <span>All batch files default collapsed on load & refresh</span>
        </div>
    </div>
</x-filament-panels::page>