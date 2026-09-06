@php
    $unreadNotificationsCount = $unreadNotificationsCount ?? 0;
@endphp

<button
    type="button"
    aria-label="Open notifications"
    class="fi-topbar-database-notifications-btn relative flex items-center justify-center rounded-xl p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 transition-all focus:outline-none"
    title="Open notifications"
>
    <!-- Prominent, Enlarged Bell Icon -->
    <svg class="fi-topbar-bell-icon w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
    </svg>

    @if ($unreadNotificationsCount > 0)
        <!-- Red Notification Badge / Indicator -->
        <span class="fi-icon-btn-badge-ctn absolute -top-1 -right-1 flex items-center justify-center">
            <span class="fi-badge flex items-center justify-center font-extrabold text-white rounded-full bg-red-600 shadow-lg" style="background-color: #ef4444 !important; color: #ffffff !important; min-width: 1.25rem; height: 1.25rem; padding: 0 5px; font-size: 0.6875rem; border: 2px solid #ffffff; box-shadow: 0 0 10px rgba(239, 68, 68, 0.65);">
                {{ $unreadNotificationsCount }}
            </span>
        </span>
    @endif
</button>