<div class="fi-batch-group-header-strip flex flex-wrap items-center justify-between gap-3 py-1.5 px-2 w-full text-xs" onclick="/* allow parent toggle */">
    <div class="flex items-center gap-4 flex-wrap flex-1">
        <!-- # Index -->
        <div class="flex flex-col min-w-[28px]">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">#</span>
            <span class="font-bold text-gray-700 dark:text-gray-200">{{ $index }}</span>
        </div>

        <!-- File Name -->
        <div class="flex flex-col min-w-[220px] max-w-[320px]">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">File Name</span>
            <span class="font-semibold text-primary-600 dark:text-primary-400 truncate" title="{{ $fileName }}">
                {{ $fileName }}
            </span>
        </div>

        <!-- Channel -->
        <div class="flex flex-col min-w-[70px]">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Channel</span>
            <div>
                @if($channel === 'A2A')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-300 dark:border-sky-800">A2A</span>
                @elseif($channel === 'BEFTN')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-300 dark:border-purple-800">BEFTN</span>
                @elseif($channel === 'RTGS')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">RTGS</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">{{ $channel }}</span>
                @endif
            </div>
        </div>

        <!-- Total Transaction -->
        <div class="flex flex-col min-w-[95px] text-center">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Transaction</span>
            <span class="font-bold text-gray-800 dark:text-gray-200">{{ number_format($totalTrn) }}</span>
        </div>

        <!-- Success Transaction -->
        <div class="flex flex-col min-w-[105px] text-center">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Success Transaction</span>
            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($successTrn) }}</span>
        </div>

        <!-- Failed Transaction -->
        <div class="flex flex-col min-w-[95px] text-center">
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Failed Transaction</span>
            <span class="font-bold {{ $failedTrn > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500' }}">{{ number_format($failedTrn) }}</span>
        </div>

        <!-- Amount -->
        <div class="flex flex-col min-w-[130px] text-right">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Amount</span>
            <span class="font-bold text-gray-900 dark:text-gray-100">BDT {{ $formattedAmount }}</span>
        </div>
    </div>

    <!-- Action: Download Button -->
    <div class="flex flex-col items-center min-w-[90px] mr-2">
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Action</span>
        <a
            href="{{ $downloadUrl }}"
            target="_blank"
            onclick="event.stopPropagation()"
            title="Download original source file"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md shadow-sm text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200 transition dark:bg-primary-950/60 dark:text-primary-300 dark:border-primary-800 dark:hover:bg-primary-900"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            <span>Download</span>
        </a>
    </div>
</div>