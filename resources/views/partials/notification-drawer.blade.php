@php
    $notificationRows = collect($notifications ?? [])->values();
    $unreadCount = $notificationRows->where('is_read', false)->count();
    $readUrlTemplate = route('notifications.read', ['notification' => '__NOTIFICATION_ID__']);
@endphp

<aside
    id="notification-drawer"
    class="notification-drawer"
    data-notification-drawer
    hidden
    aria-hidden="true"
>
    <button class="notification-drawer-backdrop" type="button" data-notification-drawer-close aria-label="Close notifications"></button>
    <section
        class="notification-drawer-panel"
        data-notification-center
        data-notification-scope="drawer"
        data-read-url-template="{{ $readUrlTemplate }}"
        data-read-all-url="{{ route('notifications.readAll') }}"
        aria-label="Notifications"
    >
        <header class="notification-drawer-header">
            <div>
                <p class="eyebrow">Notification Center</p>
                <h2>Notifications</h2>
            </div>
            <button class="top-icon-btn" type="button" data-notification-drawer-close aria-label="Close notifications">
                <i data-lucide="x"></i>
            </button>
        </header>

        <div class="notification-tabs">
            <button class="badge is-active" type="button" data-notification-filter="all">All</button>
            <button class="badge" type="button" data-notification-filter="unread">Unread (<span data-unread-count>{{ $unreadCount }}</span>)</button>
            <button class="badge" type="button" data-notification-filter="matching">Matching Alerts</button>
        </div>

        <div class="notification-list">
            @forelse ($notificationRows as $note)
                @php
                    $noteTag = $note['tag'] ?? $note['type'] ?? 'Alert';
                    $noteBody = $note['body'] ?? $note['text'] ?? '';
                    $isRead = (bool) ($note['is_read'] ?? false);
                    $filterTags = ['all'];
                    if (! $isRead) {
                        $filterTags[] = 'unread';
                    }
                    if (($note['filter_type'] ?? '') === 'matching' || str_contains(strtolower($noteTag), 'matching')) {
                        $filterTags[] = 'matching';
                    }
                @endphp
                <article
                    class="notification-item"
                    data-notification-item
                    data-notification-id="{{ $note['id'] ?? '' }}"
                    data-note-read="{{ $isRead ? 'true' : 'false' }}"
                    data-note-filters="{{ implode(',', $filterTags) }}"
                    data-note-tag="{{ $noteTag }}"
                    data-note-title="{{ $note['title'] ?? 'Notification' }}"
                    data-note-time="{{ $note['display_time'] ?? $note['time_label'] ?? 'Unread' }}"
                    data-note-body="{{ $noteBody }}"
                    role="button"
                    tabindex="0"
                >
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="badge">{{ $noteTag }}</span>
                        <span class="text-xs text-stone-500" data-note-time>{{ $note['display_time'] ?? $note['time_label'] ?? 'Unread' }}</span>
                    </div>
                    <h3 class="font-bold">{{ $note['title'] ?? 'Notification' }}</h3>
                    <p class="mt-2 text-sm text-stone-600">{{ $noteBody }}</p>
                </article>
            @empty
                <div class="notification-empty">
                    <i data-lucide="bell-off"></i>
                    <h3>No notifications yet</h3>
                    <p>New donor updates and matching alerts will appear here.</p>
                </div>
            @endforelse
            <div class="notification-empty" data-notification-empty hidden>
                <i data-lucide="bell-off"></i>
                <h3>No notifications found</h3>
                <p>This filter does not have any visible notifications.</p>
            </div>
        </div>

        @if ($notificationRows->isNotEmpty())
            <button class="btn-outline mt-4 w-full" type="button" data-notification-mark-read>Mark All As Read</button>
        @endif
    </section>
</aside>
