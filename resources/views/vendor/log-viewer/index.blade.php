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

        /* Hide the bottom performance stats / version text line */
        .text-xs.text-gray-500.dark\:text-gray-400,
        .text-xs.text-gray-500,
        .text-xs.text-gray-400 {
            display: none !important;
        }

        /* ─── 2. POLISHED "BACK TO DASHBOARD" BUTTON (Top of Sidebar) ─── */
        a[href="/admin"],
        a[href*="/admin"] {
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
