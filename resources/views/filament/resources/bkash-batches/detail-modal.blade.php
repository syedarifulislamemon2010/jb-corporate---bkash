@php
    use App\Models\BkashTransaction;

    $successCount = $batch->transactions()->count();
    $failedCount = $batch->failedTransactions()->count();

    $pendingCount = $batch->transactions()->whereIn('status_id', [
        BkashTransaction::STATUS_PENDING_CHECKER,
        BkashTransaction::STATUS_CHECKED,
        BkashTransaction::STATUS_AUTH_1_APPROVED,
        BkashTransaction::STATUS_FINAL_AUTHORIZED,
    ])->count();

    $channel = $batch->transaction_type ?? 'A2A';
    $channelBadgeClass = match ($channel) {
        'RTGS'  => 'jb-badge-rtgs',
        'BEFTN' => 'jb-badge-beftn',
        'A2A'   => 'jb-badge-a2a',
        default => 'jb-badge-default',
    };

    $transactions = $batch->transactions()->orderBy('row_sequence', 'asc')->limit(100)->get();
    $failedList = $batch->failedTransactions()->limit(50)->get();
@endphp

<div class="jb-batch-modal-wrapper">
    <style>
        .jb-batch-modal-wrapper {
            font-family: inherit;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            gap: 1.125rem;
            width: 100%;
            padding: 0.25rem 0;
        }
        html.dark .jb-batch-modal-wrapper, .dark .jb-batch-modal-wrapper {
            color: #f1f5f9;
        }

        /* SVG Sizing Safety — Strict boundary enforcement */
        .jb-batch-modal-wrapper svg {
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }

        /* Header Card */
        .jb-modal-header-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        html.dark .jb-modal-header-card, .dark .jb-modal-header-card {
            background-color: #0f172a;
            border-color: #334155;
        }

        .jb-modal-top-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .jb-modal-meta-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        html.dark .jb-modal-meta-label, .dark .jb-modal-meta-label {
            color: #94a3b8;
        }
        .jb-modal-file-title {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.9375rem;
            font-weight: 700;
            color: #0f172a;
            word-break: break-all;
            margin: 0.25rem 0;
            line-height: 1.4;
        }
        html.dark .jb-modal-file-title, .dark .jb-modal-file-title {
            color: #f8fafc;
        }
        .jb-modal-subtext {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0;
        }
        html.dark .jb-modal-subtext, .dark .jb-modal-subtext {
            color: #94a3b8;
        }

        .jb-modal-badges-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .jb-modal-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            letter-spacing: 0.025em;
        }
        .jb-badge-rtgs {
            background-color: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }
        .jb-badge-beftn {
            background-color: #f5f3ff;
            color: #6d28d9;
            border-color: #ddd6fe;
        }
        .jb-badge-a2a {
            background-color: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .jb-badge-default {
            background-color: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }
        .jb-badge-total {
            background-color: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        html.dark .jb-badge-rtgs { background-color: rgba(245, 158, 11, 0.25); color: #fbbf24; border-color: rgba(245, 158, 11, 0.4); }
        html.dark .jb-badge-beftn { background-color: rgba(139, 92, 246, 0.25); color: #c084fc; border-color: rgba(139, 92, 246, 0.4); }
        html.dark .jb-badge-a2a { background-color: rgba(16, 185, 129, 0.25); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.4); }
        html.dark .jb-badge-total { background-color: rgba(3, 105, 161, 0.3); color: #7dd3fc; border-color: #0284c7; }

        /* Export Bar */
        .jb-modal-export-bar {
            margin-top: 0.875rem;
            padding-top: 0.875rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        html.dark .jb-modal-export-bar, .dark .jb-modal-export-bar {
            border-top-color: #334155;
        }
        .jb-btn-excel, .jb-btn-csv {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .jb-btn-excel {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .jb-btn-excel:hover {
            background-color: #d1fae5;
            color: #065f46;
        }
        .jb-btn-csv {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }
        .jb-btn-csv:hover {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        html.dark .jb-btn-excel {
            background-color: rgba(6, 78, 59, 0.4);
            color: #6ee7b7;
            border-color: #059669;
        }
        html.dark .jb-btn-excel:hover {
            background-color: rgba(6, 78, 59, 0.6);
        }
        html.dark .jb-btn-csv {
            background-color: rgba(3, 105, 161, 0.4);
            color: #7dd3fc;
            border-color: #0284c7;
        }
        html.dark .jb-btn-csv:hover {
            background-color: rgba(3, 105, 161, 0.6);
        }

        /* Metrics 3-Card Grid */
        .jb-metrics-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.875rem;
            width: 100%;
        }
        @media (max-width: 640px) {
            .jb-metrics-row {
                grid-template-columns: 1fr;
            }
        }
        .jb-metric-card {
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .jb-metric-card-success {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }
        html.dark .jb-metric-card-success, .dark .jb-metric-card-success {
            background-color: rgba(6, 78, 59, 0.25);
            border-color: rgba(5, 150, 105, 0.4);
        }
        .jb-metric-card-failed {
            background-color: #fff1f2;
            border-color: #fecdd3;
        }
        html.dark .jb-metric-card-failed, .dark .jb-metric-card-failed {
            background-color: rgba(136, 19, 55, 0.25);
            border-color: rgba(225, 29, 72, 0.4);
        }
        .jb-metric-card-pending {
            background-color: #fffbeb;
            border-color: #fde68a;
        }
        html.dark .jb-metric-card-pending, .dark .jb-metric-card-pending {
            background-color: rgba(120, 53, 15, 0.25);
            border-color: rgba(217, 119, 6, 0.4);
        }
        .jb-metric-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .jb-metric-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .jb-metric-label-success { color: #15803d; }
        .jb-metric-label-failed { color: #be123c; }
        .jb-metric-label-pending { color: #b45309; }
        html.dark .jb-metric-label-success { color: #86efac; }
        html.dark .jb-metric-label-failed { color: #fda4af; }
        html.dark .jb-metric-label-pending { color: #fde047; }

        .jb-metric-count {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 0.35rem;
            font-family: inherit;
        }
        .jb-metric-count-success { color: #166534; }
        .jb-metric-count-failed { color: #9f1239; }
        .jb-metric-count-pending { color: #92400e; }
        html.dark .jb-metric-count-success, .dark .jb-metric-count-success { color: #86efac; }
        html.dark .jb-metric-count-failed, .dark .jb-metric-count-failed { color: #fca5a5; }
        html.dark .jb-metric-count-pending, .dark .jb-metric-count-pending { color: #fde047; }

        .jb-metric-icon-box {
            width: 2.25rem;
            height: 2.25rem;
            min-width: 2.25rem;
            min-height: 2.25rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .jb-metric-icon-box-success {
            background-color: #dcfce7;
            color: #16a34a;
        }
        .jb-metric-icon-box-failed {
            background-color: #ffe4e6;
            color: #e11d48;
        }
        .jb-metric-icon-box-pending {
            background-color: #fef3c7;
            color: #d97706;
        }
        html.dark .jb-metric-icon-box-success { background-color: rgba(22, 163, 74, 0.3); color: #86efac; }
        html.dark .jb-metric-icon-box-failed { background-color: rgba(225, 29, 72, 0.3); color: #fda4af; }
        html.dark .jb-metric-icon-box-pending { background-color: rgba(217, 119, 6, 0.3); color: #fde047; }

        .jb-metric-desc {
            font-size: 0.6875rem;
            margin-top: 0.5rem;
            margin-bottom: 0;
            color: #64748b;
        }
        html.dark .jb-metric-desc {
            color: #94a3b8;
        }

        /* Table Card */
        .jb-modal-table-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            width: 100%;
        }
        html.dark .jb-modal-table-card, .dark .jb-modal-table-card {
            background-color: #0f172a;
            border-color: #334155;
        }
        .jb-modal-table-header {
            padding: 0.625rem 1rem;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        html.dark .jb-modal-table-header, .dark .jb-modal-table-header {
            background-color: #1e293b;
            border-bottom-color: #334155;
        }
        .jb-table-heading {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #334155;
            margin: 0;
        }
        html.dark .jb-table-heading {
            color: #cbd5e1;
        }
        .jb-table-scroll-container {
            overflow-x: auto;
            max-height: 20rem;
            width: 100%;
        }
        .jb-modal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            text-align: left;
        }
        .jb-modal-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        html.dark .jb-modal-table th, .dark .jb-modal-table th {
            background-color: #1e293b;
            color: #94a3b8;
            border-bottom-color: #334155;
        }
        .jb-modal-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        html.dark .jb-modal-table td, .dark .jb-modal-table td {
            border-bottom-color: #1e293b;
            color: #cbd5e1;
        }
        .jb-modal-table tr:hover td {
            background-color: #f8fafc;
        }
        html.dark .jb-modal-table tr:hover td, .dark .jb-modal-table tr:hover td {
            background-color: #1e293b;
        }

        .jb-status-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            border-radius: 0.25rem;
            text-align: center;
            white-space: nowrap;
        }
        .jb-status-success {
            background-color: #dcfce7;
            color: #15803d;
        }
        .jb-status-danger {
            background-color: #ffe4e6;
            color: #be123c;
        }
        .jb-status-warning {
            background-color: #fef3c7;
            color: #b45309;
        }
        .jb-status-gray {
            background-color: #f1f5f9;
            color: #475569;
        }
        html.dark .jb-status-success { background-color: rgba(21, 128, 61, 0.3); color: #86efac; }
        html.dark .jb-status-danger { background-color: rgba(190, 18, 60, 0.3); color: #fda4af; }
        html.dark .jb-status-warning { background-color: rgba(180, 83, 9, 0.3); color: #fde047; }
        html.dark .jb-status-gray { background-color: rgba(71, 85, 105, 0.3); color: #cbd5e1; }
    </style>

    {{-- Header Metadata --}}
    <div class="jb-modal-header-card">
        <div class="jb-modal-top-row">
            <div>
                <span class="jb-modal-meta-label">Batch File Name</span>
                <h3 class="jb-modal-file-title">{{ $batch->file_name }}</h3>
                <p class="jb-modal-subtext">
                    Uploaded by <strong style="font-weight: 600; color: #334155;">{{ $batch->created_by ?? 'SYSTEM' }}</strong>
                    on {{ $batch->create_date?->format('d M Y, h:i A') ?? $batch->created_at?->format('d M Y, h:i A') }}
                </p>
            </div>
            <div class="jb-modal-badges-group">
                <span class="jb-modal-badge {{ $channelBadgeClass }}">
                    {{ $channel }}
                </span>
                <span class="jb-modal-badge jb-badge-total">
                    {{ $batch->total_data ?? $batch->transactions()->count() }} Total
                </span>
            </div>
        </div>

        {{-- Export Action Buttons --}}
        <div class="jb-modal-export-bar">
            <span style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Export Transactions:</span>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <a href="{{ route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'xlsx']) }}"
                   target="_blank"
                   class="jb-btn-excel">
                    <svg width="15" height="15" style="width: 15px; height: 15px; min-width: 15px; min-height: 15px; max-width: 15px; max-height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Download as Excel</span>
                </a>
                <a href="{{ route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'csv']) }}"
                   target="_blank"
                   class="jb-btn-csv">
                    <svg width="15" height="15" style="width: 15px; height: 15px; min-width: 15px; min-height: 15px; max-width: 15px; max-height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Download as CSV</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Status Summary Cards (3 cards) --}}
    <div class="jb-metrics-row">
        {{-- Successful Card --}}
        <div class="jb-metric-card jb-metric-card-success">
            <div>
                <div class="jb-metric-header-flex">
                    <span class="jb-metric-label jb-metric-label-success">Successful</span>
                    <div class="jb-metric-icon-box jb-metric-icon-box-success">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px; max-width: 18px; max-height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <div class="jb-metric-count jb-metric-count-success">{{ $successCount }}</div>
            </div>
            <p class="jb-metric-desc" style="color: #15803d;">Settled in CBS system</p>
        </div>

        {{-- Failed Card --}}
        <div class="jb-metric-card jb-metric-card-failed">
            <div>
                <div class="jb-metric-header-flex">
                    <span class="jb-metric-label jb-metric-label-failed">Failed / Error</span>
                    <div class="jb-metric-icon-box jb-metric-icon-box-failed">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px; max-width: 18px; max-height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <div class="jb-metric-count jb-metric-count-failed">{{ $failedCount }}</div>
            </div>
            <p class="jb-metric-desc" style="color: #be123c;">Validation or CBS callback rejected</p>
        </div>

        {{-- Pending Card --}}
        <div class="jb-metric-card jb-metric-card-pending">
            <div>
                <div class="jb-metric-header-flex">
                    <span class="jb-metric-label jb-metric-label-pending">Pending</span>
                    <div class="jb-metric-icon-box jb-metric-icon-box-pending">
                        <svg width="18" height="18" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px; max-width: 18px; max-height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="jb-metric-count jb-metric-count-pending">{{ $pendingCount }}</div>
            </div>
            <p class="jb-metric-desc" style="color: #b45309;">Awaiting checker or authorizers</p>
        </div>
    </div>

    {{-- Mini-Table of Transactions --}}
    <div class="jb-modal-table-card">
        <div class="jb-modal-table-header">
            <h4 class="jb-table-heading">
                Batch Transactions Preview ({{ $transactions->count() }} of {{ $batch->total_data }})
            </h4>
            <span style="font-size: 0.6875rem; color: #64748b; font-weight: 500;">Read-Only Audit View</span>
        </div>

        <div class="jb-table-scroll-container">
            <table class="jb-modal-table">
                <thead>
                    <tr>
                        <th>Ref / Txn ID</th>
                        <th>Beneficiary Name</th>
                        <th>Beneficiary Account</th>
                        <th style="text-align: right;">Amount (BDT)</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        @php
                            $statusLabel = BkashTransaction::statusLabel($txn->status_id);
                            $statusBadge = match($txn->status_id) {
                                1004, 1006 => 'jb-status-success',
                                9000, 1007 => 'jb-status-danger',
                                1000        => 'jb-status-warning',
                                default     => 'jb-status-gray',
                            };
                        @endphp
                        <tr>
                            <td style="font-family: ui-monospace, monospace; font-weight: 600;">
                                {{ $txn->reference_id }}
                                @if($txn->txn_id && $txn->txn_id !== $txn->reference_id)
                                    <span style="display: block; font-size: 0.625rem; color: #64748b; font-weight: normal;">{{ $txn->txn_id }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $txn->debit_account_title ?: 'N/A' }}
                            </td>
                            <td style="font-family: ui-monospace, monospace; color: #475569;">
                                {{ $txn->beneficiary_account_no }}
                            </td>
                            <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 700;">
                                {{ number_format((float) $txn->amount, 2) }}
                            </td>
                            <td style="text-align: center;">
                                <span class="jb-status-badge {{ $statusBadge }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 1.5rem; text-align: center; color: #64748b;">No transactions found for this batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Failed Transactions Section if any --}}
    @if($failedList->isNotEmpty())
        <div class="jb-modal-table-card" style="border-color: #fecdd3;">
            <div class="jb-modal-table-header" style="background-color: #fff1f2; border-bottom-color: #fecdd3;">
                <h4 class="jb-table-heading" style="color: #9f1239;">
                    Failed Ingestion Rows ({{ $failedList->count() }})
                </h4>
            </div>
            <div class="jb-table-scroll-container">
                <table class="jb-modal-table">
                    <thead>
                        <tr style="background-color: #ffe4e6;">
                            <th style="color: #9f1239; background-color: #ffe4e6;">Row #</th>
                            <th style="color: #9f1239; background-color: #ffe4e6;">Ref No</th>
                            <th style="color: #9f1239; background-color: #ffe4e6;">Failure Code</th>
                            <th style="color: #9f1239; background-color: #ffe4e6;">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedList as $failed)
                            <tr>
                                <td style="font-family: ui-monospace, monospace; font-weight: 600;">{{ $failed->row_number }}</td>
                                <td style="font-family: ui-monospace, monospace;">{{ $failed->reference_id }}</td>
                                <td>
                                    <span class="jb-status-badge jb-status-danger">{{ $failed->failure_code }}</span>
                                </td>
                                <td style="color: #be123c;">{{ $failed->reject_reason }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>