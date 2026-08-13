<!-- Google Fonts: Plus Jakarta Sans for Modern FinTech Typography -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* ===== JANATA BANK CORPORATE PORTAL — ULTRA PREMIUM FINTECH ENGINE ===== */

    :root {
        --font-family-sans: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    body, button, input, select, textarea, .fi-body {
        font-family: var(--font-family-sans) !important;
        letter-spacing: -0.01em;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

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

    /* ─── Global Search Bar & Inputs Enhancement ─── */
    .fi-global-search-field input,
    .fi-ta-search-field input,
    .fi-input-wrapper input {
        font-family: var(--font-family-sans) !important;
        font-size: 0.875rem !important;
        border-radius: 10px !important;
        transition: all 0.2s ease-in-out !important;
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

    /* Light Theme Search Bar */
    html:not(.dark) .fi-global-search-field input,
    html:not(.dark) .fi-ta-search-field input {
        background-color: #f1f5f9 !important;
        border: 1px solid #cbd5e1 !important;
        color: #0f172a !important;
    }

    html:not(.dark) .fi-global-search-field input:focus,
    html:not(.dark) .fi-ta-search-field input:focus {
        background-color: #ffffff !important;
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
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

    /* Dark Theme Search Bar */
    html.dark .fi-global-search-field input,
    html.dark .fi-ta-search-field input,
    .dark .fi-global-search-field input,
    .dark .fi-ta-search-field input {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        color: #f8fafc !important;
    }

    html.dark .fi-global-search-field input:focus,
    html.dark .fi-ta-search-field input:focus,
    .dark .fi-global-search-field input:focus,
    .dark .fi-ta-search-field input:focus {
        background-color: #0f172a !important;
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2) !important;
    }

    /* Stats Overview Cards Dark */
    html.dark .fi-wi-stats-overview-stat,
    .dark .fi-wi-stats-overview-stat {
        background-color: #0f172a !important;
        border: 1px solid #1e293b !important;
        border-radius: 14px !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }

    html.dark .fi-wi-stats-overview-stat:hover,
    .dark .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
        border-color: #334155 !important;
    }

    html.dark .fi-wi-stats-overview-stat-label,
    .dark .fi-wi-stats-overview-stat-label {
        color: #94a3b8 !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
    }

    html.dark .fi-wi-stats-overview-stat-value,
    .dark .fi-wi-stats-overview-stat-value {
        color: #f8fafc !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
    }

    /* Table Improvements */
    .fi-ta-header-cell-label {
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        font-weight: 700 !important;
    }
</style>
