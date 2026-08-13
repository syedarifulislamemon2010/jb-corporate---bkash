<style>
    /* ===== JANATA BANK CORPORATE PORTAL — LIGHT & DARK THEME ENGINE ===== */

    /* ─── Hide Default Filament Widgets ─── */
    .fi-wi-account,
    .fi-wi-filament-info,
    [class*="AccountWidget"],
    [class*="FilamentInfoWidget"] {
        display: none !important;
    }

    /* ─── Color Palette Variables ─── */
    :root {
        --jb-primary: #0284c7;
        --jb-primary-dark: #0369a1;
        --jb-primary-light: #e0f2fe;
        --jb-accent: #0ea5e9;
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

    /* ─── Common Improvements ─── */
    .fi-sidebar-item a,
    .fi-sidebar-item button {
        white-space: normal !important;
        line-height: 1.3 !important;
        word-break: break-word !important;
    }

    .fi-sidebar-item-label {
        white-space: normal !important;
        line-height: 1.3 !important;
        overflow: visible !important;
        text-overflow: unset !important;
    }

    /* ─── LIGHT THEME (Default) ─── */
    html:not(.dark) body {
        background-color: #f8fafc !important;
    }

    html:not(.dark) aside.fi-sidebar {
        background-color: #ffffff !important;
        border-right: 1px solid #e2e8f0 !important;
    }

    html:not(.dark) .fi-sidebar-header {
        border-bottom: 1px solid #e2e8f0 !important;
    }

    html:not(.dark) .fi-sidebar-header a,
    html:not(.dark) .fi-sidebar-header span {
        color: #0f172a !important;
        font-weight: 700 !important;
    }

    html:not(.dark) .fi-sidebar-group-label,
    html:not(.dark) .fi-sidebar-group-button span {
        color: #64748b !important;
        font-weight: 700 !important;
    }

    html:not(.dark) .fi-sidebar-item a,
    html:not(.dark) .fi-sidebar-item button {
        color: #334155 !important;
    }

    html:not(.dark) .fi-sidebar-item a:hover,
    html:not(.dark) .fi-sidebar-item button:hover {
        background-color: #f1f5f9 !important;
        color: #0284c7 !important;
    }

    html:not(.dark) .fi-sidebar-item-active a,
    html:not(.dark) .fi-sidebar-item-active button {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
        border-left: 3px solid #0284c7 !important;
        font-weight: 600 !important;
    }

    html:not(.dark) .fi-topbar {
        background-color: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    html:not(.dark) .fi-header-heading {
        color: #0f172a !important;
        font-weight: 800 !important;
    }

    html:not(.dark) .fi-wi-stats-overview-stat {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
    }

    html:not(.dark) .fi-wi-stats-overview-stat-label {
        color: #64748b !important;
    }

    html:not(.dark) .fi-wi-stats-overview-stat-value {
        color: #0f172a !important;
    }

    /* ─── DARK THEME (When .dark is present) ─── */
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
    }

    html.dark .fi-sidebar-group-label,
    html.dark .fi-sidebar-group-button span,
    .dark .fi-sidebar-group-label,
    .dark .fi-sidebar-group-button span {
        color: #64748b !important;
    }

    html.dark .fi-sidebar-item a,
    html.dark .fi-sidebar-item button,
    .dark .fi-sidebar-item a,
    .dark .fi-sidebar-item button {
        color: #94a3b8 !important;
    }

    html.dark .fi-sidebar-item a:hover,
    html.dark .fi-sidebar-item button:hover,
    .dark .fi-sidebar-item a:hover,
    .dark .fi-sidebar-item button:hover {
        background-color: #1e293b !important;
        color: #38bdf8 !important;
    }

    html.dark .fi-sidebar-item-active a,
    html.dark .fi-sidebar-item-active button,
    .dark .fi-sidebar-item-active a,
    .dark .fi-sidebar-item-active button {
        background-color: rgba(56, 189, 248, 0.12) !important;
        color: #38bdf8 !important;
        border-left: 3px solid #38bdf8 !important;
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
    }

    html.dark .fi-wi-stats-overview-stat,
    .dark .fi-wi-stats-overview-stat {
        background-color: #0f172a !important;
        border: 1px solid #1e293b !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
    }

    html.dark .fi-wi-stats-overview-stat-label,
    .dark .fi-wi-stats-overview-stat-label {
        color: #94a3b8 !important;
    }

    html.dark .fi-wi-stats-overview-stat-value,
    .dark .fi-wi-stats-overview-stat-value {
        color: #f8fafc !important;
    }
</style>
