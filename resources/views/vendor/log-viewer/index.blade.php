<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
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
        :root {
            --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', 'Fira Code', ui-monospace, SFMono-Regular, monospace;
        }

        body {
            font-family: var(--font-sans) !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Monospace font for logs and timestamps */
        code, pre, .font-mono, [class*="font-mono"] {
            font-family: var(--font-mono) !important;
        }

        /* Refined Custom Scrollbars */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.03);
        }
        .dark ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.03);
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Top Brand Navigation Bar */
        .jb-log-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65rem 1.25rem;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
            z-index: 50;
        }
        .dark .jb-log-topbar {
            background-color: #0f172a;
            border-bottom: 1px solid #1e293b;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .jb-brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
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
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
        }

        .jb-brand-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .dark .jb-brand-title {
            color: #f8fafc;
        }

        .jb-brand-subtitle {
            font-size: 0.75rem;
            font-weight: 500;
            color: #64748b;
            margin-left: 0.5rem;
            padding-left: 0.5rem;
            border-left: 1px solid #e2e8f0;
        }
        .dark .jb-brand-subtitle {
            color: #94a3b8;
            border-left-color: #334155;
        }

        .jb-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            font-size: 0.825rem;
            font-weight: 600;
            color: #0369a1;
            background-color: #e0f2fe;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .jb-back-btn:hover {
            background-color: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px -2px rgba(2, 132, 199, 0.25);
        }
        .dark .jb-back-btn {
            color: #38bdf8;
            background-color: rgba(14, 165, 233, 0.12);
            border-color: rgba(14, 165, 233, 0.3);
        }
        .dark .jb-back-btn:hover {
            background-color: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }

        /* Main log-viewer container */
        #log-viewer {
            height: calc(100vh - 54px) !important;
            max-height: calc(100vh - 54px) !important;
        }
    </style>
</head>

<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 antialiased flex flex-col overflow-hidden">
    <!-- Top JB Corporate Brand Header Bar -->
    <header class="jb-log-topbar">
        <div class="flex items-center">
            <a href="/admin" class="jb-brand-logo">
                <div class="jb-logo-badge">JB</div>
                <div class="flex items-center">
                    <span class="jb-brand-title">JB Corporate</span>
                    <span class="jb-brand-subtitle">System Logs & Diagnostics</span>
                </div>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin" class="jb-back-btn" title="Return to JB Corporate Admin Dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </header>

    <!-- Main Log Viewer App Container -->
    <main class="flex-1 w-full overflow-hidden">
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
