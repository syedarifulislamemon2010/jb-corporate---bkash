<style>
    /* ===== JANATA BANK CORPORATE PORTAL — PREMIUM FINTECH THEME ===== */

    /* ─── Color Palette ─── */
    :root {
        --jb-primary: #0369a1;
        --jb-primary-dark: #075985;
        --jb-primary-light: #e0f2fe;
        --jb-accent: #0ea5e9;
        --jb-surface: #f8fafc;
        --jb-sidebar-bg: #0f172a;
        --jb-sidebar-text: #94a3b8;
        --jb-sidebar-active: #38bdf8;
        --jb-sidebar-hover: #1e293b;
    }

    /* ─── Sidebar: Dark Premium Banking Theme ─── */
    aside.fi-sidebar {
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%) !important;
        border-right: 1px solid rgba(56, 189, 248, 0.1) !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    aside.fi-sidebar::-webkit-scrollbar {
        display: none !important;
    }

    .fi-sidebar-nav,
    .fi-sidebar-nav nav {
        scrollbar-width: none !important;
    }

    .fi-sidebar-nav::-webkit-scrollbar,
    .fi-sidebar-nav nav::-webkit-scrollbar {
        display: none !important;
    }

    /* ─── Sidebar Brand / Logo ─── */
    .fi-sidebar-header {
        background: transparent !important;
        border-bottom: 1px solid rgba(148, 163, 184, 0.15) !important;
        padding: 1rem !important;
    }

    .fi-sidebar-header a,
    .fi-sidebar-header span {
        color: #f1f5f9 !important;
        font-weight: 700 !important;
        font-size: 1.05rem !important;
        letter-spacing: 0.02em !important;
    }

    /* ─── Sidebar Group Labels ─── */
    .fi-sidebar-group-label,
    .fi-sidebar-group > button > span,
    .fi-sidebar-group-button span {
        color: #64748b !important;
        font-size: 0.6rem !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        padding: 0.6rem 0.75rem 0.3rem !important;
    }

    /* Sidebar group collapse icon */
    .fi-sidebar-group-button svg,
    .fi-sidebar-group > button > svg {
        color: #475569 !important;
        width: 1rem !important;
        height: 1rem !important;
    }

    /* ─── Sidebar Items ─── */
    .fi-sidebar-item a,
    .fi-sidebar-item button {
        color: #94a3b8 !important;
        font-size: 0.82rem !important;
        font-weight: 500 !important;
        padding: 0.5rem 0.75rem !important;
        border-radius: 0.5rem !important;
        margin: 1px 0.5rem !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .fi-sidebar-item a:hover,
    .fi-sidebar-item button:hover {
        color: #e2e8f0 !important;
        background: rgba(56, 189, 248, 0.08) !important;
        transform: translateX(3px);
    }

    /* Sidebar item icons */
    .fi-sidebar-item-icon {
        color: #64748b !important;
        width: 1.15rem !important;
        height: 1.15rem !important;
        transition: color 0.2s ease !important;
    }

    .fi-sidebar-item a:hover .fi-sidebar-item-icon,
    .fi-sidebar-item button:hover .fi-sidebar-item-icon {
        color: var(--jb-sidebar-active) !important;
    }

    /* ─── Sidebar Active Item ─── */
    .fi-sidebar-item-active a,
    .fi-sidebar-item-active button {
        color: #ffffff !important;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.2) 0%, rgba(56, 189, 248, 0.1) 100%) !important;
        border-left: 3px solid var(--jb-sidebar-active) !important;
        font-weight: 600 !important;
    }

    .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: var(--jb-sidebar-active) !important;
    }

    /* ─── Top Bar ─── */
    .fi-topbar {
        background: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
    }

    /* ─── Dashboard Page Title ─── */
    .fi-header-heading {
        font-weight: 800 !important;
        color: #0f172a !important;
        font-size: 1.5rem !important;
    }

    /* ─── Stats Widget Cards ─── */
    .fi-wi-stats-overview-stat {
        border-radius: 1rem !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        overflow: hidden !important;
    }

    .fi-wi-stats-overview-stat:hover {
        box-shadow: 0 8px 25px rgba(3, 105, 161, 0.12) !important;
        transform: translateY(-4px);
        border-color: var(--jb-accent) !important;
    }

    /* ─── Table Styling ─── */
    .fi-ta-header-cell {
        background-color: #f1f5f9 !important;
        font-weight: 700 !important;
        font-size: 0.68rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
        color: #475569 !important;
        border-bottom: 2px solid var(--jb-primary) !important;
    }

    .fi-ta-row:hover {
        background-color: #f0f9ff !important;
        transition: background-color 0.15s ease;
    }

    .fi-ta-row:nth-child(even) {
        background-color: #fafbfc;
    }

    /* ─── Badges ─── */
    .fi-badge {
        font-weight: 600 !important;
        letter-spacing: 0.03em !important;
        border-radius: 0.5rem !important;
        font-size: 0.7rem !important;
    }

    /* ─── Tabs ─── */
    .fi-tabs-item-active {
        border-bottom: 3px solid var(--jb-primary) !important;
        font-weight: 700 !important;
    }

    .fi-tabs-item {
        transition: all 0.15s ease !important;
    }

    .fi-tabs-item:hover {
        color: var(--jb-primary) !important;
    }

    /* ─── Buttons ─── */
    .fi-btn-primary {
        border-radius: 0.6rem !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    }

    .fi-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(3, 105, 161, 0.25) !important;
    }

    /* ─── Section / Card ─── */
    .fi-section {
        border-radius: 1rem !important;
        overflow: hidden !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
    }

    /* ─── Notification Bell ─── */
    .fi-topbar-database-notifications-btn {
        position: relative;
    }

    /* ─── Bulk Actions ─── */
    .fi-ta-bulk-actions {
        border-radius: 0.75rem !important;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%) !important;
        border: 1px solid rgba(14, 165, 233, 0.2) !important;
    }

    /* ─── Pagination ─── */
    .fi-pagination {
        padding: 0.75rem !important;
    }

    /* ─── Modals ─── */
    .fi-modal-window {
        border-radius: 1.25rem !important;
    }

    /* ─── Collapsed Sidebar Icon-Only Mode ─── */
    .fi-sidebar-collapsed .fi-sidebar-item a,
    .fi-sidebar-collapsed .fi-sidebar-item button {
        justify-content: center !important;
        padding: 0.6rem !important;
        margin: 2px 0.3rem !important;
    }

    .fi-sidebar-collapsed .fi-sidebar-item-icon {
        color: #94a3b8 !important;
    }

    .fi-sidebar-collapsed .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: var(--jb-sidebar-active) !important;
    }
</style>
