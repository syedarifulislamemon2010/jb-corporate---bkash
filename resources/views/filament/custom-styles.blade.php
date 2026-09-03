<style>
    /* ===== JANATA BANK CORPORATE PORTAL — VISUAL DESIGN TOKEN SYSTEM ===== */

    :root {
        --font-family-sans: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --color-base-surface: #F8FAFC;
        --color-card-surface: #FFFFFF;
        --color-primary-ink: #0F172A;
        --color-secondary-ink: #64748B;
        --color-signature-accent: #1E3A5F;

        /* ─── 3-COLOR STAGE PALETTE (Workflow Hierarchy) ─── */
        --color-stage-checker: #D97706; /* Amber / Orange for Tier 1 Verification */
        --color-stage-checker-bg: rgba(217, 119, 6, 0.05);
        --color-stage-auth1: #6366F1;   /* Indigo / Blue for Tier 2 1st Authorization */
        --color-stage-auth1-bg: rgba(99, 102, 241, 0.05);
        --color-stage-auth2: #0D9488;   /* Teal / Emerald for Tier 3 Final Authorization */
        --color-stage-auth2-bg: rgba(13, 148, 136, 0.05);

        /* ─── CHANNEL COLOR PALETTE (Payment Modes) ─── */
        --color-channel-a2a: #0284C7;   /* Sky Blue for Account-to-Account */
        --color-channel-a2a-bg: rgba(2, 132, 199, 0.04);
        --color-channel-beftn: #8B5CF6; /* Violet for BEFTN Clearing Batch */
        --color-channel-beftn-bg: rgba(139, 92, 246, 0.04);
        --color-channel-rtgs: #EA580C;  /* Deep Orange for High-Value RTGS */
        --color-channel-rtgs-bg: rgba(234, 88, 12, 0.04);
    }

    html.dark, .dark {
        --color-base-surface: #0B0F19;
        --color-card-surface: #0F172A;
        --color-primary-ink: #F8FAFC;
        --color-secondary-ink: #94A3B8;
        --color-signature-accent: #4A6FA5;

        /* Dark Mode Stage Palette */
        --color-stage-checker: #F59E0B;
        --color-stage-checker-bg: rgba(245, 158, 11, 0.08);
        --color-stage-auth1: #818CF8;
        --color-stage-auth1-bg: rgba(129, 140, 248, 0.08);
        --color-stage-auth2: #14B8A6;
        --color-stage-auth2-bg: rgba(20, 184, 166, 0.08);

        /* Dark Mode Channel Palette */
        --color-channel-a2a: #38BDF8;
        --color-channel-a2a-bg: rgba(56, 189, 248, 0.08);
        --color-channel-beftn: #A78BFA;
        --color-channel-beftn-bg: rgba(167, 139, 250, 0.08);
        --color-channel-rtgs: #FB923C;
        --color-channel-rtgs-bg: rgba(251, 146, 60, 0.08);
    }

    body, button, input, select, textarea, .fi-body {
        font-family: var(--font-family-sans) !important;
        letter-spacing: -0.01em;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .fi-main-ctn, main.fi-main {
        background-color: var(--color-base-surface) !important;
    }
    html.dark .fi-main-ctn,
    html.dark main.fi-main,
    .dark .fi-main-ctn,
    .dark main.fi-main {
        background-color: #0B0F19 !important;
    }

    /* ─── TABULAR NUMERAL ENGINE ─── */
    .db-text-val, .db-tabular, .db-stat-num, [data-tabular-num] {
        font-variant-numeric: tabular-nums !important;
        font-feature-settings: "tnum" 1 !important;
    }

    /* ─── DASHBOARD LAYOUT & CARD ENGINE ─── */
    .db-grid-3 {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1.25rem;
    }
    @media (min-width: 768px) {
        .db-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .db-grid-2-1 {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1.25rem;
    }
    @media (min-width: 1024px) {
        .db-grid-2-1 {
            grid-template-columns: 2fr 1fr;
        }
    }

    /* ─── 3-TIER ELEVATION HIERARCHY ─── */

    /* TIER 1: SIGNATURE HERO CARD (TCSA Live Balance) */
    .db-card-hero {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--color-signature-accent) !important;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
        position: relative;
        overflow: hidden;
    }

    /* TIER 2: ACTION REQUIRED (Stage Specific Cards) */
    .db-card-stage-checker {
        background-color: #ffffff;
        border: 1px solid rgba(217, 119, 6, 0.35);
        border-left: 4px solid var(--color-stage-checker) !important;
        background-image: linear-gradient(to bottom, var(--color-stage-checker-bg), transparent);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
        transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        display: block;
        text-decoration: none;
    }
    .db-card-stage-checker:hover {
        border-color: rgba(217, 119, 6, 0.8);
        box-shadow: 0 4px 14px rgba(217, 119, 6, 0.12);
        transform: translateY(-1px);
    }

    .db-card-stage-auth1 {
        background-color: #ffffff;
        border: 1px solid rgba(99, 102, 241, 0.35);
        border-left: 4px solid var(--color-stage-auth1) !important;
        background-image: linear-gradient(to bottom, var(--color-stage-auth1-bg), transparent);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
        transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        display: block;
        text-decoration: none;
    }
    .db-card-stage-auth1:hover {
        border-color: rgba(99, 102, 241, 0.8);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.12);
        transform: translateY(-1px);
    }

    .db-card-stage-auth2 {
        background-color: #ffffff;
        border: 1px solid rgba(13, 148, 136, 0.35);
        border-left: 4px solid var(--color-stage-auth2) !important;
        background-image: linear-gradient(to bottom, var(--color-stage-auth2-bg), transparent);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
        transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        display: block;
        text-decoration: none;
    }
    .db-card-stage-auth2:hover {
        border-color: rgba(13, 148, 136, 0.8);
        box-shadow: 0 4px 14px rgba(13, 148, 136, 0.12);
        transform: translateY(-1px);
    }

    /* Channel Payment Mode Specific Cards */
    .db-card-channel-a2a {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--color-channel-a2a) !important;
        background-image: linear-gradient(to bottom, var(--color-channel-a2a-bg), transparent);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.03);
        transition: all 0.15s ease;
    }
    .db-card-channel-beftn {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--color-channel-beftn) !important;
        background-image: linear-gradient(to bottom, var(--color-channel-beftn-bg), transparent);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.03);
        transition: all 0.15s ease;
    }
    .db-card-channel-rtgs {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--color-channel-rtgs) !important;
        background-image: linear-gradient(to bottom, var(--color-channel-rtgs-bg), transparent);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.03);
        transition: all 0.15s ease;
    }

    .db-card-warning {
        background-color: #ffffff;
        border: 1px solid rgba(217, 119, 6, 0.45);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
        display: block;
        text-decoration: none;
    }
    .db-card-warning:hover {
        border-color: rgba(217, 119, 6, 0.8);
        box-shadow: 0 4px 14px rgba(217, 119, 6, 0.08);
    }

    /* TIER 3: BASELINE INFORMATIONAL (Quiet) */
    .db-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.03);
    }

    /* ZERO-STATE DASHED CARD */
    .db-card-zero {
        background-color: #ffffff;
        border: 1px dashed #cbd5e1 !important;
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: none !important;
    }

    .db-card-inner {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .db-card-danger {
        background-color: #ffffff;
        border: 1px solid rgba(244, 63, 94, 0.5);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: block;
        text-decoration: none;
    }

    .db-banner-warning {
        background-color: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        color: #92400e;
    }

    .db-text-heading {
        color: #0f172a;
    }

    .db-text-val {
        font-size: 1.875rem;
        line-height: 2.25rem;
        font-weight: 800;
        color: #0f172a;
        font-variant-numeric: tabular-nums !important;
    }

    .db-text-sub {
        color: #64748b;
    }

    .db-link-action {
        color: var(--color-signature-accent);
        font-size: 0.75rem;
        font-weight: 600;
        transition: opacity 0.15s ease;
    }
    .db-link-action:hover {
        opacity: 0.85;
    }

    .db-strip {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        color: #334155;
    }
    @media (min-width: 640px) {
        .db-strip {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .db-btn-refresh {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #334155;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    /* Dark Theme Overrides (html.dark) */
    html.dark .db-card-hero {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        border-left: 4px solid var(--color-signature-accent) !important;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4), 0 2px 6px -1px rgba(0, 0, 0, 0.2) !important;
    }

    html.dark .db-card {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.2) !important;
    }

    html.dark .db-card-zero {
        background-color: #0f172a !important;
        border: 1px dashed #334155 !important;
    }

    html.dark .db-card-inner {
        background-color: rgba(2, 6, 23, 0.5) !important;
        border-color: #1e293b !important;
    }

    html.dark .db-card-stage-checker {
        background-color: #0f172a !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
        border-left: 4px solid var(--color-stage-checker) !important;
    }

    html.dark .db-card-stage-auth1 {
        background-color: #0f172a !important;
        border-color: rgba(129, 140, 248, 0.3) !important;
        border-left: 4px solid var(--color-stage-auth1) !important;
    }

    html.dark .db-card-stage-auth2 {
        background-color: #0f172a !important;
        border-color: rgba(20, 184, 166, 0.3) !important;
        border-left: 4px solid var(--color-stage-auth2) !important;
    }

    html.dark .db-card-channel-a2a {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        border-left: 4px solid var(--color-channel-a2a) !important;
    }

    html.dark .db-card-channel-beftn {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        border-left: 4px solid var(--color-channel-beftn) !important;
    }

    html.dark .db-card-channel-rtgs {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        border-left: 4px solid var(--color-channel-rtgs) !important;
    }

    html.dark .db-card-warning {
        background-color: #0f172a !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }

    html.dark .db-card-danger {
        background-color: #0f172a !important;
        border-color: rgba(244, 63, 94, 0.4) !important;
    }

    html.dark .db-banner-warning {
        background-color: rgba(245, 158, 11, 0.1) !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
        color: #f59e0b !important;
    }

    html.dark .db-text-heading {
        color: #f8fafc !important;
    }

    html.dark .db-text-val {
        color: #f8fafc !important;
    }

    html.dark .db-text-sub {
        color: #94a3b8 !important;
    }

    html.dark .db-strip {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        color: #94a3b8 !important;
    }

    html.dark .db-btn-refresh {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    /* ─── Role Permission Matrix Section Badges ─── */
    .shield-badge-neutral {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }
    html.dark .shield-badge-neutral {
        background: rgba(148, 163, 184, 0.15) !important;
        color: #94a3b8 !important;
        border-color: rgba(148, 163, 184, 0.3) !important;
    }

    .shield-badge-info {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #7dd3fc;
    }
    html.dark .shield-badge-info {
        background: rgba(74, 111, 165, 0.25) !important;
        color: #93c5fd !important;
        border-color: rgba(74, 111, 165, 0.5) !important;
    }

    .shield-badge-success {
        background: #d1fae5;
        color: #047857;
        border: 1px solid #34d399;
    }
    html.dark .shield-badge-success {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #34d399 !important;
        border-color: rgba(16, 185, 129, 0.4) !important;
    }

    .db-flex-between {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .db-flex-center {
        display: flex;
        align-items: center;
    }

    .db-flex-gap-2 {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .db-flex-gap-3 {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .db-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .db-badge-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .db-badge-success {
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.4);
    }

    .db-badge-danger {
        background: rgba(239, 68, 68, 0.15);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.4);
    }

    .db-badge-warning {
        background: rgba(245, 158, 11, 0.15);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.4);
    }

    .db-badge-info {
        background: rgba(74, 111, 165, 0.15);
        color: var(--color-signature-accent);
        border: 1px solid rgba(74, 111, 165, 0.3);
    }

    .db-badge-sm {
        padding: 0.125rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .db-badge-square-sm {
        padding: 0.125rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.625rem;
        font-weight: 700;
    }

    .db-channel-cols {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        text-align: center;
        padding: 0.5rem 0;
        border-radius: 0.75rem;
    }

    .db-channel-col-border {
        border-left: 1px solid rgba(148, 163, 184, 0.2);
        border-right: 1px solid rgba(148, 163, 184, 0.2);
    }

    .db-activity-list {
        display: flex;
        flex-direction: column;
    }

    .db-activity-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.875rem 0;
    }

    .db-activity-item + .db-activity-item {
        border-top: 1px solid rgba(148, 163, 184, 0.2);
    }

    /* ─── Dashboard Header & Controls ─── */
    .db-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        padding-bottom: 0.5rem;
    }

    .db-sync-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .db-sync-dot-delayed {
        background-color: #dc2626;
    }

    .db-sync-dot-ok {
        background-color: #10b981;
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
    }

    html.dark .db-autorefresh-label {
        color: #94a3b8;
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
    }

    /* ─── Dashboard Urgency Banner ─── */
    .db-urgency-icon-box {
        padding: 0.625rem;
        border-radius: 0.75rem;
        background: rgba(245, 158, 11, 0.2);
        color: #d97706;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .db-urgency-content {
        flex: 1;
        min-width: 0;
    }

    .db-urgency-title {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0;
    }

    .db-urgency-desc {
        font-size: 0.75rem;
        opacity: 0.85;
        margin: 0.125rem 0 0 0;
    }

    /* ─── Stage Action Cards ─── */
    .db-stage-header {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .db-stage-body {
        margin-top: 0.75rem;
    }

    .db-stage-sub {
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .db-stage-action {
        margin-top: 0.5rem;
    }

    .db-stage-checker-theme {
        color: var(--color-stage-checker);
    }

    .db-stage-auth1-theme {
        color: var(--color-stage-auth1);
    }

    .db-stage-auth2-theme {
        color: var(--color-stage-auth2);
    }

    .db-stage-empty-muted {
        color: #64748b;
    }

    html.dark .db-stage-empty-muted {
        color: #94a3b8;
    }

    .db-card-zero-dimmed {
        opacity: 0.65;
    }

    .db-flex-center-wrap {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .db-link-xs {
        font-size: 0.75rem;
    }

    .db-font-semibold {
        font-weight: 600;
    }

    /* ─── Channel Row Elements ─── */
    .db-channel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .db-channel-title {
        font-size: 1rem;
        font-weight: 800;
    }

    .db-channel-col-label {
        font-size: 0.75rem;
        font-weight: 500;
    }

    .db-channel-col-val {
        font-size: 1rem;
        font-weight: 700;
        margin-top: 0.125rem;
    }

    .db-channel-val-active {
        color: #d97706;
    }

    .db-channel-val-settled {
        color: #059669;
    }

    .db-channel-val-auth {
        color: var(--color-signature-accent);
    }

    .db-channel-val-zero {
        color: #64748b;
    }

    html.dark .db-channel-val-zero {
        color: #94a3b8;
    }

    .db-channel-inactive-badge {
        background: rgba(148, 163, 184, 0.15);
        border: 1px solid rgba(148, 163, 184, 0.3);
        color: #64748b;
    }

    html.dark .db-channel-inactive-badge {
        color: #94a3b8;
    }

    .db-channel-inactive-desc {
        font-size: 0.75rem;
        font-style: italic;
        margin-top: 0.5rem;
    }

    /* ─── Exception Row ─── */
    .db-exception-link {
        text-decoration: none;
        display: block;
    }

    .db-exception-icon-ok {
        padding: 0.5rem;
        border-radius: 0.75rem;
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .db-exception-icon-err {
        padding: 0.5rem;
        border-radius: 0.75rem;
        background: rgba(244, 63, 94, 0.15);
        color: #e11d48;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .db-exception-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .db-exception-headline {
        font-size: 0.875rem;
        font-weight: 600;
        margin-top: 0.125rem;
    }

    .db-exception-action {
        font-weight: 700;
    }

    /* ─── Balances Row ─── */
    .db-balance-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .db-tcsa-title {
        font-size: 0.875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0;
        color: var(--color-signature-accent);
    }

    .db-tcsa-sub {
        font-size: 0.75rem;
        font-weight: 500;
        margin: 0.125rem 0 0 0;
    }

    .db-tcsa-amount {
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        font-size: 2.25rem;
        letter-spacing: -0.03em;
    }

    .db-settled-summary {
        margin-top: 1.25rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(148, 163, 184, 0.15);
    }

    .db-settled-label {
        font-size: 0.75rem;
        font-weight: 600;
    }

    .db-settled-count {
        font-size: 0.875rem;
        font-weight: 700;
    }

    .db-settled-amount {
        font-size: 0.875rem;
        font-weight: 700;
        color: #059669;
    }

    html.dark .db-settled-amount {
        color: #10b981;
    }

    .db-ops-card {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .db-ops-title {
        font-size: 0.875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0;
    }

    .db-ops-amount {
        margin-top: 1.25rem;
        font-size: 1.75rem;
        letter-spacing: -0.025em;
    }

    .db-ops-footer {
        margin-top: 1.5rem;
    }

    /* ─── MT940 & Activity ─── */
    .db-mt940-title {
        font-weight: 700;
    }

    .db-mt940-list {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        font-size: 0.75rem;
    }

    /* ─── Modern Timeline & Pipeline Audit Feed ─── */
    .db-live-feed-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        background-color: rgba(16, 185, 129, 0.12);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }

    html:not(.dark) .db-live-feed-badge {
        background-color: #ecfdf5;
        color: #059669;
        border-color: #a7f3d0;
    }

    .db-timeline-feed {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding-top: 0.25rem;
    }

    .db-timeline-item {
        display: flex;
        position: relative;
        gap: 1rem;
        padding: 0.75rem 0.65rem;
        border-radius: 8px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .db-timeline-item:hover {
        background-color: rgba(241, 245, 249, 0.65);
    }

    .dark .db-timeline-item:hover {
        background-color: rgba(30, 41, 59, 0.45);
    }

    .db-timeline-node-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        width: 28px;
        flex-shrink: 0;
    }

    .db-timeline-connector {
        position: absolute;
        top: 28px;
        bottom: -16px;
        width: 2px;
        background: linear-gradient(180deg, rgba(148, 163, 184, 0.3) 0%, rgba(148, 163, 184, 0.1) 100%);
    }

    .dark .db-timeline-connector {
        background: linear-gradient(180deg, rgba(51, 65, 85, 0.8) 0%, rgba(51, 65, 85, 0.2) 100%);
    }

    .db-timeline-node {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border: 1.5px solid;
    }

    .db-node-sky {
        background-color: rgba(14, 165, 233, 0.12);
        border-color: rgba(14, 165, 233, 0.4);
        color: #0284c7;
    }
    .dark .db-node-sky {
        color: #38bdf8;
        border-color: rgba(56, 189, 248, 0.4);
    }

    .db-node-indigo {
        background-color: rgba(99, 102, 241, 0.12);
        border-color: rgba(99, 102, 241, 0.4);
        color: #4f46e5;
    }
    .dark .db-node-indigo {
        color: #818cf8;
        border-color: rgba(129, 140, 248, 0.4);
    }

    .db-node-amber {
        background-color: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.4);
        color: #d97706;
    }
    .dark .db-node-amber {
        color: #fbbf24;
        border-color: rgba(251, 191, 36, 0.4);
    }

    .db-node-emerald {
        background-color: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.4);
        color: #059669;
    }
    .dark .db-node-emerald {
        color: #34d399;
        border-color: rgba(52, 211, 153, 0.4);
    }

    .db-node-slate {
        background-color: rgba(148, 163, 184, 0.12);
        border-color: rgba(148, 163, 184, 0.4);
        color: #64748b;
    }
    .dark .db-node-slate {
        color: #94a3b8;
    }

    .db-timeline-content {
        flex: 1 1 0%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .db-timeline-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .db-stage-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.45rem;
        border-radius: 4px;
        font-size: 0.675rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        border: 1px solid;
    }

    .db-stage-sky {
        background-color: #e0f2fe;
        color: #0369a1;
        border-color: #bae6fd;
    }
    .dark .db-stage-sky {
        background-color: rgba(14, 165, 233, 0.1);
        border-color: rgba(14, 165, 233, 0.25);
        color: #38bdf8;
    }

    .db-stage-indigo {
        background-color: #e0e7ff;
        color: #4338ca;
        border-color: #c7d2fe;
    }
    .dark .db-stage-indigo {
        background-color: rgba(99, 102, 241, 0.1);
        border-color: rgba(99, 102, 241, 0.25);
        color: #818cf8;
    }

    .db-stage-amber {
        background-color: #fef3c7;
        color: #b45309;
        border-color: #fde68a;
    }
    .dark .db-stage-amber {
        background-color: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.25);
        color: #fbbf24;
    }

    .db-stage-emerald {
        background-color: #d1fae5;
        color: #047857;
        border-color: #a7f3d0;
    }
    .dark .db-stage-emerald {
        background-color: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.25);
        color: #34d399;
    }

    .db-stage-slate {
        background-color: #f1f5f9;
        color: #475569;
        border-color: #e2e8f0;
    }
    .dark .db-stage-slate {
        background-color: rgba(148, 163, 184, 0.1);
        border-color: rgba(148, 163, 184, 0.25);
        color: #94a3b8;
    }

    .db-timeline-action-title {
        font-size: 0.825rem;
        font-weight: 600;
        color: #0f172a;
    }
    .dark .db-timeline-action-title {
        color: #f8fafc;
    }

    .db-timeline-file-tag {
        font-family: var(--font-mono, monospace);
        font-size: 0.725rem;
        font-weight: 500;
        padding: 0.1rem 0.4rem;
        border-radius: 4px;
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .dark .db-timeline-file-tag {
        background-color: #0f172a;
        color: #94a3b8;
        border-color: #334155;
    }

    .db-timeline-human-time {
        font-size: 0.725rem;
        font-weight: 600;
        color: #0284c7;
        white-space: nowrap;
    }
    .dark .db-timeline-human-time {
        color: #38bdf8;
    }

    .db-timeline-meta-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 0.725rem;
        color: #64748b;
    }
    .dark .db-timeline-meta-row {
        color: #94a3b8;
    }

    .db-timeline-actor {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-weight: 500;
    }

    .db-timeline-dot-sep {
        color: #94a3b8;
        opacity: 0.6;
    }

    .db-timeline-full-time {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-weight: 400;
        opacity: 0.85;
    }

    .db-activity-header {
        margin-bottom: 0.75rem;
    }

    .db-activity-empty {
        padding: 1.5rem 0;
        justify-content: center;
        font-size: 0.875rem;
    }

    .db-activity-more {
        text-align: center;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(148, 163, 184, 0.15);
    }

    /* ─── Slide-over Notifications Drawer Polish ─── */
    .fi-no-database,
    .fi-no-notifications {
        overflow-x: hidden !important;
    }

    .fi-no-notification-unread-ctn,
    .fi-no-notification-read-ctn {
        padding: 0.875rem 1rem !important;
        margin-bottom: 0.625rem !important;
        border-radius: 0.875rem !important;
        transition: all 0.15s ease !important;
    }

    .fi-no-notification-unread-ctn {
        background-color: rgba(2, 132, 199, 0.05) !important;
        border: 1px solid rgba(2, 132, 199, 0.25) !important;
        border-left: 4px solid #0284c7 !important;
    }

    html.dark .fi-no-notification-unread-ctn {
        background-color: rgba(56, 189, 248, 0.06) !important;
        border: 1px solid rgba(56, 189, 248, 0.2) !important;
        border-left: 4px solid #38bdf8 !important;
    }

    .fi-no-notification-read-ctn {
        background-color: rgba(248, 250, 252, 0.8) !important;
        border: 1px solid #e2e8f0 !important;
    }

    html.dark .fi-no-notification-read-ctn {
        background-color: rgba(15, 23, 42, 0.5) !important;
        border: 1px solid #1e293b !important;
    }

    /* ─── Collapsed Sidebar Group Hover Flyout & Sub-item Labels ─── */
    .fi-sidebar-group .fi-dropdown-panel {
        min-width: 16rem !important;
        border-radius: 1rem !important;
        padding: 0.75rem !important;
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        z-index: 9999 !important;
    }

    html.dark .fi-sidebar-group .fi-dropdown-panel {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important;
    }

    .fi-sidebar-group .fi-dropdown-header {
        padding: 0.25rem 0.5rem 0.5rem 0.5rem !important;
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        color: #0f172a !important;
        border-bottom: 1px solid #f1f5f9 !important;
        margin-bottom: 0.35rem !important;
    }

    html.dark .fi-sidebar-group .fi-dropdown-header {
        color: #f8fafc !important;
        border-bottom-color: #1e293b !important;
    }

    .fi-sidebar-group .fi-dropdown-panel .fi-dropdown-list-item {
        border-radius: 0.5rem !important;
        padding: 0.625rem 0.75rem !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        color: #334155 !important;
        transition: all 0.15s ease !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.625rem !important;
    }

    html.dark .fi-sidebar-group .fi-dropdown-panel .fi-dropdown-list-item {
        color: #cbd5e1 !important;
    }

    .fi-sidebar-group .fi-dropdown-panel .fi-dropdown-list-item:hover {
        background-color: rgba(2, 132, 199, 0.08) !important;
        color: #0284c7 !important;
    }

    html.dark .fi-sidebar-group .fi-dropdown-panel .fi-dropdown-list-item:hover {
        background-color: rgba(56, 189, 248, 0.12) !important;
        color: #38bdf8 !important;
    }

    /* ─── Enterprise Corporate Footer ─── */
    .jb-portal-footer {
        width: 100%;
        margin-top: 2rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid #e2e8f0;
        background-color: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(8px);
        font-size: 0.75rem;
        color: #64748b;
    }

    html.dark .jb-portal-footer {
        border-top-color: #1e293b;
        background-color: rgba(15, 23, 42, 0.75);
        color: #94a3b8;
    }

    .jb-portal-footer-inner {
        max-width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    @media (min-width: 768px) {
        .jb-portal-footer-inner {
            flex-direction: row;
        }
    }

    .jb-footer-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .jb-footer-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
    }

    .jb-footer-org {
        font-weight: 700;
        color: #0f172a;
    }

    html.dark .jb-footer-org {
        color: #f8fafc;
    }

    .jb-footer-sep {
        color: #cbd5e1;
    }

    html.dark .jb-footer-sep {
        color: #475569;
    }

    .jb-footer-desc {
        font-weight: 500;
    }

    .jb-footer-right {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        flex-wrap: wrap;
    }

    .jb-footer-security {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
        font-size: 0.7rem;
        color: #059669;
        background: rgba(16, 185, 129, 0.1);
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }

    html.dark .jb-footer-security {
        color: #34d399;
        background: rgba(16, 185, 129, 0.15);
        border-color: rgba(16, 185, 129, 0.35);
    }

    .jb-footer-lock-icon {
        width: 0.75rem;
        height: 0.75rem;
    }

    .jb-footer-badge {
        font-family: ui-monospace, monospace;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        color: #475569;
    }

    html.dark .jb-footer-badge {
        background: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }

    .jb-footer-copy {
        font-weight: 600;
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

    /* ─── ROLE PERMISSION-MATRIX UI REINFORCEMENTS ─── */
    .fi-fo-checkbox-list label span {
        white-space: normal !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
        hyphens: manual !important;
        line-height: 1.35 !important;
        display: inline-block !important;
    }

    .fi-fo-checkbox-list {
        gap: 0.875rem 1rem !important;
    }

    /* Top Select All Toggle Caption Separator */
    .shield-select-all-separator {
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    html.dark .shield-select-all-separator {
        border-color: #1e293b;
    }
    /* ─── MOBILE RESPONSIVENESS: Payment-Channel Breakdown ─── */
    @media (max-width: 480px) {
        .db-channel-cols {
            grid-template-columns: repeat(3, 1fr);
            gap: 0.25rem;
            padding: 0.35rem 0;
        }

        .db-channel-col-label {
            font-size: 0.65rem !important;
        }

        .db-channel-col-val {
            font-size: 0.8rem !important;
        }

        .db-channel-col-border {
            border-left-width: 1px;
            border-right-width: 1px;
        }
    }

    /* ─── High-Priority Failed / Partial Transactions Critical Monitor Card ─── */
    .db-exception-link {
        text-decoration: none !important;
        display: block !important;
    }
    .db-card-danger,
    a.db-exception-link {
        background-color: #fff1f2 !important;
        border: 2px solid #f43f5e !important;
        border-left: 6px solid #e11d48 !important;
        box-shadow: 0 4px 14px rgba(225, 29, 72, 0.15) !important;
        border-radius: 0.75rem !important;
        transition: all 0.2s ease-in-out !important;
    }
    html.dark .db-card-danger,
    html.dark a.db-exception-link,
    .dark .db-card-danger,
    .dark a.db-exception-link {
        background-color: rgba(225, 29, 72, 0.1) !important;
        border-color: #e11d48 !important;
        box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25) !important;
    }
    a.db-exception-link:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 18px rgba(225, 29, 72, 0.25) !important;
    }
    .db-exception-icon-ok,
    .db-exception-icon-err {
        background: #ffe4e6 !important;
        color: #e11d48 !important;
        border: 1px solid #fecdd3 !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem !important;
    }
    html.dark .db-exception-icon-ok,
    html.dark .db-exception-icon-err {
        background: rgba(225, 29, 72, 0.2) !important;
        color: #fb7185 !important;
        border-color: rgba(225, 29, 72, 0.4) !important;
    }
    .db-exception-label {
        color: #be123c !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em !important;
    }
    html.dark .db-exception-label {
        color: #fda4af !important;
    }
    .db-exception-headline {
        color: #9f1239 !important;
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
    }
    html.dark .db-exception-headline {
        color: #fff1f2 !important;
    }
    .db-exception-action {
        color: #e11d48 !important;
        font-weight: 700 !important;
    }

    /* ─── Recent Activity & Pipeline Audit Feed Larger Font Sizes ─── */
    .db-timeline-file-tag {
        font-family: var(--font-mono, monospace) !important;
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        padding: 0.2rem 0.6rem !important;
        border-radius: 6px !important;
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        border: 1.5px solid #cbd5e1 !important;
        letter-spacing: 0.02em !important;
    }
    html.dark .db-timeline-file-tag,
    .dark .db-timeline-file-tag {
        background-color: #090d16 !important;
        color: #38bdf8 !important;
        border-color: #334155 !important;
    }
    .db-timeline-action-title {
        font-size: 0.9375rem !important;
        font-weight: 600 !important;
    }
    .db-stage-badge {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
    }
    .db-timeline-human-time {
        font-size: 0.8125rem !important;
    }
    .db-timeline-actor,
    .db-timeline-full-time {
        font-size: 0.8125rem !important;
    }

    /* ─── 2-Line Text Wrapping & No Horizontal Scrolling on 4 Report / Audit Tables ─── */
    .fi-resource-bkash-failed-transactions .fi-ta-ctn,
    .fi-resource-bkash-batches .fi-ta-ctn,
    .fi-resource-bkash-reports .fi-ta-ctn,
    .fi-resource-eft-returns .fi-ta-ctn {
        overflow-x: hidden !important;
    }
    .fi-resource-bkash-failed-transactions .fi-ta-table,
    .fi-resource-bkash-batches .fi-ta-table,
    .fi-resource-bkash-reports .fi-ta-table,
    .fi-resource-eft-returns .fi-ta-table {
        table-layout: auto !important;
        width: 100% !important;
    }
    .fi-resource-bkash-failed-transactions .fi-ta-cell,
    .fi-resource-bkash-batches .fi-ta-cell,
    .fi-resource-bkash-reports .fi-ta-cell,
    .fi-resource-eft-returns .fi-ta-cell {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    .fi-resource-bkash-failed-transactions .fi-ta-cell p,
    .fi-resource-bkash-failed-transactions .fi-ta-cell span,
    .fi-resource-bkash-batches .fi-ta-cell p,
    .fi-resource-bkash-batches .fi-ta-cell span,
    .fi-resource-bkash-reports .fi-ta-cell p,
    .fi-resource-bkash-reports .fi-ta-cell span,
    .fi-resource-eft-returns .fi-ta-cell p,
    .fi-resource-eft-returns .fi-ta-cell span {
        white-space: normal !important;
        word-break: break-word !important;
        line-height: 1.25 !important;
    }

    /* ─── Clean Topbar Icons Styling ─── */
    .fi-topbar-end select,
    .fi-topbar-end input[type="number"]::-webkit-inner-spin-button,
    .fi-topbar-end input[type="number"]::-webkit-outer-spin-button {
        display: none !important;
        -webkit-appearance: none !important;
        margin: 0 !important;
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

        // 6. Sidebar Navigation Accordion Mode (Single Expanded Group at a Time + Dashboard Auto-Collapse)
        const getAllGroupLabels = () => {
            const groupElements = document.querySelectorAll('.fi-sidebar-group[data-group-label], li[data-group-label]');
            const allLabels = new Set();
            groupElements.forEach(el => {
                const label = el.getAttribute('data-group-label');
                if (label) allLabels.add(label);
            });
            return Array.from(allLabels);
        };

        const collapseAllGroups = () => {
            const sidebarStore = window.Alpine && window.Alpine.store && window.Alpine.store('sidebar');
            if (!sidebarStore) return;
            const allLabels = getAllGroupLabels();
            if (allLabels.length > 0) {
                sidebarStore.collapsedGroups = allLabels;
            }
        };

        const collapseAllGroupsOnTopLevelClick = () => {
            const allSidebarLinks = document.querySelectorAll('.fi-sidebar-item a, .fi-sidebar-item button, .fi-sidebar-header a, .fi-logo a');

            allSidebarLinks.forEach((link) => {
                // Only target top-level standalone items (outside of any .fi-sidebar-group)
                if (link.closest('.fi-sidebar-group')) return;

                if (link.dataset.accordionBound) return;
                link.dataset.accordionBound = 'true';

                link.addEventListener('click', () => {
                    collapseAllGroups();
                });
            });
        };

        const checkCurrentRouteAndSyncAccordion = () => {
            const path = window.location.pathname.replace(/\/$/, '');
            // If on Dashboard (/admin or /admin/dashboard), collapse all groups
            if (path === '/admin' || path === '/admin/dashboard') {
                collapseAllGroups();
                return;
            }

            // If on a page inside a group, keep only that active group open and collapse all other groups
            const activeItem = document.querySelector('.fi-sidebar-group .fi-sidebar-item.fi-active, .fi-sidebar-group .fi-sidebar-item-active');
            if (activeItem) {
                const parentGroup = activeItem.closest('.fi-sidebar-group');
                if (parentGroup) {
                    const activeLabel = parentGroup.getAttribute('data-group-label');
                    if (activeLabel) {
                        const sidebarStore = window.Alpine && window.Alpine.store && window.Alpine.store('sidebar');
                        if (sidebarStore) {
                            const allLabels = getAllGroupLabels();
                            sidebarStore.collapsedGroups = allLabels.filter(label => label !== activeLabel);
                        }
                    }
                }
            }
        };

        const setupSidebarAccordion = () => {
            if (!window.Alpine || !window.Alpine.store || !window.Alpine.store('sidebar')) {
                setTimeout(setupSidebarAccordion, 50);
                return;
            }

            const sidebarStore = window.Alpine.store('sidebar');
            if (!sidebarStore._accordionConfigured) {
                sidebarStore._accordionConfigured = true;

                const originalToggle = sidebarStore.toggleCollapsedGroup.bind(sidebarStore);

                sidebarStore.toggleCollapsedGroup = function (targetLabel) {
                    // Determine if target group is currently collapsed (being opened)
                    const isCurrentlyCollapsed = (!this.collapsedGroups || !Array.isArray(this.collapsedGroups)) 
                        ? true 
                        : this.collapsedGroups.includes(targetLabel);

                    if (isCurrentlyCollapsed) {
                        const allLabels = getAllGroupLabels();
                        // Ensure targetLabel is in the label set
                        if (!allLabels.includes(targetLabel)) allLabels.push(targetLabel);

                        // Accordion behavior: Collapse every group except targetLabel
                        this.collapsedGroups = allLabels.filter(label => label !== targetLabel);
                    } else {
                        // User is closing the currently open group -> normal collapse
                        if (!this.collapsedGroups || !Array.isArray(this.collapsedGroups)) {
                            this.collapsedGroups = [targetLabel];
                        } else if (!this.collapsedGroups.includes(targetLabel)) {
                            this.collapsedGroups = this.collapsedGroups.concat(targetLabel);
                        }
                    }
                };
            }

            // Bind collapse on top-level navigation links (e.g. Dashboard)
            collapseAllGroupsOnTopLevelClick();

            // Sync current route accordion state
            checkCurrentRouteAndSyncAccordion();
        };
        setupSidebarAccordion();
        document.addEventListener('livewire:navigated', setupSidebarAccordion);

        // 7. Collapsed Sidebar Group Hover Flyout with Full Names
        let activeHoverTimeout;
        let currentlyOpenDropdown = null;

        const openGroupFlyout = (groupEl) => {
            const sidebarStore = window.Alpine && window.Alpine.store && window.Alpine.store('sidebar');
            if (!sidebarStore || sidebarStore.isOpen) return; // Only when sidebar is collapsed

            const dropdown = groupEl.querySelector('.fi-dropdown');
            if (!dropdown) return;

            const trigger = dropdown.querySelector('.fi-dropdown-trigger') || dropdown.querySelector('.fi-sidebar-group-dropdown-trigger-btn');
            const panel = dropdown.querySelector('.fi-dropdown-panel');

            clearTimeout(activeHoverTimeout);

            // Close any previously opened other dropdown
            if (currentlyOpenDropdown && currentlyOpenDropdown !== dropdown) {
                const prevPanel = currentlyOpenDropdown.querySelector('.fi-dropdown-panel');
                const prevTrigger = currentlyOpenDropdown.querySelector('.fi-dropdown-trigger') || currentlyOpenDropdown.querySelector('.fi-sidebar-group-dropdown-trigger-btn');
                if (prevPanel && prevTrigger && (prevPanel.style.display === 'block' || getComputedStyle(prevPanel).display === 'block')) {
                    prevTrigger.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true, button: 0 }));
                }
            }

            // Open this dropdown
            if (trigger && panel) {
                const isClosed = !panel.style.display || panel.style.display === 'none' || getComputedStyle(panel).display === 'none';
                if (isClosed) {
                    currentlyOpenDropdown = dropdown;
                    trigger.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true, button: 0 }));
                }
            }
        };

        const closeGroupFlyout = (groupEl) => {
            const sidebarStore = window.Alpine && window.Alpine.store && window.Alpine.store('sidebar');
            if (!sidebarStore || sidebarStore.isOpen) return;

            const dropdown = groupEl.querySelector('.fi-dropdown');
            if (!dropdown) return;

            const trigger = dropdown.querySelector('.fi-dropdown-trigger') || dropdown.querySelector('.fi-sidebar-group-dropdown-trigger-btn');
            const panel = dropdown.querySelector('.fi-dropdown-panel');

            clearTimeout(activeHoverTimeout);
            activeHoverTimeout = setTimeout(() => {
                // Verify mouse is not hovering over the panel, dropdown, or group
                const isHovering = groupEl.matches(':hover') || (panel && panel.matches(':hover')) || dropdown.matches(':hover');
                if (!isHovering && trigger && panel) {
                    const isOpen = panel.style.display === 'block' || (getComputedStyle(panel).display !== 'none' && panel.style.display !== 'none');
                    if (isOpen) {
                        trigger.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true, button: 0 }));
                        if (currentlyOpenDropdown === dropdown) {
                            currentlyOpenDropdown = null;
                        }
                    }
                }
            }, 300);
        };

        document.addEventListener('mouseover', function (e) {
            const group = e.target.closest('.fi-sidebar-group');
            if (group) {
                openGroupFlyout(group);
            }
        }, { passive: true });

        document.addEventListener('mouseout', function (e) {
            const group = e.target.closest('.fi-sidebar-group');
            if (group && (!e.relatedTarget || !group.contains(e.relatedTarget))) {
                closeGroupFlyout(group);
            }
        }, { passive: true });
    });
</script>


<style>
    /* ===== BATCH FILE GROUP HEADER STYLING ===== */
    .fi-ta-group-header-row {
        background-color: rgba(241, 245, 249, 0.75) !important;
        border-top: 1px solid #e2e8f0 !important;
        border-bottom: 1px solid #cbd5e1 !important;
    }
    html.dark .fi-ta-group-header-row {
        background-color: rgba(15, 23, 42, 0.85) !important;
        border-top: 1px solid #1e293b !important;
        border-bottom: 1px solid #334155 !important;
    }
    .fi-ta-group-header {
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 0.35rem 0.5rem !important;
        cursor: pointer !important;
    }
    .fi-ta-group-header > div:first-child {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        width: 100% !important;
    }
    .fi-ta-group-header .fi-ta-group-heading {
        display: none !important;
    }
    .fi-ta-group-header .fi-ta-group-description {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .fi-batch-group-header-strip {
        user-select: text;
    }

    /* ─── High-Priority Failed / Partial Transactions Critical Monitor Card ─── */
    .db-exception-link {
        text-decoration: none !important;
        display: block !important;
    }
    .db-card-danger,
    a.db-exception-link {
        background-color: #fff1f2 !important;
        border: 2px solid #f43f5e !important;
        border-left: 6px solid #e11d48 !important;
        box-shadow: 0 4px 14px rgba(225, 29, 72, 0.15) !important;
        border-radius: 0.75rem !important;
        transition: all 0.2s ease-in-out !important;
    }
    html.dark .db-card-danger,
    html.dark a.db-exception-link,
    .dark .db-card-danger,
    .dark a.db-exception-link {
        background-color: rgba(225, 29, 72, 0.1) !important;
        border-color: #e11d48 !important;
        box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25) !important;
    }
    a.db-exception-link:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 18px rgba(225, 29, 72, 0.25) !important;
    }
    .db-exception-icon-ok,
    .db-exception-icon-err {
        background: #ffe4e6 !important;
        color: #e11d48 !important;
        border: 1px solid #fecdd3 !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem !important;
    }
    html.dark .db-exception-icon-ok,
    html.dark .db-exception-icon-err {
        background: rgba(225, 29, 72, 0.2) !important;
        color: #fb7185 !important;
        border-color: rgba(225, 29, 72, 0.4) !important;
    }
    .db-exception-label {
        color: #be123c !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em !important;
    }
    html.dark .db-exception-label {
        color: #fda4af !important;
    }
    .db-exception-headline {
        color: #9f1239 !important;
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
    }
    html.dark .db-exception-headline {
        color: #fff1f2 !important;
    }
    .db-exception-action {
        color: #e11d48 !important;
        font-weight: 700 !important;
    }

    /* ─── Recent Activity & Pipeline Audit Feed Larger Font Sizes ─── */
    .db-timeline-file-tag {
        font-family: var(--font-mono, monospace) !important;
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        padding: 0.2rem 0.6rem !important;
        border-radius: 6px !important;
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        border: 1.5px solid #cbd5e1 !important;
        letter-spacing: 0.02em !important;
    }
    html.dark .db-timeline-file-tag,
    .dark .db-timeline-file-tag {
        background-color: #090d16 !important;
        color: #38bdf8 !important;
        border-color: #334155 !important;
    }
    .db-timeline-action-title {
        font-size: 0.9375rem !important;
        font-weight: 600 !important;
    }
    .db-stage-badge {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
    }
    .db-timeline-human-time {
        font-size: 0.8125rem !important;
    }
    .db-timeline-actor,
    .db-timeline-full-time {
        font-size: 0.8125rem !important;
    }

    /* ─── 2-Line Text Wrapping & No Horizontal Scrolling on 4 Report / Audit Tables ─── */
    .fi-resource-bkash-failed-transactions .fi-ta-ctn,
    .fi-resource-bkash-batches .fi-ta-ctn,
    .fi-resource-bkash-reports .fi-ta-ctn,
    .fi-resource-eft-returns .fi-ta-ctn {
        overflow-x: hidden !important;
    }
    .fi-resource-bkash-failed-transactions .fi-ta-table,
    .fi-resource-bkash-batches .fi-ta-table,
    .fi-resource-bkash-reports .fi-ta-table,
    .fi-resource-eft-returns .fi-ta-table {
        table-layout: auto !important;
        width: 100% !important;
    }
    .fi-resource-bkash-failed-transactions .fi-ta-cell,
    .fi-resource-bkash-batches .fi-ta-cell,
    .fi-resource-bkash-reports .fi-ta-cell,
    .fi-resource-eft-returns .fi-ta-cell {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    .fi-resource-bkash-failed-transactions .fi-ta-cell p,
    .fi-resource-bkash-failed-transactions .fi-ta-cell span,
    .fi-resource-bkash-batches .fi-ta-cell p,
    .fi-resource-bkash-batches .fi-ta-cell span,
    .fi-resource-bkash-reports .fi-ta-cell p,
    .fi-resource-bkash-reports .fi-ta-cell span,
    .fi-resource-eft-returns .fi-ta-cell p,
    .fi-resource-eft-returns .fi-ta-cell span {
        white-space: normal !important;
        word-break: break-word !important;
        line-height: 1.25 !important;
    }

    /* ─── Clean Topbar Icons Styling ─── */
    .fi-topbar-end select,
    .fi-topbar-end input[type="number"]::-webkit-inner-spin-button,
    .fi-topbar-end input[type="number"]::-webkit-outer-spin-button {
        display: none !important;
        -webkit-appearance: none !important;
        margin: 0 !important;
    }
</style>