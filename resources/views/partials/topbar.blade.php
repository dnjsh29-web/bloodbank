@php
    $portalName = $portal ?? 'donor';
    $shellHref = $portalName === 'donor'
        ? route('donor.shell')
        : ($portalName === 'super' ? route('super.section') : route('admin.section'));
    $panelRoute = fn (string $key) => $portalName === 'donor'
        ? route('donor.panel', $key)
        : ($portalName === 'super' ? route('super.panel', $key) : route('admin.panel', $key));
    $profileHref = $portalName === 'donor'
        ? $shellHref.'#personal-info'
        : $shellHref.'#donor-records';
    $profilePanel = $portalName === 'donor' ? 'personal-info' : 'donor-records';
    $settingsHref = $portalName === 'donor'
        ? $shellHref.'#account-security'
        : $shellHref.'#security';
    $settingsPanel = $portalName === 'donor' ? 'account-security' : 'security';
    $notificationsHref = $portalName === 'admin' ? $shellHref.'#notifications' : $settingsHref;
    $notificationsPanel = $portalName === 'admin' ? 'notifications' : $settingsPanel;
    $unreadCount = (int) ($unreadNotificationCount ?? 0);
@endphp

<header class="topbar">
    <div class="flex min-w-0 items-center gap-3">
        <h1 class="topbar-title" data-topbar-title>{{ $heading ?? 'RedCross Blood Bank' }}</h1>
    </div>
    <div class="topbar-actions">
        <div class="search-pill">
            <i data-lucide="search"></i>
            <input placeholder="Search records..." aria-label="Search records">
        </div>
        @if ($portalName === 'donor')
            <button class="top-icon-btn notification-bell" type="button" data-notification-drawer-open aria-label="Open notifications" aria-controls="notification-drawer">
                <i data-lucide="bell"></i>
                <span class="notification-badge" data-notification-global-count @if ($unreadCount === 0) hidden @endif>{{ $unreadCount }}</span>
            </button>
        @else
            <a class="top-icon-btn" href="{{ $notificationsHref }}" data-portal-tab="{{ $notificationsPanel }}" data-panel-url="{{ $panelRoute($notificationsPanel) }}" data-title="{{ $portalName === 'admin' ? 'Notifications' : 'Account Security' }}" aria-label="Open notifications">
                <i data-lucide="bell"></i>
            </a>
        @endif
        <a class="top-icon-btn" href="{{ $settingsHref }}" data-portal-tab="{{ $settingsPanel }}" data-panel-url="{{ $panelRoute($settingsPanel) }}" data-title="Account Security" aria-label="Open settings">
            <i data-lucide="settings"></i>
        </a>
        <a class="top-icon-btn" href="{{ $profileHref }}" data-portal-tab="{{ $profilePanel }}" data-panel-url="{{ $panelRoute($profilePanel) }}" data-title="{{ $portalName === 'donor' ? 'Personal Info' : 'Donor Records' }}" aria-label="Open profile">
            <i data-lucide="user"></i>
        </a>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="top-icon-btn" type="submit" aria-label="Logout">
                <i data-lucide="log-out"></i>
            </button>
        </form>
    </div>
</header>
