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
           JANATA BANK CORPORATE — LOG VIEWER 10/10 ENTERPRISE THEME
           ═══════════════════════════════════════════════════════════════════ */

        :root {
            --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', 'Fira Code', ui-monospace, SFMono-Regular, monospace;
            --brand-primary: #0284c7;
            --brand-primary-hover: #0369a1;
        }

        html, body {
            height: 100vh !important;
            max-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            font-family: var(--font-sans) !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            display: flex !important;
            flex-direction: column !important;
            background-color: #0b0f19 !important;
        }

        html:not(.dark) body {
            background-color: #f8fafc !important;
        }

        /* Monospace font for logs, timestamps, line numbers, file sizes */
        code, pre, .font-mono, [class*="font-mono"] {
            font-family: var(--font-mono) !important;
            font-feature-settings: "liga" 0, "tnum" 1;
        }

        /* ─── 1. TOP BRAND HEADER (Single, Clean, Non-Duplicated Link) ─── */
        .jb-log-topbar {
            height: 52px !important;
            min-height: 52px !important;
            max-height: 52px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 1.25rem !important;
            background-color: #0f172a !important;
            border-bottom: 1px solid #1e293b !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25) !important;
            z-index: 50 !important;
            flex-shrink: 0 !important;
        }

        html:not(.dark) .jb-log-topbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
        }

        /* Static Brand Header Badge (Non-clickable to eliminate redundant duplicate link) */
        .jb-brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            user-select: none;
            cursor: default;
        }

        .jb-logo-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: -0.02em;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35);
        }

        .jb-brand-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.02em;
        }

        html:not(.dark) .jb-brand-title {
            color: #0f172a;
        }

        .jb-brand-subtitle {
            font-size: 0.75rem;
            font-weight: 500;
            color: #94a3b8;
            margin-left: 0.65rem;
            padding-left: 0.65rem;
            border-left: 1px solid #334155;
        }

        html:not(.dark) .jb-brand-subtitle {
            color: #64748b;
            border-left-color: #e2e8f0;
        }

        /* Dedicated "Back to Dashboard" Single Link */
        .jb-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.9rem;
            font-size: 0.825rem;
            font-weight: 600;
            color: #38bdf8;
            background-color: rgba(14, 165, 233, 0.12);
            border: 1px solid rgba(14, 165, 233, 0.28);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .jb-back-btn:hover {
            background-color: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px -2px rgba(2, 132, 199, 0.4);
        }

        html:not(.dark) .jb-back-btn {
            color: #0369a1;
            background-color: #e0f2fe;
            border-color: #bae6fd;
        }

        html:not(.dark) .jb-back-btn:hover {
            background-color: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }

        /* ─── 2. MAIN LOG VIEWER CONTAINER & VIEWPORT HEIGHT FIX ─── */
        main.jb-log-main {
            height: calc(100vh - 52px) !important;
            max-height: calc(100vh - 52px) !important;
            flex: 1 1 0% !important;
            min-height: 0 !important;
            overflow: hidden !important;
            display: flex !important;
        }

        #log-viewer {
            height: 100% !important;
            max-height: 100% !important;
            width: 100% !important;
            display: flex !important;
            overflow: hidden !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        #log-viewer .max-h-screen {
            max-height: 100% !important;
        }

        /* ─── 3. SIDEBAR MATCHING DASHBOARD DESIGN (Zero Clipping) ─── */
        #log-viewer nav {
            background-color: #0f172a !important;
            border-right: 1px solid #1e293b !important;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.2) !important;
            padding: 1.25rem 1rem !important;
            display: flex !important;
            flex-direction: column !important;
            height: 100% !important;
            overflow-y: auto !important;
            box-sizing: border-box !important;
        }

        html:not(.dark) #log-viewer nav {
            background-color: #ffffff !important;
            border-right: 1px solid #e2e8f0 !important;
            box-shadow: 2px 0 12px rgba(15, 23, 42, 0.02) !important;
        }

        /* Hide the redundant "Log Viewer" title line inside the sidebar */
        #log-viewer nav h1 {
            display: none !important;
        }

        /* Sidebar Section Header (e.g. "Log files on Local" & "Sort direction") */
        #log-viewer nav .text-sm,
        #log-viewer nav .text-xs,
        #log-viewer nav [class*="text-sm"],
        #log-viewer nav [class*="text-xs"] {
            color: #94a3b8 !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            line-height: 1.6 !important;
            overflow: visible !important;
            text-overflow: unset !important;
            white-space: normal !important;
        }

        html:not(.dark) #log-viewer nav .text-sm,
        html:not(.dark) #log-viewer nav .text-xs,
        html:not(.dark) #log-viewer nav [class*="text-sm"],
        html:not(.dark) #log-viewer nav [class*="text-xs"] {
            color: #64748b !important;
        }

        /* Top row of file list (contains "Log files on Local" and sort dropdown) */
        #log-viewer nav select,
        #log-viewer nav .select {
            font-size: 0.775rem !important;
            padding: 0.25rem 0.5rem !important;
            border-radius: 6px !important;
            line-height: 1.2 !important;
        }

        /* ─── 4. MAIN CONTENT AREA PADDING & VERTICAL ALIGNMENT ─── */
        #log-viewer > div > div:last-child {
            padding: 1.25rem 1.25rem 0.5rem 1.25rem !important;
            display: flex !important;
            flex-direction: column !important;
            height: 100% !important;
            max-height: 100% !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
        }

        /* Sidebar Log File Items */
        #log-viewer nav button,
        #log-viewer nav a {
            border-radius: 8px !important;
            font-weight: 500 !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* Active selected log file */
        #log-viewer nav .bg-brand-50,
        #log-viewer nav .bg-brand-100,
        #log-viewer nav .bg-brand-500\/10,
        #log-viewer nav [class*="bg-brand-"] {
            background-color: rgba(14, 165, 233, 0.15) !important;
            color: #38bdf8 !important;
            border-left: 3px solid #0284c7 !important;
            font-weight: 700 !important;
            box-shadow: inset 0 0 0 1px rgba(14, 165, 233, 0.25) !important;
        }

        html:not(.dark) #log-viewer nav .bg-brand-50,
        html:not(.dark) #log-viewer nav .bg-brand-100,
        html:not(.dark) #log-viewer nav .bg-brand-500\/10,
        html:not(.dark) #log-viewer nav [class*="bg-brand-"] {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            border-left: 3px solid #0284c7 !important;
            box-shadow: none !important;
        }

        /* Hover on sidebar log files */
        #log-viewer nav button:hover,
        #log-viewer nav a:hover {
            transform: translateX(2px);
        }

        /* ─── 4. REMOVE ALL UNWANTED EXTERNAL & SPONSOR LINKS ─── */
        /* Hide GitHub link */
        a[href*="github.com"] {
            display: none !important;
        }

        /* Hide "Buy me a coffee" in footer and dropdown */
        a[href*="buymeacoffee"],
        a[href*="buymeacoffee.com"] {
            display: none !important;
        }

        /* Hide external Help and Documentation in settings dropdown */
        a[href*="log-viewer.opcodes.io"] {
            display: none !important;
        }

        /* Hide any orphan divider lines in settings dropdown */
        div[class*="menu"] hr,
        div[class*="menu"] .divider:last-child,
        div[class*="dropdown"] hr:last-child {
            display: none !important;
        }

        /* ─── 5. REMOVE FOOTER STATS & VERSION COMPLETELY ─── */
        /* Completely hide the Memory / Duration / Version footer text */
        .text-xs.text-gray-500,
        .text-xs.text-gray-400,
        [class*="text-xs text-gray-500"],
        div:has(> a[href*="buymeacoffee"]) {
            display: none !important;
        }

        /* ─── 6. CLEAN, PROMINENT PAGINATION ─── */
        #log-viewer nav[aria-label="Pagination"],
        #log-viewer .pagination,
        #log-viewer [class*="pagination"] {
            padding-top: 0.6rem !important;
            padding-bottom: 0.75rem !important;
            margin-bottom: 0 !important;
        }

        /* Footer bar wrapper border */
        #log-viewer .border-t {
            border-top-color: #1e293b !important;
        }

        html:not(.dark) #log-viewer .border-t {
            border-top-color: #e2e8f0 !important;
        }

        /* ─── 7. TABLE & BADGES POLISH ─── */
        tbody tr {
            transition: background-color 0.15s ease !important;
        }

        tbody tr:hover {
            background-color: rgba(14, 165, 233, 0.04) !important;
        }

        .dark tbody tr:hover {
            background-color: rgba(14, 165, 233, 0.08) !important;
        }

        /* ─── 8. CUSTOM SCROLLBARS ─── */
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

<body class="h-full bg-slate-950 text-slate-100 antialiased flex flex-col overflow-hidden">
    <!-- 1. Top JB Corporate Brand Header (Single dedicated link to Dashboard) -->
    <header class="jb-log-topbar">
        <div class="flex items-center gap-4">
            <!-- Non-clickable Brand Identifier (Eliminates redundant link) -->
            <div class="jb-brand-logo" aria-label="Janata Bank Corporate System Logs">
                <div class="jb-logo-badge">JB</div>
                <div class="flex items-center">
                    <span class="jb-brand-title">JB Corporate</span>
                    <span class="jb-brand-subtitle">System Logs & Diagnostics</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Single, Dedicated Link Back to Admin Dashboard -->
            <a href="/admin" class="jb-back-btn" title="Return to Admin Dashboard" aria-label="Back to Admin Dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </header>

    <!-- 2. Main Log Viewer Container -->
    <main class="jb-log-main">
        <div id="log-viewer" class="flex h-full max-h-full max-w-full">
            <router-view></router-view>
        </div>
    </main>

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
