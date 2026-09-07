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

        /* ─── 5. JANATA BANK DASHBOARD-MATCHING AUTO-REFRESH & REFRESH BUTTON ─── */
        .jb-log-controls {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            margin-left: 0.75rem !important;
            flex-shrink: 0 !important;
        }

        .db-autorefresh-label {
            cursor: pointer;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.15s ease;
        }
        .dark .db-autorefresh-label,
        html.dark .db-autorefresh-label {
            color: #94a3b8;
        }
        .db-autorefresh-label:hover {
            color: #334155;
        }
        .dark .db-autorefresh-label:hover,
        html.dark .db-autorefresh-label:hover {
            color: #e2e8f0;
        }

        .db-autorefresh-checkbox {
            cursor: pointer;
            width: 1rem;
            height: 1rem;
            border-radius: 0.25rem;
            accent-color: #0284c7;
        }

        .db-autorefresh-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .db-pulse-dot-sm {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #10b981;
            display: inline-block;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
            animation: jb-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes jb-pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.4;
                transform: scale(0.85);
            }
        }

        .db-btn-refresh {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #334155;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            user-select: none;
        }
        .db-btn-refresh:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
            transform: translateY(-0.5px);
        }
        .db-btn-refresh:active {
            transform: translateY(0);
        }
        .db-btn-refresh:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .dark .db-btn-refresh,
        html.dark .db-btn-refresh {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }
        .dark .db-btn-refresh:hover,
        html.dark .db-btn-refresh:hover {
            background-color: #1e293b !important;
            border-color: #475569 !important;
            color: #ffffff !important;
        }

        @keyframes jb-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: jb-spin 0.8s linear infinite !important;
        }

        /* Hide the raw/default icon-only button so it doesn't duplicate our polished Refresh button */
        #reload-logs-button {
            display: none !important;
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
<script>
    (function () {
        try {
            if ('BroadcastChannel' in window) {
                var bc = new BroadcastChannel('jb-corporate-auth');
                bc.onmessage = function (e) {
                    if (e && e.data === 'logout') {
                        window.location.href = '/admin/login';
                    }
                };
            }
            window.addEventListener('storage', function (e) {
                if (e.key === 'jb_logout_event') {
                    window.location.href = '/admin/login';
                }
            });
        } catch (err) {}

        /* ─── Auto-Refresh (15s) & Manual Refresh Controls ─── */
        var STORAGE_KEY = 'jb_log_viewer_auto_refresh';
        var autoRefreshInterval = null;

        function isAutoRefreshEnabled() {
            var saved = localStorage.getItem(STORAGE_KEY);
            return saved === null ? true : saved === 'true';
        }

        function setAutoRefreshEnabled(val) {
            localStorage.setItem(STORAGE_KEY, val ? 'true' : 'false');
            syncTimerState();
        }

        function triggerLogReload(isAuto) {
            var reloadBtn = document.getElementById('reload-logs-button');
            if (reloadBtn) {
                reloadBtn.click();
            } else {
                window.dispatchEvent(new CustomEvent('reload-results'));
            }

            var icon = document.getElementById('jb-log-refresh-icon');
            var text = document.getElementById('jb-log-refresh-text');
            var btn = document.getElementById('jb-log-manual-refresh-btn');

            if (icon) icon.classList.add('animate-spin');
            if (text && !isAuto) text.textContent = 'Refreshing...';
            if (btn) btn.disabled = true;

            setTimeout(function () {
                if (icon) icon.classList.remove('animate-spin');
                if (text) text.textContent = 'Refresh';
                if (btn) btn.disabled = false;
            }, 800);
        }

        function syncTimerState() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }

            var enabled = isAutoRefreshEnabled();
            var pulseDot = document.getElementById('jb-log-pulse-dot');
            var checkbox = document.getElementById('jb-log-autorefresh-checkbox');

            if (checkbox && checkbox.checked !== enabled) {
                checkbox.checked = enabled;
            }

            if (pulseDot) {
                pulseDot.style.display = enabled ? 'inline-block' : 'none';
            }

            if (enabled) {
                autoRefreshInterval = setInterval(function () {
                    if (document.hidden) return;
                    triggerLogReload(true);
                }, 15000);
            }
        }

        function mountControls() {
            try {
                if (document.getElementById('jb-log-controls-wrapper')) {
                    return;
                }

                var reloadBtn = document.getElementById('reload-logs-button');
                var desktopSettings = document.getElementById('desktop-site-settings');

                var container = null;
                var insertBeforeNode = null;

                if (desktopSettings) {
                    var settingsCol = desktopSettings.closest('.hidden.md\\:block') || desktopSettings.parentElement;
                    container = settingsCol ? settingsCol.parentElement : desktopSettings.parentElement;
                    insertBeforeNode = settingsCol || desktopSettings;
                } else if (reloadBtn) {
                    var reloadCol = reloadBtn.closest('.hidden.md\\:block') || reloadBtn.parentElement;
                    container = reloadCol ? reloadCol.parentElement : reloadBtn.parentElement;
                    insertBeforeNode = reloadCol || reloadBtn;
                } else {
                    container = document.querySelector('.log-list .flex-1.flex.justify-end') ||
                                document.querySelector('.log-list header') ||
                                document.querySelector('.log-list');
                }

                if (!container) {
                    return;
                }

                var wrapper = document.createElement('div');
                wrapper.id = 'jb-log-controls-wrapper';
                wrapper.className = 'jb-log-controls';

                var isChecked = isAutoRefreshEnabled();
                wrapper.innerHTML = 
                    '<label class="db-autorefresh-label" aria-label="Toggle auto-refresh" title="Toggle 15-second automatic log refresh">' +
                        '<input type="checkbox" id="jb-log-autorefresh-checkbox" class="db-autorefresh-checkbox" aria-label="Enable or disable 15-second log viewer auto-refresh"' + (isChecked ? ' checked' : '') + ' />' +
                        '<span class="db-autorefresh-badge">' +
                            '<span>Auto-refresh (15s)</span>' +
                            '<span class="db-pulse-dot-sm" id="jb-log-pulse-dot" style="' + (isChecked ? 'display: inline-block;' : 'display: none;') + '" title="Auto-refresh active" aria-hidden="true"></span>' +
                        '</span>' +
                    '</label>' +
                    '<button type="button" id="jb-log-manual-refresh-btn" class="db-btn-refresh" aria-label="Refresh logs now" title="Refresh log viewer data now">' +
                        '<svg id="jb-log-refresh-icon" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />' +
                        '</svg>' +
                        '<span id="jb-log-refresh-text">Refresh</span>' +
                    '</button>';

                if (insertBeforeNode && insertBeforeNode.parentElement === container) {
                    container.insertBefore(wrapper, insertBeforeNode);
                } else {
                    container.appendChild(wrapper);
                }

                var checkbox = wrapper.querySelector('#jb-log-autorefresh-checkbox');
                if (checkbox) {
                    checkbox.addEventListener('change', function (e) {
                        setAutoRefreshEnabled(e.target.checked);
                    });
                }

                var manualBtn = wrapper.querySelector('#jb-log-manual-refresh-btn');
                if (manualBtn) {
                    manualBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        triggerLogReload(false);
                        syncTimerState();
                    });
                }

                syncTimerState();
            } catch (err) {
                console.error('mountControls error:', err);
            }
        }

        var observer = new MutationObserver(function () {
            mountControls();
        });

        var root = document.getElementById('log-viewer') || document.body;
        observer.observe(root, { childList: true, subtree: true });

        mountControls();
        var initTimer = setInterval(function () {
            mountControls();
            if (document.getElementById('jb-log-controls-wrapper')) {
                clearInterval(initTimer);
            }
        }, 250);
        setTimeout(function () { clearInterval(initTimer); }, 5000);
    })();
</script>
</body>
</html>
