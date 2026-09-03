<x-filament-panels::page>
    <style>
        /* ===== JANATA BANK BATCH PIPELINE ENTERPRISE UI SYSTEM ===== */
        .jb-batch-container {
            width: 100%;
            margin-top: 0.25rem;
            font-family: inherit;
        }

        /* High-Contrast Uniform Channel Tabs Bar */
        .jb-tabs-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1.5px solid #cbd5e1;
            margin-bottom: 1.25rem;
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

        /* High-Contrast Distinct Channel Tabs Across Pipeline & Reports */
        .jb-tab-all, .jb-tab-a2a, .jb-tab-beftn, .jb-tab-rtgs {
            padding: 0.5rem 1.15rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            border-radius: 0.5rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease-in-out !important;
            font-family: inherit !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
        }

        /* 1. All Transactions (Slate / Charcoal) */
        .jb-tab-all {
            background-color: #ffffff !important;
            color: #1e293b !important;
            border: 1.5px solid #94a3b8 !important;
        }
        html.dark .jb-tab-all, .dark .jb-tab-all {
            background-color: #1e293b !important;
            border-color: #475569 !important;
            color: #f1f5f9 !important;
        }
        .jb-tab-all:hover {
            background-color: #f1f5f9 !important;
            border-color: #475569 !important;
            color: #0f172a !important;
            transform: translateY(-1px) !important;
        }
        .jb-tab-all.active {
            background-color: #0f172a !important;
            border-color: #020617 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 3px 8px rgba(15, 23, 42, 0.4) !important;
        }
        html.dark .jb-tab-all.active, .dark .jb-tab-all.active {
            background-color: #0284c7 !important;
            border-color: #38bdf8 !important;
            color: #ffffff !important;
        }

        /* 2. A2A (Vibrant Emerald Green) */
        .jb-tab-a2a {
            background-color: #f0fdf4 !important;
            color: #15803d !important;
            border: 1.5px solid #86efac !important;
        }
        html.dark .jb-tab-a2a, .dark .jb-tab-a2a {
            background-color: rgba(16, 185, 129, 0.12) !important;
            border-color: rgba(16, 185, 129, 0.35) !important;
            color: #34d399 !important;
        }
        .jb-tab-a2a:hover {
            background-color: #dcfce7 !important;
            color: #166534 !important;
            border-color: #4ade80 !important;
            transform: translateY(-1px) !important;
        }
        .jb-tab-a2a.active {
            background-color: #10b981 !important;
            border-color: #059669 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.4) !important;
        }
        html.dark .jb-tab-a2a.active, .dark .jb-tab-a2a.active {
            background-color: #10b981 !important;
            border-color: #34d399 !important;
            color: #ffffff !important;
        }

        /* 3. BEFTN (Vibrant Royal Purple) */
        .jb-tab-beftn {
            background-color: #faf5ff !important;
            color: #7e22ce !important;
            border: 1.5px solid #d8b4fe !important;
        }
        html.dark .jb-tab-beftn, .dark .jb-tab-beftn {
            background-color: rgba(139, 92, 246, 0.12) !important;
            border-color: rgba(139, 92, 246, 0.35) !important;
            color: #c084fc !important;
        }
        .jb-tab-beftn:hover {
            background-color: #f3e8ff !important;
            color: #6b21a8 !important;
            border-color: #c084fc !important;
            transform: translateY(-1px) !important;
        }
        .jb-tab-beftn.active {
            background-color: #8b5cf6 !important;
            border-color: #7c3aed !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 3px 10px rgba(139, 92, 246, 0.4) !important;
        }
        html.dark .jb-tab-beftn.active, .dark .jb-tab-beftn.active {
            background-color: #8b5cf6 !important;
            border-color: #a78bfa !important;
            color: #ffffff !important;
        }

        /* 4. RTGS (Vibrant Warm Amber Orange) */
        .jb-tab-rtgs {
            background-color: #fffbeb !important;
            color: #b45309 !important;
            border: 1.5px solid #fde68a !important;
        }
        html.dark .jb-tab-rtgs, .dark .jb-tab-rtgs {
            background-color: rgba(245, 158, 11, 0.12) !important;
            border-color: rgba(245, 158, 11, 0.35) !important;
            color: #fbbf24 !important;
        }
        .jb-tab-rtgs:hover {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            border-color: #fcd34d !important;
            transform: translateY(-1px) !important;
        }
        .jb-tab-rtgs.active {
            background-color: #f59e0b !important;
            border-color: #d97706 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 3px 10px rgba(245, 158, 11, 0.4) !important;
        }
        html.dark .jb-tab-rtgs.active, .dark .jb-tab-rtgs.active {
            background-color: #f59e0b !important;
            border-color: #fbbf24 !important;
            color: #ffffff !important;
        }

        /* Search input */
        .jb-search-box {
            position: relative;
            min-width: 260px;
        }
        .jb-search-input {
            width: 100%;
            padding: 0.45rem 0.75rem 0.45rem 2.25rem;
            font-size: 0.8125rem;
            border: 1.5px solid #94a3b8;
            border-radius: 0.5rem;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease;
            font-family: inherit;
            font-weight: 500;
        }
        html.dark .jb-search-input, .dark .jb-search-input {
            background-color: #0f172a;
            border-color: #475569;
            color: #f8fafc;
        }
        .jb-search-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.25);
        }
        .jb-search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: #64748b;
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
            background-color: #f0f9ff;
            border: 1.5px solid #bae6fd;
            border-radius: 0.5rem;
        }
        html.dark .jb-bulk-bar, .dark .jb-bulk-bar {
            background-color: rgba(2, 132, 199, 0.1);
            border-color: rgba(2, 132, 199, 0.3);
        }

        .jb-btn-verify {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.9rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #ffffff;
            background-color: #059669;
            border: 1px solid #047857;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.15s ease;
            font-family: inherit;
        }
        .jb-btn-verify:hover {
            background-color: #047857;
        }

        /* Master Table Card */
        .jb-table-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow-x: auto;
            width: 100%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        html.dark .jb-table-card, .dark .jb-table-card {
            background-color: #0f172a;
            border-color: #334155;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .jb-master-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8125rem;
            font-family: inherit;
        }

        .jb-master-thead {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        html.dark .jb-master-thead, .dark .jb-master-thead {
            background-color: #1e293b;
            border-bottom-color: #334155;
        }

        .jb-master-thead th {
            padding: 0.75rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 600;
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
            border-bottom-color: #334155;
        }
        .jb-master-row:hover {
            background-color: #f8fafc;
        }
        html.dark .jb-master-row:hover, .dark .jb-master-row:hover {
            background-color: rgba(30, 41, 59, 0.4);
        }

        .jb-master-row td {
            padding: 0.75rem 0.85rem;
            vertical-align: middle;
            color: #1e293b;
            white-space: nowrap;
        }
        html.dark .jb-master-row td, .dark .jb-master-row td {
            color: #f1f5f9;
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
            white-space: nowrap;
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

        /* Expand Button / Trigger - Clean UI with NO URL underline */
        .jb-expand-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            background: none;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0284c7;
            text-align: left;
            padding: 0.2rem 0.4rem;
            font-family: inherit;
            transition: all 0.15s ease;
            text-decoration: none !important;
        }
        html.dark .jb-expand-btn, .dark .jb-expand-btn {
            color: #38bdf8;
        }
        .jb-expand-btn:hover {
            background-color: #f0f9ff;
            border-color: #bae6fd;
            color: #0369a1;
            text-decoration: none !important;
        }
        html.dark .jb-expand-btn:hover, .dark .jb-expand-btn:hover {
            background-color: rgba(2, 132, 199, 0.15);
            border-color: rgba(2, 132, 199, 0.3);
            color: #7dd3fc;
            text-decoration: none !important;
        }
        .jb-expand-chevron {
            width: 14px;
            height: 14px;
            min-width: 14px;
            transition: transform 0.2s ease-in-out;
            display: inline-block;
            color: #0284c7;
        }
        html.dark .jb-expand-chevron, .dark .jb-expand-chevron {
            color: #38bdf8;
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
            background-color: #090d16;
            border-bottom-color: #334155;
        }

        .jb-subtable-wrapper {
            padding: 0.75rem 1rem 1rem 1.5rem;
            overflow-x: auto;
            width: 100%;
            box-sizing: border-box;
        }

        .jb-subtable-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            overflow-x: auto;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            width: 100%;
        }
        html.dark .jb-subtable-card, .dark .jb-subtable-card {
            background-color: #1e293b;
            border-color: #334155;
        }

        .jb-subtable-header-title {
            padding: 0.5rem 0.85rem;
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.725rem;
            font-weight: 600;
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
            min-width: 960px;
            border-collapse: collapse;
            font-size: 0.75rem;
            text-align: left;
            font-family: inherit;
        }
        .jb-subtable th {
            padding: 0.5rem 0.75rem;
            font-size: 0.725rem;
            font-weight: 600;
            background-color: #f8fafc;
            color: #475569;
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
            white-space: nowrap;
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
        <!-- High-Contrast Uniform Channel Tabs & Search Header -->
        <div class="jb-tabs-bar">
            <div class="jb-tabs-group">
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'all')"
                    class="jb-tab-all {{ $activeChannel === 'all' ? 'active' : '' }}"
                >
                    All Transactions
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'a2a')"
                    class="jb-tab-a2a {{ $activeChannel === 'a2a' ? 'active' : '' }}"
                >
                    Account to Account (A2A) - Janata Bank PLC.
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'beftn')"
                    class="jb-tab-beftn {{ $activeChannel === 'beftn' ? 'active' : '' }}"
                >
                    BEFTN
                </button>
                <button
                    type="button"
                    wire:click="$set('activeChannel', 'rtgs')"
                    class="jb-tab-rtgs {{ $activeChannel === 'rtgs' ? 'active' : '' }}"
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
                        wire:click="{{ $actionMethod }}"
                        wire:confirm="Are you sure you want to process the selected batch file(s)?"
                        class="jb-btn-verify"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $actionLabel }}</span>
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
                        <th style="width: 44px; min-width: 44px; text-align: center;">
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                class="jb-checkbox"
                            />
                        </th>
                        <th style="width: 48px; min-width: 48px;">#</th>
                        <th style="min-width: 260px;">File Name</th>
                        <th style="width: 90px; min-width: 90px; text-align: center;">Channel</th>
                        <th style="width: 120px; min-width: 120px; text-align: center;">Total Transaction</th>
                        <th style="width: 130px; min-width: 130px; text-align: center;">Success Transaction</th>
                        <th style="width: 120px; min-width: 120px; text-align: center;">Failed Transaction</th>
                        <th style="width: 140px; min-width: 140px; text-align: right;">Amount BDT</th>
                        <th style="width: 120px; min-width: 120px; text-align: center; padding-right: 1rem;">Action</th>
                    </tr>
                </thead>

                @forelse($batches as $batch)
                    @php
                        $txns = $batch->getBatchTransactions();
                        $totalCount = $batch->total_data ?: $txns->count();
                        $successCount = $txns->whereIn('status_id', [1001, 1002, 1003, 1004, 1006])->count();
                        $failedCount = $txns->whereIn('status_id', [1007, 9000])->count() + $batch->failedTransactions->count();
                        $batchAmount = (float) ($batch->total_amount ?: $txns->sum('amount'));
                        $downloadUrl = route('admin.bkash.download-batch', ['file' => $batch->file_name]);
                    @endphp
                    <!-- Scoped tbody per batch ensures 100% reliable default-collapsed Alpine state -->
                    <tbody x-data="{ open: false }" class="jb-batch-tbody">
                        <tr class="jb-master-row">
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
                            <td style="font-weight: 500; color: #64748b;" class="jb-num">
                                {{ $loop->iteration }}
                            </td>

                            <!-- File Name with expand toggle (No URL Underline) -->
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
                            <td style="text-align: center;">
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
                            <td style="text-align: center; font-weight: 600;" class="jb-num">
                                {{ number_format($totalCount) }}
                            </td>

                            <!-- Success Transaction -->
                            <td style="text-align: center; font-weight: 600; color: #059669;" class="jb-num">
                                {{ number_format($successCount) }}
                            </td>

                            <!-- Failed Transaction -->
                            <td style="text-align: center; font-weight: 600; color: {{ $failedCount > 0 ? '#e11d48' : '#94a3b8' }};" class="jb-num">
                                {{ number_format($failedCount) }}
                            </td>

                            <!-- Amount BDT -->
                            <td style="text-align: right; font-weight: 700; white-space: nowrap;" class="jb-num">
                                BDT {{ number_format($batchAmount, 2) }}
                            </td>

                            <!-- Action: Download Button -->
                            <td style="text-align: center; white-space: nowrap; padding-right: 1rem;" onclick="event.stopPropagation()">
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

                        <!-- Sub-table Row: Child Transactions (Default Collapsed via x-show="open") -->
                        <tr x-show="open" x-cloak class="jb-subtable-row">
                            <td colspan="9" style="padding: 0;">
                                <div class="jb-subtable-wrapper">
                                    <div class="jb-subtable-card">
                                        <div class="jb-subtable-header-title">
                                            <span>Transactions breakdown for {{ $batch->file_name }} ({{ $txns->count() }} items)</span>
                                            <span style="font-weight: 400; color: #64748b;">No select options for individual transaction rows</span>
                                        </div>
                                        <div style="overflow-x: auto; width: 100%;">
                                            <table class="jb-subtable">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 40px; min-width: 40px; text-align: center;">#</th>
                                                        <th style="min-width: 160px;">Ref No</th>
                                                        <th style="width: 70px; min-width: 70px; text-align: center;">Channel</th>
                                                        <th style="min-width: 160px;">Source Account (TCSA/Ops)</th>
                                                        <th style="min-width: 160px;">Beneficiary Name</th>
                                                        <th style="min-width: 160px;">Beneficiary Account</th>
                                                        <th style="min-width: 110px; text-align: right;">Amount (BDT)</th>
                                                        <th style="min-width: 100px; text-align: right;">Routing Number</th>
                                                        <th style="min-width: 120px;">Bank Name</th>
                                                        <th style="min-width: 180px;">Txn ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($txns as $txn)
                                                        <tr>
                                                            <td style="text-align: center; color: #64748b;" class="jb-num">{{ $loop->iteration }}</td>
                                                            <td style="font-weight: 600; color: #0284c7;">{{ $txn->reference_id }}</td>
                                                            <td style="text-align: center;">
                                                                <span class="jb-badge" style="background-color: #f1f5f9; color: #334155; font-size: 10px; padding: 1px 5px;">
                                                                    {{ $txn->transaction_type }}
                                                                </span>
                                                            </td>
                                                            <td style="font-family: monospace;">{{ $txn->source_account_no ?? '0100202707747' }}</td>
                                                            <td style="font-weight: 500;">{{ $txn->debit_account_title }}</td>
                                                            <td style="font-family: monospace;">{{ $txn->beneficiary_account_no }}</td>
                                                            <td style="text-align: right; font-weight: 600; font-family: monospace;" class="jb-num">{{ number_format($txn->amount, 2) }}</td>
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
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="9" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <svg style="width: 36px; height: 36px; color: #cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p style="font-weight: 600; font-size: 0.9375rem; margin: 0; color: #475569;">{{ $emptyHeading ?? 'All Caught Up!' }}</p>
                                    <p style="font-size: 0.8125rem; margin: 0; color: #94a3b8;">{{ $emptyDescription ?? 'No files currently pending action.' }}</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #64748b; padding: 0.5rem 0.25rem;">
            <span>Showing {{ $batches->count() }} batch file(s) pending action</span>
            <span>All batch files default collapsed on load & refresh</span>
        </div>
    </div>
</x-filament-panels::page>