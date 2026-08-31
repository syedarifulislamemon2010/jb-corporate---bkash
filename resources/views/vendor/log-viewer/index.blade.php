<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if ($assetsPublished)
        <link rel="shortcut icon" href="{{ asset(mix('img/log-viewer-32.png', config('log-viewer.assets_path'))) }}">
    @else
        {!! \Opcodes\LogViewer\Facades\LogViewer::favicon() !!}
    @endif

    <title>System Log Viewer | Janata Bank Corporate Portal</title>

    <!-- Google Inter & JetBrains Mono Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Style sheets-->
    @if ($assetsPublished)
        <link href="{{ asset(mix('app.css', config('log-viewer.assets_path'))) }}" rel="stylesheet" onerror="alert('app.css failed to load. Please refresh the page, re-publish Log Viewer assets, or fix routing for vendor assets.')">
    @else
        {!! \Opcodes\LogViewer\Facades\LogViewer::css() !!}
    @endif

    <style>
        /* ═══════════════════════════════════════════════════════════════════
           JANATA BANK CORPORATE — CLEAN ENTERPRISE LOG VIEWER THEME
           ═══════════════════════════════════════════════════════════════════ */

        :root {
            --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', 'Fira Code', ui-monospace, SFMono-Regular, monospace;
        }

        body {
            font-family: var(--font-sans) !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        code, pre, .font-mono, [class*="font-mono"] {
            font-family: var(--font-mono) !important;
            font-feature-settings: "liga" 0, "tnum" 1;
        }

        /* ─── 1. HIDE ALL SPONSOR / GITHUB / EXTERNAL DOC LINKS ─── */
        /* Hide GitHub logo in header */
        a[href*="github.com"] {
            display: none !important;
        }

        /* Hide "Buy me a coffee" in footer and dropdown menu */
        a[href*="buymeacoffee"],
        a[href*="buymeacoffee.com"] {
            display: none !important;
        }

        /* Hide external Help and Documentation in dropdown menu */
        a[href*="log-viewer.opcodes.io"] {
            display: none !important;
        }

        /* Hide the bottom performance stats / version text line.
           The stats <p> lives inside a unique absolute-positioned
           container: <div class="absolute bottom-4 right-4 flex items-center">.
           Scoping to that parent avoids hiding unrelated .text-xs
           elements (timestamps, badge subtitles, etc.). */
        .absolute.bottom-4.right-4 .text-xs {
            display: none !important;
        }

        /* Also hide the "Buy me a coffee" badge sitting next to the stats */
        .absolute.bottom-4.right-4 a[href*="buymeacoffee"] {
            display: none !important;
        }

        /* ─── 2. POLISHED "BACK TO DASHBOARD" BUTTON (Top of Sidebar) ─── */
        /* Exact-match only — no substring wildcard, so log entries
           containing "/admin" in their text are never affected. */
        a[href="/admin"] {
            font-weight: 600 !important;
            border-radius: 8px !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* ─── 3. SUBTLE JANATA BANK THEME TOUCHES ─── */
        /* Smooth table hover */
        tbody tr {
            transition: background-color 0.15s ease !important;
        }

        tbody tr:hover {
            background-color: rgba(14, 165, 233, 0.04) !important;
        }

        .dark tbody tr:hover {
            background-color: rgba(14, 165, 233, 0.08) !important;
        }

        /* ─── 4. BEAUTIFUL HORIZONTAL PAGINATION (Previous / Next Buttons) ─── */
        nav.pagination,
        .pagination {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: nowrap !important;
            gap: 0.75rem !important;
            padding: 0.75rem 0.5rem !important;
            border-top: 1px solid #1e293b !important;
            margin-top: auto !important;
        }

        html:not(.dark) nav.pagination,
        html:not(.dark) .pagination {
            border-top-color: #e2e8f0 !important;
        }

        /* Previous & Next Buttons */
        .pagination .previous,
        .pagination .next {
            display: flex !important;
            align-items: center !important;
            flex-shrink: 0 !important;
        }

        .pagination .previous button,
        .pagination .next button {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.4rem !important;
            padding: 0.4rem 0.85rem !important;
            font-size: 0.825rem !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            background-color: #1e293b !important;
            color: #38bdf8 !important;
            border: 1px solid #334155 !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
        }

        html:not(.dark) .pagination .previous button,
        html:not(.dark) .pagination .next button {
            background-color: #f8fafc !important;
            color: #0369a1 !important;
            border-color: #cbd5e1 !important;
        }

        .pagination .previous button:hover,
        .pagination .next button:hover {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            border-color: #0284c7 !important;
        }

        /* Ensure Previous and Next text labels are visible */
        .pagination .previous button span,
        .pagination .next button span {
            display: inline !important;
        }

        /* Page Numbers Container */
        .pagination .pages {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-wrap: wrap !important;
            gap: 0.25rem !important;
        }

        .pagination .pages button,
        .pagination .pages span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 30px !important;
            height: 30px !important;
            padding: 0 0.35rem !important;
            border-radius: 6px !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            border: none !important;
        }

        .pagination .pages button[aria-current="page"],
        .pagination .pages button.border-brand-500 {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.4) !important;
        }

        .pagination .pages button:not([aria-current="page"]) {
            color: #94a3b8 !important;
            background-color: transparent !important;
        }

        .pagination .pages button:not([aria-current="page"]):hover {
            color: #38bdf8 !important;
            background-color: rgba(14, 165, 233, 0.12) !important;
        }

        html:not(.dark) .pagination .pages button:not([aria-current="page"]) {
            color: #64748b !important;
        }

        html:not(.dark) .pagination .pages button:not([aria-current="page"]):hover {
            color: #0ea5e9 !important;
            background-color: #e0f2fe !important;
        }

        /* Clean scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        html:not(.dark) ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
        }
        html:not(.dark) ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="h-full px-3 lg:px-5 bg-gray-100 dark:bg-gray-900">
<div id="log-viewer" class="flex h-full max-h-screen max-w-full">
    <router-view></router-view>
</div>

<!-- Global LogViewer Object -->
<script>
    window.LogViewer = @json($logViewerScriptVariables);
</script>
@if ($assetsPublished)
    <script src="{{ asset(mix('app.js', config('log-viewer.assets_path'))) }}" onerror="alert('app.js failed to load. Please refresh the page, re-publish Log Viewer assets, or fix routing for vendor assets.')"></script>
@else
    {!! \Opcodes\LogViewer\Facades\LogViewer::js() !!}
@endif
</body>
</html>
