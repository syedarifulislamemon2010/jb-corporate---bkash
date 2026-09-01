@php
    use App\Models\BkashTransaction;

    $successCount = $batch->transactions()->whereIn('status_id', [
        BkashTransaction::STATUS_CBS_SUCCESS,
        BkashTransaction::STATUS_CBS_RESPONSE_SUCCESS,
    ])->count();

    $failedCount = $batch->failedTransactions()->count() + $batch->transactions()->whereIn('status_id', [
        BkashTransaction::STATUS_REJECTED,
        BkashTransaction::STATUS_CBS_RESPONSE_FAILED,
    ])->count();

    $pendingCount = $batch->transactions()->whereIn('status_id', [
        BkashTransaction::STATUS_PENDING_CHECKER,
        BkashTransaction::STATUS_CHECKED,
        BkashTransaction::STATUS_AUTH_1_APPROVED,
        BkashTransaction::STATUS_FINAL_AUTHORIZED,
    ])->count();

    $channel = $batch->transaction_type ?? 'A2A';
    $channelBadgeClass = match ($channel) {
        'RTGS'  => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'BEFTN' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'A2A'   => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700',
    };

    $transactions = $batch->transactions()->orderBy('row_sequence', 'asc')->limit(100)->get();
    $failedList = $batch->failedTransactions()->limit(50)->get();
@endphp

<div class="space-y-6">
    {{-- Header Metadata --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 p-4">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Batch File Name</span>
                <h3 class="font-mono text-base font-bold text-gray-900 dark:text-white">{{ $batch->file_name }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Uploaded by <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $batch->created_by ?? 'SYSTEM' }}</span>
                    on {{ $batch->create_date?->format('d M Y, h:i A') ?? $batch->created_at?->format('d M Y, h:i A') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-md border {{ $channelBadgeClass }}">
                    {{ $channel }}
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                    {{ $batch->total_data ?? $batch->transactions()->count() }} Total
                </span>
            </div>
        </div>

        {{-- Export Action Buttons --}}
        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Export Transactions:</span>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'xlsx']) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 shadow-sm transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download as Excel
                </a>
                <a href="{{ route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'csv']) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-sky-700 bg-sky-50 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-300 dark:border-sky-800 rounded-lg hover:bg-sky-100 dark:hover:bg-sky-900/50 shadow-sm transition">
                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download as CSV
                </a>
            </div>
        </div>
    </div>

    {{-- Status Summary Cards (3 cards) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Successful Card --}}
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Successful</span>
                    <h4 class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ $successCount }}</h4>
                </div>
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-emerald-600 dark:text-emerald-400">Settled in CBS system</p>
        </div>

        {{-- Failed Card --}}
        <div class="rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/50 dark:bg-rose-950/20 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-400">Failed / Error</span>
                    <h4 class="mt-1 text-2xl font-bold text-rose-800 dark:text-rose-200">{{ $failedCount }}</h4>
                </div>
                <div class="p-2 bg-rose-100 dark:bg-rose-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">Validation or CBS callback rejected</p>
        </div>

        {{-- Pending Card --}}
        <div class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-950/20 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Pending</span>
                    <h4 class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">{{ $pendingCount }}</h4>
                </div>
                <div class="p-2 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">Awaiting checker or authorizers</p>
        </div>
    </div>

    {{-- Mini-Table of Transactions --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                Batch Transactions Preview ({{ $transactions->count() }} of {{ $batch->total_data }})
            </h4>
            <span class="text-xs text-gray-500">Read-Only Audit View</span>
        </div>

        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-gray-100/75 dark:bg-gray-800 text-gray-600 dark:text-gray-400 sticky top-0 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="py-2.5 px-3 font-semibold">Ref / Txn ID</th>
                        <th class="py-2.5 px-3 font-semibold">Beneficiary Name</th>
                        <th class="py-2.5 px-3 font-semibold">Beneficiary Account</th>
                        <th class="py-2.5 px-3 font-semibold text-right">Amount (BDT)</th>
                        <th class="py-2.5 px-3 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-sans">
                    @forelse($transactions as $txn)
                        @php
                            $statusLabel = BkashTransaction::statusLabel($txn->status_id);
                            $statusBadge = match($txn->status_id) {
                                1004, 1006 => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                9000, 1007 => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                1000        => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                default     => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                            <td class="py-2 px-3 font-mono font-medium text-gray-900 dark:text-white">
                                {{ $txn->reference_id }}
                                @if($txn->txn_id && $txn->txn_id !== $txn->reference_id)
                                    <span class="block text-[10px] text-gray-500 font-normal">{{ $txn->txn_id }}</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-gray-800 dark:text-gray-200">
                                {{ $txn->debit_account_title ?: 'N/A' }}
                            </td>
                            <td class="py-2 px-3 font-mono text-gray-600 dark:text-gray-300">
                                {{ $txn->beneficiary_account_no }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono font-semibold text-gray-900 dark:text-white">
                                {{ number_format((float) $txn->amount, 2) }}
                            </td>
                            <td class="py-2 px-3 text-center">
                                <span class="inline-block px-2 py-0.5 text-[11px] font-semibold rounded {{ $statusBadge }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No transactions found for this batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Failed Transactions Section if any --}}
    @if($failedList->isNotEmpty())
        <div class="rounded-xl border border-rose-200 dark:border-rose-900/60 overflow-hidden bg-white dark:bg-gray-900">
            <div class="px-4 py-2.5 bg-rose-50 dark:bg-rose-950/30 border-b border-rose-200 dark:border-rose-900/60 flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-rose-800 dark:text-rose-300">
                    Failed Ingestion Rows ({{ $failedList->count() }})
                </h4>
            </div>
            <div class="overflow-x-auto max-h-60">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-rose-100/50 dark:bg-rose-950/40 text-rose-900 dark:text-rose-200 sticky top-0">
                        <tr>
                            <th class="py-2 px-3 font-semibold">Row #</th>
                            <th class="py-2 px-3 font-semibold">Ref No</th>
                            <th class="py-2 px-3 font-semibold">Failure Code</th>
                            <th class="py-2 px-3 font-semibold">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100 dark:divide-rose-900/40">
                        @foreach($failedList as $failed)
                            <tr class="hover:bg-rose-50/30 dark:hover:bg-rose-950/20">
                                <td class="py-2 px-3 font-mono font-medium">{{ $failed->row_number }}</td>
                                <td class="py-2 px-3 font-mono">{{ $failed->reference_id }}</td>
                                <td class="py-2 px-3"><span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-rose-200 text-rose-900 dark:bg-rose-900 dark:text-rose-100">{{ $failed->failure_code }}</span></td>
                                <td class="py-2 px-3 text-rose-700 dark:text-rose-300">{{ $failed->reject_reason }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>