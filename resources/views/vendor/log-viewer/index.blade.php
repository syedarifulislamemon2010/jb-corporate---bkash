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
           JANATA BANK CORPORATE — LOG VIEWER 10/10 MASTER THEME
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

        /* Monospace font for logs, timestamps, line numbers */
        code, pre, .font-mono, [class*="font-mono"] {
            font-family: var(--font-mono) !important;
            font-feature-settings: "liga" 0, "tnum" 1;
        }

        /* ─── 1. TOP BRAND NAVIGATION BAR (Seamless JB Corporate) ─── */
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

        .jb-brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            user-select: none;
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

        /* ─── 3. REMOVE UNNECESSARY LINKS, SPONSORS & REDUNDANCIES ─── */
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

        /* ─── 4. FIX PAGINATION CUT-OFF & FOOTER BAR ─── */
        /* Pagination container breathing room */
        #log-viewer nav[aria-label="Pagination"],
        #log-viewer .pagination,
        #log-viewer [class*="pagination"] {
            padding-top: 0.5rem !important;
            padding-bottom: 0.65rem !important;
            margin-bottom: 0 !important;
        }

        /* Footer bar wrapper */
        #log-viewer .border-t {
            border-top-color: #1e293b !important;
        }

        html:not(.dark) #log-viewer .border-t {
            border-top-color: #e2e8f0 !important;
        }

        /* Performance stats in bottom footer */
        .text-xs.text-gray-500.dark\:text-gray-400 {
            font-size: 0.75rem !important;
            opacity: 0.75 !important;
            font-family: var(--font-mono) !important;
        }

        /* ─── 5. LOG TABLE & SEVERITY BADGES POLISH ─── */
        tbody tr {
            transition: background-color 0.15s ease !important;
        }

        tbody tr:hover {
            background-color: rgba(14, 165, 233, 0.04) !important;
        }

        .dark tbody tr:hover {
            background-color: rgba(14, 165, 233, 0.08) !important;
        }

        /* ─── 6. CUSTOM SCROLLBARS ─── */
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
    <!-- 1. Top JB Corporate Brand Navigation Bar -->
    <header class="jb-log-topbar">
        <div class="flex items-center">
            <a href="/admin" class="jb-brand-logo" title="Janata Bank Corporate Payment Portal">
                <div class="jb-logo-badge">JB</div>
                <div class="flex items-center">
                    <span class="jb-brand-title">JB Corporate</span>
                    <span class="jb-brand-subtitle">System Logs & Diagnostics</span>
                </div>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin" class="jb-back-btn" title="Return to Admin Dashboard">
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
