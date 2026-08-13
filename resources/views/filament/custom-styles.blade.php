<style>
    /* ===== PREMIUM FINTECH STYLING ===== */

    /* Root variables */
    :root {
        --jb-primary: #0369a1;
        --jb-primary-light: #e0f2fe;
        --jb-accent: #0ea5e9;
        --jb-success: #059669;
        --jb-warning: #d97706;
        --jb-danger: #dc2626;
    }

    /* Hide scrollbars in sidebar while maintaining scrollability */
    .fi-sidebar-nav,
    aside.fi-sidebar,
    .fi-sidebar-nav nav {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    .fi-sidebar-nav::-webkit-scrollbar,
    aside.fi-sidebar::-webkit-scrollbar,
    .fi-sidebar-nav nav::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

    /* Sidebar group styling */
    .fi-sidebar-group-label {
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 0.65rem !important;
        letter-spacing: 0.08em !important;
        color: #64748b !important;
        padding: 0.5rem 0.75rem !important;
    }

    /* Sidebar active item */
    .fi-sidebar-item-active {
        background: linear-gradient(135deg, var(--jb-primary-light) 0%, #f0f9ff 100%) !important;
        border-left: 3px solid var(--jb-primary) !important;
        border-radius: 0 0.5rem 0.5rem 0 !important;
    }

    /* Sidebar hover effect */
    .fi-sidebar-item:hover {
        background-color: #f8fafc !important;
        transform: translateX(2px);
        transition: all 0.15s ease;
    }

    /* Stats widget cards */
    .fi-wi-stats-overview-stat {
        border-radius: 1rem !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.2s ease !important;
    }

    .fi-wi-stats-overview-stat:hover {
        box-shadow: 0 4px 12px rgba(3, 105, 161, 0.12) !important;
        transform: translateY(-2px);
        border-color: var(--jb-accent) !important;
    }

    /* Table header styling */
    .fi-ta-header-cell {
        background-color: #f1f5f9 !important;
        font-weight: 700 !important;
        font-size: 0.7rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        color: #475569 !important;
        border-bottom: 2px solid var(--jb-primary) !important;
    }

    /* Table row hover */
    .fi-ta-row:hover {
        background-color: #f0f9ff !important;
        transition: background-color 0.15s ease;
    }

    /* Table alternate rows */
    .fi-ta-row:nth-child(even) {
        background-color: #fafbfc;
    }

    /* Badges */
    .fi-badge {
        font-weight: 600 !important;
        letter-spacing: 0.025em !important;
        border-radius: 0.5rem !important;
    }

    /* Tabs styling */
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

    /* Header / Top Bar */
    .fi-topbar {
        border-bottom: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
    }

    /* Buttons */
    .fi-btn-primary {
        border-radius: 0.75rem !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
    }

    .fi-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(3, 105, 161, 0.3) !important;
    }

    /* Section cards */
    .fi-section {
        border-radius: 1rem !important;
        overflow: hidden !important;
    }

    /* Notification bell animation */
    .fi-topbar-database-notifications-btn {
        animation: pulse-subtle 2s infinite;
    }

    @keyframes pulse-subtle {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }

    /* Page title styling */
    .fi-header-heading {
        font-weight: 800 !important;
        color: #1e293b !important;
    }

    /* Bulk action bar */
    .fi-ta-bulk-actions {
        border-radius: 0.75rem !important;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%) !important;
    }

    /* Pagination */
    .fi-pagination {
        padding: 0.75rem !important;
    }

    /* Modal backdrop */
    .fi-modal-window {
        border-radius: 1.25rem !important;
    }
</style>
