<!-- Google Fonts: Plus Jakarta Sans for Modern FinTech Typography -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* ===== JANATA BANK CORPORATE PORTAL — ALL-IN-ONE FINTECH ENGINE ===== */

    :root {
        --font-family-sans: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    body, button, input, select, textarea, .fi-body {
        font-family: var(--font-family-sans) !important;
        letter-spacing: -0.01em;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* ─── PREVENT OVERSIZED SVG ICON EXPLOSION ─── */
    svg {
        display: inline-block;
        vertical-align: middle;
    }
    
    svg.w-4 { width: 1rem !important; height: 1rem !important; min-width: 1rem !important; min-height: 1rem !important; }
    svg.w-5 { width: 1.25rem !important; height: 1.25rem !important; min-width: 1.25rem !important; min-height: 1.25rem !important; }
    svg.w-6 { width: 1.5rem !important; height: 1.5rem !important; min-width: 1.5rem !important; min-height: 1.5rem !important; }
    svg.w-8 { width: 2rem !important; height: 2rem !important; min-width: 2rem !important; min-height: 2rem !important; }

    /* ─── Hide Default Filament Widgets ─── */
    .fi-wi-account,
    .fi-wi-filament-info,
    [class*="AccountWidget"],
    [class*="FilamentInfoWidget"] {
        display: none !important;
    }

    /* ─── Hide Sidebar Scrollbars Completely ─── */
    aside.fi-sidebar,
    aside.fi-sidebar *,
    .fi-sidebar-nav,
    .fi-sidebar-nav * {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    aside.fi-sidebar::-webkit-scrollbar,
    aside.fi-sidebar *::-webkit-scrollbar,
    .fi-sidebar-nav::-webkit-scrollbar,
    .fi-sidebar-nav *::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

    /* ─── Live SFTP System Pulse Badge in Sidebar ─── */
    .sftp-pulse-container {
        padding: 10px 14px;
        margin: 10px 14px;
        border-radius: 10px;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #10b981;
    }

    .sftp-pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: sftpPulse 1.8s infinite;
    }

    @keyframes sftpPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* ─── Sidebar Item Wrapping & Ergonomics ─── */
    .fi-sidebar-item a,
    .fi-sidebar-item button {
        white-space: normal !important;
        line-height: 1.35 !important;
        word-break: break-word !important;
        border-radius: 8px !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .fi-sidebar-item-label {
        white-space: normal !important;
        line-height: 1.35 !important;
        overflow: visible !important;
        text-overflow: unset !important;
        font-size: 0.915rem !important;
    }

    /* ─── SPOTLIGHT GLOBAL SEARCH BAR (Mac Command+K Style) ─── */
    .fi-global-search-field {
        width: 100% !important;
        max-width: 460px !important;
    }

    .fi-global-search-field input {
        font-family: var(--font-family-sans) !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        border-radius: 12px !important;
        padding-left: 2.75rem !important;
        padding-right: 3rem !important;
        height: 42px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Search Dropdown Suggestion Box */
    .fi-global-search-results-ctn {
        border-radius: 14px !important;
        margin-top: 8px !important;
        overflow: hidden !important;
        backdrop-filter: blur(12px) !important;
        box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.25) !important;
    }

    /* Click-to-copy Toast Badge */
    .copyable-cell {
        cursor: pointer;
        transition: color 0.15s ease;
    }
    .copyable-cell:hover {
        color: #0284c7 !important;
        text-decoration: underline;
    }

    /* ─── LIGHT THEME ENGINE ─── */
    html:not(.dark) body {
        background-color: #f8fafc !important;
    }

    html:not(.dark) aside.fi-sidebar {
        background-color: #ffffff !important;
        border-right: 1px solid #e2e8f0 !important;
        box-shadow: 2px 0 12px rgba(15, 23, 42, 0.02) !important;
    }

    html:not(.dark) .fi-sidebar-header {
        border-bottom: 1px solid #e2e8f0 !important;
    }

    html:not(.dark) .fi-sidebar-header a,
    html:not(.dark) .fi-sidebar-header span {
        color: #0f172a !important;
        font-weight: 800 !important;
        font-size: 1.15rem !important;
        letter-spacing: -0.02em !important;
    }

    html:not(.dark) .fi-sidebar-group-label,
    html:not(.dark) .fi-sidebar-group-button span {
        color: #64748b !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
    }

    html:not(.dark) .fi-sidebar-item a,
    html:not(.dark) .fi-sidebar-item button {
        color: #334155 !important;
        font-weight: 500 !important;
    }

    html:not(.dark) .fi-sidebar-item a:hover,
    html:not(.dark) .fi-sidebar-item button:hover {
        background-color: #f1f5f9 !important;
        color: #0284c7 !important;
        transform: translateX(2px);
    }

    html:not(.dark) .fi-sidebar-item-active a,
    html:not(.dark) .fi-sidebar-item-active button {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
        border-left: 3px solid #0284c7 !important;
        font-weight: 700 !important;
    }

    html:not(.dark) .fi-topbar {
        background-color: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02) !important;
    }

    html:not(.dark) .fi-header-heading {
        color: #0f172a !important;
        font-weight: 800 !important;
        letter-spacing: -0.025em !important;
    }

    /* Light Theme Global Search Bar */
    html:not(.dark) .fi-global-search-field input {
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        color: #0f172a !important;
    }

    html:not(.dark) .fi-global-search-field input:focus {
        background-color: #ffffff !important;
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12), 0 4px 12px rgba(2, 132, 199, 0.08) !important;
    }

    html:not(.dark) .fi-global-search-results-ctn {
        background-color: rgba(255, 255, 255, 0.98) !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* Stats Overview Cards Light */
    html:not(.dark) .fi-wi-stats-overview-stat {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.02) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }

    html:not(.dark) .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06) !important;
    }

    html:not(.dark) .fi-wi-stats-overview-stat-label {
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
    }

    html:not(.dark) .fi-wi-stats-overview-stat-value {
        color: #0f172a !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
    }

    /* ─── DARK THEME ENGINE ─── */
    html.dark body,
    .dark body {
        background-color: #090d16 !important;
    }

    html.dark aside.fi-sidebar,
    .dark aside.fi-sidebar {
        background-color: #0f172a !important;
        border-right: 1px solid #1e293b !important;
    }

    html.dark .fi-sidebar-header,
    .dark .fi-sidebar-header {
        border-bottom: 1px solid #1e293b !important;
    }

    html.dark .fi-sidebar-header a,
    html.dark .fi-sidebar-header span,
    .dark .fi-sidebar-header a,
    .dark .fi-sidebar-header span {
        color: #f8fafc !important;
        font-weight: 800 !important;
        font-size: 1.15rem !important;
    }

    html.dark .fi-sidebar-group-label,
    html.dark .fi-sidebar-group-button span,
    .dark .fi-sidebar-group-label,
    .dark .fi-sidebar-group-button span {
        color: #64748b !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
    }

    html.dark .fi-sidebar-item a,
    html.dark .fi-sidebar-item button,
    .dark .fi-sidebar-item a,
    .dark .fi-sidebar-item button {
        color: #94a3b8 !important;
        font-weight: 500 !important;
    }

    html.dark .fi-sidebar-item a:hover,
    html.dark .fi-sidebar-item button:hover,
    .dark .fi-sidebar-item a:hover,
    .dark .fi-sidebar-item button:hover {
        background-color: #1e293b !important;
        color: #38bdf8 !important;
        transform: translateX(2px);
    }

    html.dark .fi-sidebar-item-active a,
    html.dark .fi-sidebar-item-active button,
    .dark .fi-sidebar-item-active a,
    .dark .fi-sidebar-item-active button {
        background-color: rgba(56, 189, 248, 0.12) !important;
        color: #38bdf8 !important;
        border-left: 3px solid #38bdf8 !important;
        font-weight: 700 !important;
    }

    html.dark .fi-topbar,
    .dark .fi-topbar {
        background-color: #0f172a !important;
        border-bottom: 1px solid #1e293b !important;
    }

    html.dark .fi-header-heading,
    .dark .fi-header-heading {
        color: #38bdf8 !important;
        font-weight: 800 !important;
        letter-spacing: -0.025em !important;
    }

    /* Dark Theme Global Search Bar */
    html.dark .fi-global-search-field input,
    .dark .fi-global-search-field input {
        background-color: #0f172a !important;
        border: 1px solid #1e293b !important;
        color: #f8fafc !important;
    }

    html.dark .fi-global-search-field input:focus,
    .dark .fi-global-search-field input:focus {
        background-color: #0b1120 !important;
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.18), 0 4px 16px rgba(0, 0, 0, 0.4) !important;
    }

    html.dark .fi-global-search-results-ctn,
    .dark .fi-global-search-results-ctn {
        background-color: rgba(15, 23, 42, 0.96) !important;
        border: 1px solid #1e293b !important;
    }

    /* Table Improvements */
    .fi-ta-header-cell-label {
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        font-weight: 700 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Placeholder setup for Search
        const setPlaceholder = () => {
            const inputs = document.querySelectorAll('.fi-global-search-field input');
            inputs.forEach(input => {
                input.placeholder = 'Search Txn ID, Ref No, Account No, Batch File... (Ctrl + K)';
            });
        };
        setPlaceholder();
        setTimeout(setPlaceholder, 1000);

        // 2. Inject SFTP System Pulse Badge into Sidebar Header
        const injectPulseBadge = () => {
            const sidebarHeader = document.querySelector('aside.fi-sidebar .fi-sidebar-header');
            if (sidebarHeader && !document.querySelector('.sftp-pulse-container')) {
                const badge = document.createElement('div');
                badge.className = 'sftp-pulse-container';
                badge.innerHTML = '<div class="sftp-pulse-dot"></div> <span>SFTP Engine: Active (15m scan)</span>';
                sidebarHeader.appendChild(badge);
            }
        };
        injectPulseBadge();
        setTimeout(injectPulseBadge, 1200);

        // 3. Banking Web Audio Sound Effect Helper
        const playBankingChime = () => {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12); // A5
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } catch (e) {}
        };

        // 4. Click-to-copy handler
        document.addEventListener('click', function (e) {
            const cell = e.target.closest('.fi-ta-cell-text');
            if (cell) {
                const text = cell.textContent.trim();
                if (text && (text.startsWith('TXN') || text.startsWith('JANATA') || text.startsWith('BEFTN') || text.startsWith('RTGS') || (text.length >= 10 && !isNaN(text)))) {
                    navigator.clipboard.writeText(text).then(() => {
                        playBankingChime();
                        if (typeof Filament !== 'undefined' && Filament.notify) {
                            Filament.notify('success', 'Copied to Clipboard: ' + text);
                        }
                    }).catch(() => {});
                }
            }
        });

        // 5. Power-User Keyboard Shortcuts (G+D = Dashboard, G+T = Transactions, G+R = Reports)
        let keySequence = '';
        let keyTimeout;
        document.addEventListener('keydown', function (e) {
            if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
            
            clearTimeout(keyTimeout);
            keySequence += e.key.toLowerCase();
            
            if (keySequence === 'gd') {
                window.location.href = '/admin';
                keySequence = '';
            } else if (keySequence === 'gt') {
                window.location.href = '/admin/bkash-transactions';
                keySequence = '';
            } else if (keySequence === 'gr') {
                window.location.href = '/admin/bkash-reports';
                keySequence = '';
            }
            
            keyTimeout = setTimeout(() => { keySequence = ''; }, 600);
        });
    });
</script>
