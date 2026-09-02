<x-filament-panels::page>
    <style>
        /* ===== JANATA BANK BATCH VERIFICATION UI SYSTEM ===== */
        .jb-batch-container {
            width: 100%;
            margin-top: 0.25rem;
        }

        /* Tabs Bar */
        .jb-tabs-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        html.dark .jb-tabs-bar, .dark .jb-tabs-bar {
            border-bottom-color: #334155;
        }

        .jb-tabs-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .jb-tab-btn {
            padding: 0.4rem 0.85rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            background-color: #f8fafc;
            color: #475569;
        }
        html.dark .jb-tab-btn, .dark .jb-tab-btn {
            background-color: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
        .jb-tab-btn:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }
        html.dark .jb-tab-btn:hover, .dark .jb-tab-btn:hover {
            background-color: #334155;
            color: #f8fafc;
        }
        .jb-tab-btn.active {
            background-color: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.08);
        }

        .jb-search-box {
            position: relative;
            min-width: 260px;
        }
        .jb-search-input {
            width: 100%;
            padding: 0.45rem 0.75rem 0.45rem 2.25rem;
            font-size: 0.8125rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease;
        }
        html.dark .jb-search-input, .dark .jb-search-input {
            background-color: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .jb-search-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
        }
        .jb-search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: #94a3b8;
            pointer-events: none;
        }

        /* Bulk Action Bar */
        .jb-bulk-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.65rem 1rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.08), rgba(2, 132, 199, 0.03));
            border: 1px solid rgba(2, 132, 199, 0.3);
            border-radius: 0.5rem;
        }
        html.dark .jb-bulk-bar, .dark .jb-bulk-bar {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.12), rgba(15, 23, 42, 0.6));
            border-color: rgba(56, 189, 248, 0.3);
        }

        .jb-btn-verify {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #ffffff;
            background-color: #059669;
            border: 1px solid #047857;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .jb-btn-verify:hover {
            background-color: #047857;
        }

        /* Master Table Card */
        .jb-table-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        html.dark .jb-table-card, .dark .jb-table-card {
            background-color: #0f172a;
            border-color: #334155;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .jb-master-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8125rem;
        }

        .jb-master-thead {
            background-color: #f1f5f9;
            border-bottom: 2px solid #cbd5e1;
        }
        html.dark .jb-master-thead, .dark .jb-master-thead {
            background-color: #1e293b;
            border-bottom-color: #334155;
        }

        .jb-master-thead th {
            padding: 0.75rem 0.85rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #475569;
            white-space: nowrap;
        }
        html.dark .jb-master-thead th, .dark .jb-master-thead th {
            color: #94a3b8;
        }

        /* Master Row */
        .jb-master-row {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.15s ease;
        }
        html.dark .jb-master-row, .dark .jb-master-row {
            border-bottom-color: #1e293b;
        }
        .jb-master-row:hover {
            background-color: #f8fafc;
        }
        html.dark .jb-master-row:hover, .dark .jb-master-row:hover {
            background-color: rgba(30, 41, 59, 0.5);
        }

        .jb-master-row td {
            padding: 0.85rem 0.85rem;
            vertical-align: middle;
            color: #1e293b;
        }
        html.dark .jb-master-row td, .dark .jb-master-row td {
            color: #e2e8f0;
        }

        /* Badges */
        .jb-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            border: 1px solid transparent;
        }
        .jb-badge-a2a {
            background-color: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        html.dark .jb-badge-a2a, .dark .jb-badge-a2a {
            background-color: rgba(2, 132, 199, 0.2);
            color: #38bdf8;
            border-color: rgba(2, 132, 199, 0.4);
        }
        .jb-badge-beftn {
            background-color: #f3e8ff;
            color: #7e22ce;
            border-color: #e9d5ff;
        }
        html.dark .jb-badge-beftn, .dark .jb-badge-beftn {
            background-color: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border-color: rgba(139, 92, 246, 0.4);
        }
        .jb-badge-rtgs {
            background-color: #ffedd5;
            color: #c2410c;
            border-color: #fed7aa;
        }
        html.dark .jb-badge-rtgs, .dark .jb-badge-rtgs {
            background-color: rgba(234, 88, 12, 0.2);
            color: #fb923c;
            border-color: rgba(234, 88, 12, 0.4);
        }

        /* Download Button */
        .jb-btn-download {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.7rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #0284c7;
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.375rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        html.dark .jb-btn-download, .dark .jb-btn-download {
            background-color: rgba(2, 132, 199, 0.15);
            color: #38bdf8;
            border-color: rgba(2, 132, 199, 0.3);
        }
        .jb-btn-download:hover {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        html.dark .jb-btn-download:hover, .dark .jb-btn-download:hover {
            background-color: rgba(2, 132, 199, 0.3);
            color: #7dd3fc;
        }

        /* Expand Button / Trigger */
        .jb-expand-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0284c7;
            text-align: left;
            padding: 0;
        }
        html.dark .jb-expand-btn, .dark .jb-expand-btn {
            color: #38bdf8;
        }
        .jb-expand-btn:hover {
            text-decoration: underline;
        }
        .jb-expand-chevron {
            width: 14px;
            height: 14px;
            min-width: 14px;
            transition: transform 0.2s ease-in-out;
            display: inline-block;
        }
        .jb-expand-chevron.is-open {
            transform: rotate(90deg);
        }

        /* Sub-table Container */
        .jb-subtable-row {
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
        }
        html.dark .jb-subtable-row, .dark .jb-subtable-row {
            background-color: rgba(15, 23, 42, 0.95);
            border-bottom-color: #334155;
        }

        .jb-subtable-wrapper {
            padding: 0.75rem 1rem 1rem 2rem;
        }

        .jb-subtable-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        html.dark .jb-subtable-card, .dark .jb-subtable-card {
            background-color: #1e293b;
            border-color: #334155;
        }

        .jb-subtable-header-title {
            padding: 0.5rem 0.85rem;
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        html.dark .jb-subtable-header-title, .dark .jb-subtable-header-title {
            background-color: #0f172a;
            border-bottom-color: #334155;
            color: #94a3b8;
        }

        .jb-subtable {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            text-align: left;
        }
        .jb-subtable th {
            padding: 0.5rem 0.75rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            background-color: #f8fafc;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        html.dark .jb-subtable th, .dark .jb-subtable th {
            background-color: #1e293b;
            color: #94a3b8;
            border-bottom-color: #334155;
        }

        .jb-subtable td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        html.dark .jb-subtable td, .dark .jb-subtable td {
            border-bottom-color: #334155;
            color: #cbd5e1;
        }

        .jb-subtable tr:hover td {
            background-color: #f8fafc;
        }
        html.dark .jb-subtable tr:hover td, .dark .jb-subtable tr:hover td {
            background-color: rgba(15, 23, 42, 0.4);
        }

        .jb-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            cursor: pointer;
            accent-color: #0284c7;
        }

        .jb-num {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1;
        }
        [x-cloak] { display: none !important; }
    </style>

    <div class="jb-batch-container">
        <!-- Tabs & Search Header -->
        <div class="jb-tabs-bar">
            <div class="jb-tabs-group">
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'all')"
                    class="jb-tab-btn {{ $activeChannel === 'all' ? 'active' : '' }}"
                >
                    All Transmissions
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'a2a')"
                    class="jb-tab-btn {{ $activeChannel === 'a2a' ? 'active' : '' }}"
                >
                    Account to Account (A2A) - Janata Bank PLC.
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'beftn')"
                    class="jb-tab-btn {{ $activeChannel === 'beftn' ? 'active' : '' }}"
                >
                    BEFTN
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'rtgs')"
                    class="jb-tab-btn {{ $activeChannel === 'rtgs' ? 'active' : '' }}"
                >
                    RTGS
                </button>
            </div>

            <!-- Search input -->
            <div class="jb-search-box">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="Search File Name, Channel..."
                    class="jb-search-input"
                />
                <svg class="jb-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Bulk Action Toolbar (appears when batches are selected) -->
        @if(count($selectedBatches) > 0)
            <div class="jb-bulk-bar">
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; font-weight: 600; color: #0369a1;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background-color: #0284c7; color: #fff; font-size: 11px; font-weight: 700;">
                        {{ count($selectedBatches) }}
                    </span>
                    <span>batch file(s) selected</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button
                        type="button"
                        wire:click="checkSelectedBatches"
                        wire:confirm="Are you sure you want to verify all transactions in the selected batch file(s) as Checker?"
                        class="jb-btn-verify"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Check Selected Batch Files</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedBatches', [])"
                        style="padding: 0.4rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #64748b; background: none; border: none; cursor: pointer;"
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
        <div class="jb-table-card">
            <table class="jb-master-table">
                <thead class="jb-master-thead">
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                class="jb-checkbox"
                            />
                        </th>
                        <th style="width: 50px;">#</th>
                        <th>File Name</th>
                        <th>Channel</th>
                        <th style="text-align: center;">Total Transaction</th>
                        <th style="text-align: center;">Success Transaction</th>
                        <th style="text-align: center;">Failed Transaction</th>
                        <th style="text-align: right;">Amount BDT</th>
                        <th style="text-align: center; width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
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
                            class="jb-master-row"
                        >
                            <!-- Checkbox (Only on file row) -->
                            <td style="text-align: center;" onclick="event.stopPropagation()">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedBatches"
                                    value="{{ $batch->id }}"
                                    class="jb-checkbox"
                                />
                            </td>

                            <!-- # Index -->
                            <td style="font-weight: 600; color: #64748b;" class="jb-num">
                                {{ $loop->iteration }}
                            </td>

                            <!-- File Name with expand toggle -->
                            <td>
                                <button
                                    type="button"
                                    class="jb-expand-btn"
                                    @click="open = !open"
                                    title="Click to expand or collapse child transactions"
                                >
                                    <svg class="jb-expand-chevron" :class="{ 'is-open': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <span>{{ $batch->file_name }}</span>
                                </button>
                            </td>

                            <!-- Channel -->
                            <td>
                                @if($batch->transaction_type === 'A2A')
                                    <span class="jb-badge jb-badge-a2a">A2A</span>
                                @elseif($batch->transaction_type === 'BEFTN')
                                    <span class="jb-badge jb-badge-beftn">BEFTN</span>
                                @elseif($batch->transaction_type === 'RTGS')
                                    <span class="jb-badge jb-badge-rtgs">RTGS</span>
                                @else
                                    <span class="jb-badge" style="background-color: #f1f5f9; color: #475569;">{{ $batch->transaction_type }}</span>
                                @endif
                            </td>

                            <!-- Total Transaction -->
                            <td style="text-align: center; font-weight: 700;" class="jb-num">
                                {{ number_format($totalCount) }}
                            </td>

                            <!-- Success Transaction -->
                            <td style="text-align: center; font-weight: 700; color: #059669;" class="jb-num">
                                {{ number_format($successCount) }}
                            </td>

                            <!-- Failed Transaction -->
                            <td style="text-align: center; font-weight: 700; color: {{ $failedCount > 0 ? '#e11d48' : '#94a3b8' }};" class="jb-num">
                                {{ number_format($failedCount) }}
                            </td>

                            <!-- Amount BDT -->
                            <td style="text-align: right; font-weight: 700; white-space: nowrap;" class="jb-num">
                                BDT {{ number_format($batchAmount, 2) }}
                            </td>

                            <!-- Action: Download Button -->
                            <td style="text-align: center; white-space: nowrap;" onclick="event.stopPropagation()">
                                <a
                                    href="{{ $downloadUrl }}"
                                    target="_blank"
                                    title="Download original batch file"
                                    class="jb-btn-download"
                                >
                                    <svg style="width: 14px; height: 14px; min-width: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <span>Download</span>
                                </a>
                            </td>
                        </tr>

                        <!-- Sub-table Row: Child Transactions (Collapsed by Default) -->
                        <tr x-show="open" x-cloak class="jb-subtable-row">
                            <td colspan="9" style="padding: 0;">
                                <div class="jb-subtable-wrapper">
                                    <div class="jb-subtable-card">
                                        <div class="jb-subtable-header-title">
                                            <span>Transactions breakdown for {{ $batch->file_name }} ({{ $txns->count() }} items)</span>
                                            <span style="font-weight: 400; text-transform: none; color: #64748b;">No select option for individual transactions</span>
                                        </div>
                                        <div style="overflow-x: auto;">
                                            <table class="jb-subtable">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 40px; text-align: center;">#</th>
                                                        <th>Ref No</th>
                                                        <th>Channel</th>
                                                        <th>Source Account (TCSA/Ops)</th>
                                                        <th>Beneficiary Name</th>
                                                        <th>Beneficiary Account</th>
                                                        <th style="text-align: right;">Amount (BDT)</th>
                                                        <th style="text-align: right;">Routing Number</th>
                                                        <th>Bank Name</th>
                                                        <th>Txn ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($txns as $txn)
                                                        <tr>
                                                            <td style="text-align: center; color: #64748b;" class="jb-num">{{ $loop->iteration }}</td>
                                                            <td style="font-weight: 600; color: #0284c7;">{{ $txn->reference_id }}</td>
                                                            <td>
                                                                <span class="jb-badge" style="background-color: #f1f5f9; color: #334155; font-size: 10px; padding: 1px 5px;">
                                                                    {{ $txn->transaction_type }}
                                                                </span>
                                                            </td>
                                                            <td style="font-family: monospace;">{{ $txn->source_account_no ?? '0100202707747' }}</td>
                                                            <td style="font-weight: 500;">{{ $txn->debit_account_title }}</td>
                                                            <td style="font-family: monospace;">{{ $txn->beneficiary_account_no }}</td>
                                                            <td style="text-align: right; font-weight: 700; font-family: monospace;" class="jb-num">{{ number_format($txn->amount, 2) }}</td>
                                                            <td style="text-align: right; font-family: monospace;" class="jb-num">{{ $txn->credit_routing ?? '-' }}</td>
                                                            <td>{{ $txn->credit_bank ?? '-' }}</td>
                                                            <td style="font-family: monospace; color: #64748b;">{{ $txn->txn_id }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="10" style="padding: 1rem; text-align: center; color: #94a3b8;">
                                                                No transactions found for this batch.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <svg style="width: 36px; height: 36px; color: #cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p style="font-weight: 700; font-size: 0.9375rem; margin: 0; color: #475569;">All Caught Up!</p>
                                    <p style="font-size: 0.8125rem; margin: 0; color: #94a3b8;">No files are currently pending Checker verification.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #64748b; padding: 0.5rem 0.25rem;">
            <span>Showing {{ $batches->count() }} batch file(s) pending Checker verification</span>
            <span>All batch files default collapsed on load & refresh</span>
        </div>
    </div>
</x-filament-panels::page>