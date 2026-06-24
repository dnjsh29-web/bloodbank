@php
    $portalName = $portal ?? 'donor';
    $isDonor = $portalName === 'donor';
    $isSuper = $portalName === 'super';
    $items = $isDonor
        ? [
            ['Dashboard', route('donor.dashboard'), 'dashboard', 'home'],
            ['Schedule', route('donor.schedule'), 'schedule', 'calendar'],
            ['History', route('donor.history'), 'history', 'history'],
            ['Personal Info', route('donor.profile'), 'personal-info', 'user'],
            ['Account Security', route('donor.security'), 'account-security', 'shield'],
        ]
        : ($isSuper
            ? [
                ['Admin Dashboard', route('super.section', 'overview'), 'overview', 'database'],
                ['Blood Inventory', route('super.section', 'inventory'), 'inventory', 'droplet'],
                ['Donor Records', route('super.section', 'donor-records'), 'donor-records', 'users'],
                ['Security', route('super.section', 'security'), 'security', 'shield'],
            ]
            : [
                ['Reports', route('admin.section', 'reports'), 'reports', 'file-text'],
                ['Notifications', route('admin.section', 'notifications'), 'notifications', 'bell'],
                ['Donation Map', route('admin.section', 'map'), 'map', 'map'],
                ['Donor Records', route('admin.section', 'donor-records'), 'donor-records', 'users'],
                ['Blood Inventory', route('admin.section', 'inventory'), 'inventory', 'droplet'],
                ['Campaigns', route('admin.section', 'campaigns'), 'campaigns', 'megaphone'],
            ]);
    $activeKey = $active ?? $section ?? '';
    $shellHref = $isDonor ? route('donor.shell') : ($isSuper ? route('super.section') : route('admin.section'));
    $panelRoute = fn (string $key) => $isDonor
        ? route('donor.panel', $key)
        : ($isSuper ? route('super.panel', $key) : route('admin.panel', $key));
    $actionKey = $isDonor ? 'schedule' : 'inventory';
    $actionHref = $shellHref.'#'.$actionKey;
    $actionText = $isDonor ? 'New Donation' : ($isSuper ? 'Log Donation' : 'New Donation');
@endphp

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-title">{{ $isDonor ? 'RedCross Blood Bank' : 'RedCross Admin' }}</div>
        <div class="sidebar-subtitle">{{ $isDonor ? (($profile['blood_type'] ?? 'Type O Negative').' Donor') : 'Blood Bank Management' }}</div>
    </div>

    <a class="btn-primary mb-4 w-full" href="{{ $actionHref }}" data-portal-tab="{{ $actionKey }}" data-panel-url="{{ $panelRoute($actionKey) }}" data-title="{{ $isDonor ? 'Schedule' : 'Blood Inventory' }}" @unless ($isDonor) data-new-donation @endunless>
        <i data-lucide="plus" class="icon"></i>
        <span>{{ $actionText }}</span>
    </a>

    <nav class="sidebar-nav">
        @foreach ($items as [$label, $href, $key, $icon])
            <a class="sidebar-link {{ $activeKey === $key ? 'is-active' : '' }}" href="{{ $shellHref }}#{{ $key }}" data-portal-tab="{{ $key }}" data-panel-url="{{ $panelRoute($key) }}" data-title="{{ $label }}">
                <i data-lucide="{{ $icon }}" class="sidebar-icon"></i>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    <form class="mt-auto" method="post" action="{{ route('logout') }}">
        @csrf
        <button class="sidebar-logout w-full" type="submit">
            <i data-lucide="log-out" class="sidebar-icon"></i>
            <span>Logout</span>
        </button>
    </form>
</aside>

<div class="mobile-rail md:hidden" data-mobile-rail aria-label="Portal shortcuts">
    <button class="mobile-rail-brand" type="button" data-mobile-menu-open aria-label="Open navigation menu" aria-expanded="false">
        <i data-lucide="{{ $isDonor ? 'droplet' : 'shield-plus' }}" class="sidebar-icon"></i>
    </button>

    <a class="mobile-rail-action" href="{{ $actionHref }}" data-mobile-rail-tab data-portal-tab="{{ $actionKey }}" data-panel-url="{{ $panelRoute($actionKey) }}" data-title="{{ $isDonor ? 'Schedule' : 'Blood Inventory' }}" aria-label="{{ $actionText }}" @unless ($isDonor) data-new-donation @endunless>
        <i data-lucide="plus" class="sidebar-icon"></i>
    </a>

    <nav class="mobile-rail-nav" aria-label="Primary">
        @foreach ($items as [$label, $href, $key, $icon])
            <a class="mobile-rail-link {{ $activeKey === $key ? 'is-active' : '' }}" href="{{ $shellHref }}#{{ $key }}" data-mobile-rail-tab data-portal-tab="{{ $key }}" data-panel-url="{{ $panelRoute($key) }}" data-title="{{ $label }}" aria-label="{{ $label }}">
                <i data-lucide="{{ $icon }}" class="sidebar-icon"></i>
            </a>
        @endforeach
    </nav>

    <button class="mobile-rail-menu" type="button" data-mobile-menu-open aria-label="Open navigation menu" aria-expanded="false">
        <i data-lucide="menu" class="sidebar-icon"></i>
    </button>
</div>

<div class="mobile-drawer-backdrop md:hidden" data-mobile-drawer-backdrop hidden></div>

<aside class="mobile-drawer md:hidden" data-mobile-drawer aria-hidden="true" aria-label="Mobile navigation" hidden>
    <div class="mobile-drawer-header">
        <div>
            <div class="mobile-drawer-title">{{ $isDonor ? 'RedCross Blood Bank' : 'RedCross Admin' }}</div>
            <div class="sidebar-subtitle p-0">{{ $isDonor ? (($profile['blood_type'] ?? 'Type O Negative').' Donor') : 'Blood Bank Management' }}</div>
        </div>
        <button class="top-icon-btn" type="button" data-mobile-drawer-close aria-label="Close navigation menu">
            <i data-lucide="x" class="sidebar-icon"></i>
        </button>
    </div>

    <a class="btn-primary mobile-drawer-action" href="{{ $actionHref }}" data-mobile-drawer-tab data-portal-tab="{{ $actionKey }}" data-panel-url="{{ $panelRoute($actionKey) }}" data-title="{{ $isDonor ? 'Schedule' : 'Blood Inventory' }}" @unless ($isDonor) data-new-donation @endunless>
        <i data-lucide="plus" class="icon"></i>
        <span>{{ $actionText }}</span>
    </a>

    <nav class="mobile-drawer-nav" aria-label="Primary navigation">
        @foreach ($items as [$label, $href, $key, $icon])
            <a class="sidebar-link {{ $activeKey === $key ? 'is-active' : '' }}" href="{{ $shellHref }}#{{ $key }}" data-mobile-drawer-tab data-portal-tab="{{ $key }}" data-panel-url="{{ $panelRoute($key) }}" data-title="{{ $label }}">
                <i data-lucide="{{ $icon }}" class="sidebar-icon"></i>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    <form class="mobile-drawer-logout" method="post" action="{{ route('logout') }}">
        @csrf
        <button class="sidebar-logout w-full" type="submit">
            <i data-lucide="log-out" class="sidebar-icon"></i>
            <span>Logout</span>
        </button>
    </form>
</aside>
