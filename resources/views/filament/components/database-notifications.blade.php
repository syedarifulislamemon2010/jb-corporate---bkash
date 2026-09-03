@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Support\View\Components\BadgeComponent;

    $notifications = $this->getNotifications();
    $unreadNotificationsCount = $this->getUnreadNotificationsCount();
    $hasNotifications = $notifications->count();
    $isPaginated = $notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages();
    $pollingInterval = $this->getPollingInterval();
    $currentTab = $this->activeTab ?? 'all';
    $tabCounts = $tabCounts ?? ['all' => 0, 'checker' => 0, 'auth1' => 0, 'auth2' => 0];
@endphp

<div class="fi-no-database">
    <x-filament::modal
        :alignment="$hasNotifications ? null : Alignment::Center"
        aria-labelledby="database-notifications.heading"
        close-button
        :description="$hasNotifications ? null : __('filament-notifications::database.modal.empty.description')"
        :extra-modal-window-attribute-bag="
            new \Filament\Support\View\ComponentAttributeBag([
                'autofocus' => true,
                'tabindex' => '-1',
                'class' => 'jb-notifications-slideover',
            ])
        "
        :heading="$hasNotifications ? null : __('filament-notifications::database.modal.empty.heading')"
        :icon="$hasNotifications ? null : \Filament\Support\Icons\Heroicon::OutlinedBellSlash"
        :icon-alias="
            $hasNotifications
            ? null
            : \Filament\Notifications\View\NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE
        "
        :icon-color="$hasNotifications ? null : 'gray'"
        id="database-notifications"
        slide-over
        :sticky-header="true"
        teleport="body"
        width="md"
        class="fi-no-database"
        :attributes="
            new \Filament\Support\View\ComponentAttributeBag([
                'wire:poll.' . $pollingInterval => $pollingInterval ? '' : false,
            ])
        "
    >
        @if ($trigger = $this->getTrigger())
            <x-slot name="trigger">
                {{ $trigger->with(['unreadNotificationsCount' => $unreadNotificationsCount]) }}
            </x-slot>
        @endif

        {{-- Slideover Header --}}
        <x-slot name="header">
            <div class="jb-notif-header">
                <div class="jb-notif-header-title-row">
                    <div class="jb-notif-title-group">
                        <h2 id="database-notifications.heading" class="jb-notif-heading">
                            Notifications
                        </h2>
                        @if ($unreadNotificationsCount)
                            <span class="jb-notif-unread-badge">
                                {{ $unreadNotificationsCount }} New
                            </span>
                        @endif
                    </div>

                    <div class="jb-notif-header-actions">
                        @if ($unreadNotificationsCount && $this->markAllNotificationsAsReadAction?->isVisible())
                            <button
                                type="button"
                                wire:click="markAllNotificationsAsRead"
                                class="jb-mark-all-btn"
                                title="Mark all as read"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Mark all as read</span>
                            </button>
                        @endif

                        @if ($this->clearNotificationsAction?->isVisible())
                            {{ $this->clearNotificationsAction }}
                        @endif
                    </div>
                </div>

                {{-- Category Filter Tabs --}}
                <div class="jb-notif-tabs-bar">
                    <button
                        type="button"
                        wire:click="setTab('all')"
                        @class([
                            'jb-notif-tab',
                            'active' => $currentTab === 'all',
                        ])
                    >
                        <span>সব</span>
                        <span class="jb-notif-tab-count">{{ $tabCounts['all'] ?? 0 }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="setTab('checker')"
                        @class([
                            'jb-notif-tab',
                            'active' => $currentTab === 'checker',
                        ])
                    >
                        <span>Checker</span>
                        <span class="jb-notif-tab-count">{{ $tabCounts['checker'] ?? 0 }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="setTab('auth1')"
                        @class([
                            'jb-notif-tab',
                            'active' => $currentTab === 'auth1',
                        ])
                    >
                        <span>1st Auth</span>
                        <span class="jb-notif-tab-count">{{ $tabCounts['auth1'] ?? 0 }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="setTab('auth2')"
                        @class([
                            'jb-notif-tab',
                            'active' => $currentTab === 'auth2',
                        ])
                    >
                        <span>2nd Auth</span>
                        <span class="jb-notif-tab-count">{{ $tabCounts['auth2'] ?? 0 }}</span>
                    </button>
                </div>
            </div>
        </x-slot>

        {{-- Notifications List or Empty State --}}
        @if ($hasNotifications)
            <div
                aria-label="{{ __('filament-notifications::database.modal.heading') }}"
                role="list"
                class="jb-notif-list"
            >
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data ?? [];
                        $title = $data['title'] ?? 'Notification';
                        $body = $data['body'] ?? '';
                        $isUnread = $notification->unread();
                        $dateStr = $notification->created_at ? $notification->created_at->diffForHumans() : '';
                        $actions = $data['actions'] ?? [];
                        $color = $data['color'] ?? 'info';
                        $category = $data['viewData']['category'] ?? $data['category'] ?? null;

                        // Derive smart category if not explicitly tagged
                        if (!$category) {
                            if (stripos($title, 'Checker') !== false || stripos($body, 'Checker') !== false || stripos($title, 'Settlement File') !== false) {
                                $category = 'checker';
                            } elseif (stripos($title, '1st Authoriz') !== false || stripos($body, '1st Authorizer') !== false) {
                                $category = 'authorizer_1';
                            } elseif (stripos($title, '2nd') !== false || stripos($title, 'Confirmation') !== false || stripos($title, 'Settlement') !== false) {
                                $category = 'authorizer_2';
                            } else {
                                $category = 'system';
                            }
                        }
                    @endphp

                    <div
                        role="listitem"
                        wire:key="{{ $notification->getKey() }}.jb-notif.item"
                        @class([
                            'jb-notif-card',
                            'is-unread' => $isUnread,
                            'cat-' . $category,
                        ])
                    >
                        <div class="jb-notif-card-inner">
                            {{-- Category Specific Icon --}}
                            <div class="jb-notif-icon-col">
                                <div @class([
                                    'jb-notif-icon-box',
                                    'icon-checker' => $category === 'checker',
                                    'icon-auth1'   => $category === 'authorizer_1',
                                    'icon-auth2'   => $category === 'authorizer_2',
                                    'icon-system'  => $category === 'system',
                                ])>
                                    @if ($category === 'checker')
                                        {{-- Ingest / Download Icon --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    @elseif ($category === 'authorizer_1')
                                        {{-- Shield Check Icon --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    @elseif ($category === 'authorizer_2')
                                        {{-- Key / Check Badge Icon --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                    @else
                                        {{-- Bell Icon --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                    @endif
                                </div>
                            </div>

                            {{-- Notification Content --}}
                            <div class="jb-notif-body-col">
                                <div class="jb-notif-title-row">
                                    <span class="jb-notif-title">{{ $title }}</span>
                                    @if ($isUnread)
                                        <span class="jb-unread-dot" title="Unread notification"></span>
                                    @endif
                                </div>

                                @if (!empty($body))
                                    <div class="jb-notif-desc">
                                        {!! nl2br(e($body)) !!}
                                    </div>
                                @endif

                                <div class="jb-notif-footer-row">
                                    <span class="jb-notif-time">{{ $dateStr }}</span>

                                    <div class="jb-notif-item-actions">
                                        {{-- Action Links --}}
                                        @foreach ($actions as $action)
                                            @if (!empty($action['url']))
                                                <a
                                                    href="{{ $action['url'] }}"
                                                    class="jb-notif-action-btn"
                                                    wire:click="markNotificationAsRead('{{ $notification->id }}')"
                                                >
                                                    <span>{{ $action['label'] ?? 'View Details' }}</span>
                                                </a>
                                            @endif
                                        @endforeach

                                        {{-- Individual Mark as Read / Unread toggle --}}
                                        @if ($isUnread)
                                            <button
                                                type="button"
                                                wire:click="markNotificationAsRead('{{ $notification->id }}')"
                                                class="jb-mark-single-read-btn"
                                                title="Mark as read"
                                            >
                                                Mark read
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($broadcastChannel = $this->getBroadcastChannel())
                @script
                    <script>
                        window.addEventListener('EchoLoaded', () => {
                            window.Echo.private(@js($broadcastChannel)).listen(
                                '.database-notifications.sent',
                                () => {
                                    setTimeout(() => $wire.call('$refresh'), 500)
                                },
                            )
                        })

                        if (window.Echo) {
                            window.dispatchEvent(new CustomEvent('EchoLoaded'))
                        }
                    </script>
                @endscript
            @endif

            @if ($isPaginated)
                <x-slot name="footer">
                    <x-filament::pagination :paginator="$notifications" />
                </x-slot>
            @endif
        @else
            {{-- Modern Empty State --}}
            <div class="jb-notif-empty-state">
                <div class="jb-notif-empty-icon">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <h3 class="jb-notif-empty-title">
                    {{ __('filament-notifications::database.modal.empty.heading') ?: 'No notifications' }}
                </h3>
                <p class="jb-notif-empty-desc">
                    {{ __('filament-notifications::database.modal.empty.description') ?: 'Please check again later' }}
                </p>
            </div>
        @endif
    </x-filament::modal>
</div>