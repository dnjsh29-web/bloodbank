@extends('layouts.portal', [
    'title' => $portal === 'super' ? 'Super Admin' : 'RedCross Admin',
    'heading' => $portal === 'super'
        ? ($section === 'overview' ? 'Admin Dashboard' : ($section === 'donor-records' ? 'Donor Records' : ucfirst(str_replace('-', ' ', $section))))
        : ucfirst(str_replace('-', ' ', $section)),
    'portal' => $portal,
    'active' => $section,
])

@section('portal')
@php
    $isSuper = $portal === 'super';
    $actionRoute = $isSuper ? route('super.audit.action') : route('admin.audit.action');

    $fallbackDonors = [
        ['full_name' => 'Arthur Morgan', 'donor_code' => 'D-10294', 'blood_type' => 'O-', 'email' => 'arthur.m@example.com', 'contact' => '', 'last_donation_label' => 'Nov 12, 2023', 'eligibility_status' => 'Eligible', 'total_units' => 12],
        ['full_name' => 'Sadie Crawford', 'donor_code' => 'D-22910', 'blood_type' => 'A+', 'email' => 'sadie.c@example.com', 'contact' => '', 'last_donation_label' => 'Dec 01, 2023', 'eligibility_status' => 'Deferred', 'total_units' => 4],
        ['full_name' => 'John Dutton', 'donor_code' => 'D-55821', 'blood_type' => 'B-', 'email' => 'john.d@example.com', 'contact' => '', 'last_donation_label' => 'Aug 24, 2023', 'eligibility_status' => 'Eligible', 'total_units' => 7],
    ];
    $donorRows = collect($donors)->isNotEmpty() ? collect($donors)->take(10)->values()->all() : $fallbackDonors;
    $bloodTypes = ['O+','O-','A+','A-','B+','B-','AB+','AB-'];
    $reportColors = [
        'O+' => '#b70100',
        'O-' => '#9a452a',
        'A+' => '#775043',
        'A-' => '#ba1a1a',
        'B+' => '#ff9473',
        'B-' => '#ffb59f',
        'AB+' => '#623e32',
        'AB-' => '#e60000',
    ];
    $reportSource = collect($donors)->isNotEmpty() ? collect($donors)->values() : collect($fallbackDonors);
    $reportTotal = max(1, $reportSource->count());
    $statusBucket = function ($donor) {
        $status = strtolower((string) ($donor['eligibility_status'] ?? $donor['status'] ?? 'Eligible'));
        return str_contains($status, 'defer') ? 'deferred' : (str_contains($status, 'ineligible') ? 'ineligible' : 'eligible');
    };
    $buildTrend = function ($rows) {
        $trend = array_fill(0, 7, 0);
        collect($rows)->values()->each(function ($row, $index) use (&$trend) {
            $trend[$index % 7]++;
        });
        return $trend;
    };
    $buildReportData = function ($type) use ($reportSource, $reportTotal, $statusBucket, $buildTrend) {
        $rows = $type === 'All'
            ? $reportSource
            : $reportSource->filter(fn ($donor) => ($donor['blood_type'] ?? null) === $type)->values();
        $count = $rows->count();
        $denominator = max(1, $count);
        $eligibleCount = $rows->filter(fn ($donor) => $statusBucket($donor) === 'eligible')->count();
        $deferredCount = $rows->filter(fn ($donor) => $statusBucket($donor) === 'deferred')->count();
        $ineligibleCount = $rows->filter(fn ($donor) => $statusBucket($donor) === 'ineligible')->count();

        return [
            'registered' => $count,
            'eligible' => $eligibleCount,
            'deferred' => $deferredCount,
            'ineligible' => $ineligibleCount,
            'screened' => $count,
            'subtext' => $type === 'All' ? 'all blood types' : "{$type} donors",
            'screenedLabel' => $type === 'All' ? 'All screenings' : "{$type} screenings",
            'ratios' => [
                'eligible' => (int) round(($eligibleCount / $denominator) * 100),
                'deferred' => (int) round(($deferredCount / $denominator) * 100),
                'ineligible' => (int) round(($ineligibleCount / $denominator) * 100),
            ],
            'share' => $type === 'All' ? null : round(($count / $reportTotal) * 100, 1),
            'trend' => $buildTrend($rows),
        ];
    };
    $reportStats = ['All' => $buildReportData('All')];
    foreach ($bloodTypes as $type) {
        $reportStats[$type] = $buildReportData($type);
    }
    $reportDistribution = collect($bloodTypes)->map(fn ($type) => [
        'type' => $type,
        'share' => $reportStats[$type]['share'],
        'color' => $reportColors[$type],
    ])->values()->all();
    $initialReport = $reportStats['All'];

    $fallbackCampaigns = [
        ['title' => 'Spring Drive 2024', 'description' => 'Annual spring blood collection drive across regional community centers targeting type-O donors.', 'date_range' => 'March 15 - April 10', 'locations' => '12 Locations', 'status' => 'Upcoming', 'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCDisvyxYy-VGYKouaf8rmM1lDjqGnl25OQfx7ISsGsj-U8UxUSkyQdSHdCclYgkQqHGmpINiOj4_JqxUOfJtSZykflKxrxJZuynte4Vh_QaXwQDQjc5KIM8hzVTA7nME-d3C31QS6Mv-y8eXJITNvRWR_bG0-6V4KP3iAiOeQF9BMT8cpew9WxBFPGZAkh6ECv7WHkZfw3ZnuF5Hnl3MYM0-_BvAA4RDRAuAoHt5u0zffPk-bnjSMOJWBZm9qq6uLwcX-6G3gTyKE'],
        ['title' => 'Community Heroes Week', 'description' => 'Mobile donation campaign for offices, schools, and neighborhood health partners in Laguna.', 'date_range' => 'April 22 - April 30', 'locations' => 'Santa Rosa, Laguna', 'status' => 'Open', 'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDywHSBgfb-2Nl7yl4zUzIllz3YWGraPzPnuA9LxxnB6LLoViAK_oFHePRKi71m92loEOOWLxToqBLXK_hjXGxf-H61ncJiWMp-O9AqyrXX3hTJHA5xL6MNjAh1ztaS-cDQhHhYGG-zS0jZlhC8vlEQ53Up0_VZcWR9EpdHG4-v16vvUUrIkS3lCvk_gJJUxqanVEKHv_HmAVkwLLg_9ZSAk8ZaYRzH8Z6L8ENF_pV3wKYZhRxfNaHK-MDDu7oiMVc_Tfzmz63j1Mg'],
        ['title' => 'Corporate Lifeline Drive', 'description' => 'Corporate partner drive focused on employee donor registration and platelet awareness.', 'date_range' => 'May 03 - May 18', 'locations' => '8 Corporate Sites', 'status' => 'Upcoming', 'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBRugzP9wGrYoqHab5jozufSA4_T5ZjREGPt8W2kYdBp3uAjERW-1h_lbe29D_3FihJakRylna5TQIBZY3F7AcGRhTKatg5Cowyb6nXyZevWUP4dqPi9f5pvo2zT6876wCREEvjFRU9HqunHiHx9W_cgj8W_4DhhhXzllRIPu55Nrjc1dgNBAvYfvaf3MgGYvV8MxtYJT1WvQ_CQddhLtNmxIcaml4sazmLShV9QCkcKCmjHWQGdn_B4SVYsHAsnTTOmBN66Op91Fw'],
        ['title' => 'Emergency Stock Boost', 'description' => 'Emergency preparedness campaign supporting urgent blood stock readiness before typhoon season.', 'date_range' => 'June 01 - June 14', 'locations' => 'Regional Centers', 'status' => 'Urgent', 'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDX95p7cBihcJwMwTB-QWFzRghozp6gzKOuI4dwY_Hd0gdS46STq6EoMPYP3iQHuulBGDbiOwyz3Kd7vgFbcZxHIMnrQnwwXDAdGv9nrseI5PZf786ZOYyC5zvzQYSR3UHo3wpa3JQT9C63P4PlGbiIwE2fJDKU7ZYzUX2HAjfhOuoOQf7iP56ImjUDx_CsfAwEpJnqJHadUNmMudKyUGwzgLWVS_JKL99wHYz3UJfwbvFDMUBhXTlGjSrJKy-Fh6MFEiHR6D4_G0w'],
        ['title' => 'Youth Donor Mission', 'description' => 'Youth volunteer recruitment and first-time donor education campaign for college campuses.', 'date_range' => 'July 08 - July 20', 'locations' => '5 Campuses', 'status' => 'Planning', 'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBMKC2FnXEnpFQCMT-Cxrqim6mgxs-wlRl9VJ7_ez-M8ZoY2nN2pslWQEinlWdZHO4Z46xccb559RC0iZP0tiEsAGMaqk1T6dyhMOSVE_tUA7t-pBzMvUS0pcDGA81wHIWepxhUACz80BsXoeMINkQbiLOSLGKuche3tANQIiK9B0rFyYBCwhRI0txyD4WayeHQHf0oOcvVtwLBp40VQl0Tkufy4p9ENOSEHD3r_8-oUIfjdV1B4GwZNhRiy57cxnjjhzyW2bcXcFI'],
    ];
    $campaignRows = collect($campaigns)->isNotEmpty() ? collect($campaigns)->take(5)->values()->all() : $fallbackCampaigns;

    $stockDefaults = [
        ['type' => 'O+', 'units' => 412, 'goal' => 400, 'status' => 'Optimal'],
        ['type' => 'O-', 'units' => 45, 'goal' => 100, 'status' => 'Critical'],
        ['type' => 'A+', 'units' => 380, 'goal' => 350, 'status' => 'Optimal'],
        ['type' => 'A-', 'units' => 112, 'goal' => 150, 'status' => 'Low'],
        ['type' => 'B+', 'units' => 82, 'goal' => 200, 'status' => 'Critical'],
        ['type' => 'B-', 'units' => 65, 'goal' => 100, 'status' => 'Low'],
        ['type' => 'AB+', 'units' => 220, 'goal' => 200, 'status' => 'Optimal'],
        ['type' => 'AB-', 'units' => 24, 'goal' => 50, 'status' => 'Critical'],
    ];
    $stockRows = collect($stockDefaults)->map(function ($stock) use ($inventory) {
        $units = collect($inventory)->where('blood_type', $stock['type'])->sum('units');
        if ($units > 0) {
            $stock['units'] = $units;
            $stock['status'] = $units < 100 ? 'Critical' : ($units < $stock['goal'] ? 'Low' : 'Optimal');
        }
        return $stock;
    })->all();
    $totalUnits = collect($stockRows)->sum('units');
    $eligible = $initialReport['eligible'];
    $deferred = $initialReport['deferred'];
    $criticalTypes = $isSuper ? 'O-, AB-' : (collect($stockRows)->where('status', 'Critical')->pluck('type')->implode(', ') ?: 'O-, AB-');

    $notificationRows = collect($notifications)->take(25)->values()->all();
    $adminUnreadCount = collect($notificationRows)->where('is_read', false)->count();
    $notificationReadUrlTemplate = route('notifications.read', ['notification' => '__NOTIFICATION_ID__']);

    $mapFallback = [[
        'id' => 'CTR-SR-LAGUNA',
        'name' => 'PRC Laguna Chapter - Santa Rosa Branch',
        'center_type' => 'Santa Rosa Red Cross',
        'address' => 'Rotary Lane, Brgy. Tagapo, City of Santa Rosa, Laguna',
        'latitude' => 14.31554,
        'longitude' => 121.11104,
    ]];
    $mapCenters = collect($centers ?? [])
        ->map(fn ($center) => [
            'id' => $center['id'] ?? 'CTR-SR-LAGUNA',
            'name' => $center['name'] ?? 'PRC Laguna Chapter - Santa Rosa Branch',
            'type' => $center['center_type'] ?? $center['type'] ?? 'Donation Center',
            'address' => $center['address'] ?? 'Address not provided',
            'lat' => (float) ($center['latitude'] ?? $center['lat'] ?? 0),
            'lng' => (float) ($center['longitude'] ?? $center['lng'] ?? 0),
        ])
        ->filter(fn ($center) => $center['lat'] !== 0.0 && $center['lng'] !== 0.0)
        ->values();
    if ($mapCenters->isEmpty()) {
        $mapCenters = collect($mapFallback)->map(fn ($center) => [
            'id' => $center['id'],
            'name' => $center['name'],
            'type' => $center['center_type'],
            'address' => $center['address'],
            'lat' => $center['latitude'],
            'lng' => $center['longitude'],
        ]);
    }

    $fallbackInventoryRows = [
        ['blood_type' => 'O-', 'component_type' => 'Whole Blood', 'unit_code' => '#88219-BC', 'collection_date' => '2024-10-12', 'status' => 'Expiring Soon'],
        ['blood_type' => 'A+', 'component_type' => 'Platelets', 'unit_code' => '#44102-XY', 'collection_date' => '2024-10-20', 'status' => 'Available'],
        ['blood_type' => 'B+', 'component_type' => 'Plasma', 'unit_code' => '#11293-ZZ', 'collection_date' => '2024-10-18', 'status' => 'Available'],
    ];
    $inventoryRows = collect($inventory)->isNotEmpty() ? collect($inventory)->map(fn ($row) => [
        'blood_type' => $row['blood_type'] ?? 'Unknown',
        'component_type' => $row['component_type'] ?? 'Whole Blood',
        'unit_code' => $row['unit_code'] ?? $row['id'] ?? 'Not provided',
        'collection_date' => $row['collection_date'] ?? $row['created_at'] ?? null,
        'status' => $row['status'] ?? 'Available',
    ])->values() : collect($fallbackInventoryRows);
    $inventoryStatuses = $inventoryRows->pluck('status')->filter()->unique()->sort()->values();

    $auditRows = collect($auditLogs)->isNotEmpty() ? collect($auditLogs)->take(4)->map(fn ($log) => [
        'time' => isset($log['created_at']) ? date('H:i:s', strtotime($log['created_at'])) : '14:22:15',
        'user' => $log['actor_name'] ?? 'System',
        'role' => $log['actor_role'] ?? 'Admin',
        'action' => $log['action'] ?? 'System activity',
        'location' => $log['ip_address'] ?? 'Local',
        'status' => $log['status'] ?? 'Success',
    ])->values()->all() : [
        ['time' => '14:22:15', 'user' => 'Sarah Jenkins', 'role' => 'Senior Nurse', 'action' => 'Accessed Patient Record #1029', 'location' => 'Nurse Station 4 (192.168.1.45)', 'status' => 'Success'],
        ['time' => '14:18:04', 'user' => 'Mark Davis', 'role' => 'Admin', 'action' => 'Updated Inventory Thresholds', 'location' => 'Admin Suite (192.168.1.12)', 'status' => 'Success'],
        ['time' => '14:05:32', 'user' => 'Unknown User', 'role' => 'Unauthorized', 'action' => 'Unauthorized Login Attempt', 'location' => 'External (45.122.10.8)', 'status' => 'Failed'],
        ['time' => '13:55:10', 'user' => 'Alex Rivera', 'role' => 'Clinical Director', 'action' => 'Generated Regional Stock Report', 'location' => 'Remote Access (VPN)', 'status' => 'Success'],
    ];
    $reviewQueue = collect($appointments ?? [])
        ->filter(fn ($appointment) => in_array(strtolower((string) ($appointment['status'] ?? '')), ['confirmed', 'pending', 'booked', 'submitted'], true))
        ->take(5)
        ->values()
        ->all();
@endphp

@if ($section === 'overview')
    <div class="mb-10 flex flex-wrap items-start justify-between gap-4">
        <header>
            <h1 class="page-title">Global Overview</h1>
            <p class="page-subtitle">Command Center operations looking normal.</p>
        </header>
        <div class="flex gap-3">
            <div class="card flex items-center gap-3 px-5 py-3"><i data-lucide="droplet" class="red"></i><div><p class="eyebrow">Inventory</p><p class="font-bold">Optimal</p></div></div>
            <div class="card flex items-center gap-3 px-5 py-3"><i data-lucide="shield" class="red"></i><div><p class="eyebrow">Security</p><p class="font-bold">Low Threat</p></div></div>
        </div>
    </div>

    <section class="grid gap-4 lg:grid-cols-[2fr_1fr_1fr_1fr]">
        <article class="card p-5"><p class="eyebrow">Total Blood Units</p><p class="mt-4 text-3xl font-extrabold text-red-700">{{ number_format($totalUnits ?: 56) }}</p><p class="mt-4 text-sm text-red-700">Up +12% vs last week</p></article>
        <article class="card p-5"><p class="eyebrow">Expiring Soon <i data-lucide="history" class="inline size-3"></i></p><p class="mt-6 text-3xl font-extrabold">12</p><p class="mt-4 text-sm text-stone-600">&lt; 7 Days</p></article>
        <article class="card p-5"><p class="eyebrow text-red-700">Critical Lows <i data-lucide="alert-triangle" class="inline size-3"></i></p><p class="mt-6 text-3xl font-extrabold text-red-700">{{ $criticalTypes }}</p><p class="mt-4 text-sm text-red-700">Action Required</p></article>
        <article class="card p-5"><p class="eyebrow">Active Sessions <i data-lucide="users" class="inline size-3"></i></p><p class="mt-6 text-3xl font-extrabold">18</p><p class="mt-4 text-sm text-stone-600">Regional Admins</p></article>
    </section>

    <section class="mt-6 grid gap-4 lg:grid-cols-2">
        <article class="card p-5">
            <div class="mb-4 flex items-center justify-between border-b border-red-100 pb-4">
                <h2 class="section-title flex items-center gap-2"><i data-lucide="database" class="red"></i> Inventory Snapshot</h2>
                <a class="text-sm font-bold text-red-700" href="{{ route('super.section') }}#inventory" data-portal-tab="inventory" data-panel-url="{{ route('super.panel', 'inventory') }}" data-title="Blood Inventory">Full View -></a>
            </div>
            <div class="bar-chart">
                @foreach ([['O+', 80, 412], ['O-', 58, 75], ['A+', 98, 380], ['A-', 32, 112], ['B+', 68, 82], ['B-', 18, 65], ['AB+', 90, 390], ['AB-', 44, 90]] as [$type, $height, $units])
                    <div class="bar" data-tooltip="{{ $type }}: {{ $units }}" style="height: {{ $height }}%"></div>
                @endforeach
            </div>
            <h3 class="eyebrow mt-4">Critical Stock Levels</h3>
            <div class="mt-3 space-y-3">
                <div class="flex items-center justify-between rounded-lg border border-red-100 p-3"><span><span class="badge">O-</span> Universal Donor</span><span class="badge is-active">75 Units</span></div>
                <div class="flex items-center justify-between rounded-lg border border-red-100 p-3"><span><span class="badge">AB-</span> Rare Type</span><span class="badge is-active">90 Units</span></div>
            </div>
        </article>
        <article class="card p-5">
            <div class="mb-4 flex items-center justify-between border-b border-red-100 pb-4">
                <h2 class="section-title flex items-center gap-2"><i data-lucide="shield" class="red"></i> Security Intelligence</h2>
                <a class="text-sm font-bold text-red-700" href="{{ route('super.section') }}#security" data-portal-tab="security" data-panel-url="{{ route('super.panel', 'security') }}" data-title="Security">View Logs -></a>
            </div>
            <div class="space-y-5">
                @foreach ([['Failed Login Attempt','Multiple failed attempts from IP 192.168.1.105 targeting user admin_central.','2 mins ago'],['Privilege Escalation',"User jdoe role updated to 'Regional Manager' by Super Admin.",'15 mins ago'],['Bulk Data Export','Inventory history exported for Region North. Action logged.','1 hr ago']] as [$title, $text, $time])
                    <div class="flex gap-3 text-sm">
                        <span class="avatar"><i data-lucide="alert-triangle" class="size-4"></i></span>
                        <div class="flex-1"><p class="font-bold">{{ $title }}</p><p class="text-stone-600">{{ $text }}</p></div>
                        <span class="text-stone-500">{{ $time }}</span>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="card table-card mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-red-100 p-4">
            <div>
                <h2 class="section-title">Donation Review Queue</h2>
                <p class="text-sm text-stone-600">Approve only donors who are fully eligible. Approved appointments count on the donor dashboard.</p>
            </div>
            <span class="badge">{{ count($reviewQueue) }} Pending</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Appointment</th><th>Center</th><th>Date & Time</th><th>Status</th><th>Review</th></tr>
                </thead>
                <tbody>
                    @forelse ($reviewQueue as $appointment)
                        @php
                            $payload = is_array($appointment['form_payload'] ?? null) ? $appointment['form_payload'] : [];
                            $donorForm = is_array($payload['donor_form'] ?? null) ? $payload['donor_form'] : [];
                            $requestForm = is_array($payload['blood_request_form'] ?? null) ? $payload['blood_request_form'] : [];
                            $summary = ($appointment['service_type'] ?? '') === 'Blood Request'
                                ? trim(($requestForm['patient_name'] ?? 'Patient pending').' - '.($requestForm['blood_type'] ?? 'blood type pending').' - '.($requestForm['units_needed'] ?? '0').' units')
                                : trim(($appointment['donor_full_name'] ?? $donorForm['full_name'] ?? 'Donor pending').' - '.($appointment['donor_blood_type'] ?? $donorForm['blood_type'] ?? 'blood type pending'));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $appointment['service_type'] ?? 'Donor Appointment' }}</strong><br>
                                <span class="text-xs text-stone-600">{{ $summary }}</span><br>
                                <span class="text-xs text-stone-500">Profile {{ substr((string) ($appointment['profile_id'] ?? 'unknown'), 0, 8) }}</span>
                            </td>
                            <td>{{ $appointment['center_name'] ?? 'PRC Laguna Chapter - Santa Rosa Branch' }}</td>
                            <td>{{ trim(($appointment['scheduled_date'] ?? '').' '.($appointment['scheduled_time'] ?? '')) ?: 'Not scheduled' }}</td>
                            <td><span class="badge">{{ $appointment['status'] ?? 'Confirmed' }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <form method="post" action="{{ route('super.appointments.review', $appointment['id']) }}">
                                        @csrf
                                        <input type="hidden" name="_active_tab" value="overview">
                                        <input type="hidden" name="status" value="Approved">
                                        <button class="btn-primary px-3 py-2 text-xs" type="submit">Approve Eligible</button>
                                    </form>
                                    <form method="post" action="{{ route('super.appointments.review', $appointment['id']) }}">
                                        @csrf
                                        <input type="hidden" name="_active_tab" value="overview">
                                        <input type="hidden" name="status" value="Deferred">
                                        <button class="btn-outline px-3 py-2 text-xs" type="submit">Defer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-stone-500">No appointments are waiting for review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <h2 class="section-title mt-7">Quick Actions</h2>
    <div class="mt-4 grid gap-3 md:grid-cols-4">
        <a class="btn-primary" data-turbo="false" href="{{ route('reports.download', 'admin') }}"><i data-lucide="file-text"></i> Generate Report</a>
        @foreach (['Trigger Audit', 'Global Broadcast', 'Access Review'] as $label)
            <form method="post" action="{{ $actionRoute }}">@csrf<input type="hidden" name="action" value="{{ $label }}"><button class="btn-outline w-full" type="submit">{{ $label }}</button></form>
        @endforeach
    </div>

@elseif ($section === 'reports')
    <div data-report-dashboard data-report-stats='@json($reportStats)' data-report-distribution='@json($reportDistribution)'>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <header>
                <h1 class="page-title">Analytics and Reports</h1>
                <p class="page-subtitle">Descriptive statistics for donor operations.</p>
            </header>
            <div class="flex flex-wrap items-center gap-3">
                <div class="report-filter-group" role="tablist" aria-label="Blood type report filter">
                    @foreach (['All','O+','O-','A+','A-','B+','B-','AB+','AB-'] as $type)
                        <button class="report-filter {{ $loop->first ? 'is-active' : '' }}" type="button" data-report-filter="{{ $type }}">{{ $type }}</button>
                    @endforeach
                </div>
                <a class="btn-outline" data-turbo="false" href="{{ route('reports.download', 'admin') }}"><i data-lucide="download"></i> Generate Report</a>
            </div>
        </div>

        <section class="grid gap-4 md:grid-cols-5">
            <article class="card p-6"><p class="eyebrow">Total Registered</p><p class="mt-5 text-3xl font-extrabold" data-report-value="registered">{{ number_format($initialReport['registered']) }}</p><p class="mt-5 text-stone-600" data-report-subtext="registered">{{ $initialReport['subtext'] }}</p></article>
            <article class="card p-6"><p class="eyebrow">Eligible Donors</p><p class="mt-5 text-3xl font-extrabold" data-report-value="eligible">{{ number_format($eligible) }}</p><p class="mt-5 text-stone-600" data-report-subtext="eligible">{{ $initialReport['ratios']['eligible'] }}% eligible</p></article>
            <article class="card p-6"><p class="eyebrow">Temp Deferred</p><p class="mt-5 text-3xl font-extrabold" data-report-value="deferred">{{ number_format($deferred) }}</p><p class="mt-5 text-stone-600" data-report-subtext="deferred">{{ $initialReport['ratios']['deferred'] }}% deferred</p></article>
            <article class="card p-6"><p class="eyebrow">Ineligible</p><p class="mt-5 text-3xl font-extrabold" data-report-value="ineligible">{{ number_format($initialReport['ineligible']) }}</p><p class="mt-5 text-stone-600" data-report-subtext="ineligible">{{ $initialReport['ratios']['ineligible'] }}% ineligible</p></article>
            <article class="card p-6 is-red metric-card"><p class="metric-label">Screened Month</p><p class="mt-5 text-3xl font-extrabold" data-report-value="screened">{{ number_format($initialReport['screened']) }}</p><p class="mt-5 text-white" data-report-subtext="screened">{{ $initialReport['screenedLabel'] }}</p></article>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-3">
            <article class="card chart-card">
                <h2 class="section-title">Type Distribution</h2>
                <div class="mt-12 grid place-items-center"><div class="donut" data-report-donut data-tooltip="All blood types: 100%"></div></div>
                <div class="legend mt-8" data-report-legend>@foreach ($reportDistribution as $segment)<span><span class="legend-dot" style="background: {{ $segment['color'] }}"></span> {{ $segment['type'] }}: {{ (int) $segment['share'] == $segment['share'] ? (int) $segment['share'] : $segment['share'] }}%</span>@endforeach</div>
            </article>
            <article class="card chart-card">
                <h2 class="section-title">Eligibility Ratio</h2>
                <div class="mt-10 space-y-5">
                    @foreach ([['eligible','Eligible',$initialReport['ratios']['eligible'],'#9a452a'],['deferred','Temp Deferred',$initialReport['ratios']['deferred'],'#775043'],['ineligible','Ineligible',$initialReport['ratios']['ineligible'],'#c40000']] as [$key, $label, $value, $color])
                        <div data-report-ratio="{{ $key }}"><div class="mb-2 flex justify-between font-bold"><span>{{ $label }}</span><span data-report-ratio-value>{{ $value }}%</span></div><div class="progress-line"><span data-report-ratio-bar style="width: {{ $value }}%; background: {{ $color }}"></span></div></div>
                    @endforeach
                </div>
            </article>
            <article class="card chart-card">
                <h2 class="section-title">Registration Trend</h2>
                <div class="bar-chart mt-8">@foreach ($initialReport['trend'] as $value)<div class="bar" data-report-trend-bar data-tooltip="{{ ['Mon','Tue','Wed','Thu','Fri','Sat','Today'][$loop->index] }} registered: {{ number_format($value) }}" style="height: {{ max(8, round(($value / max(1, max($initialReport['trend']))) * 100)) }}%"></div>@endforeach</div>
                <div class="bar-labels"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Today</span></div>
            </article>
        </section>

        @include('admin.sections.donor-table', ['donors' => $donorRows])
        <div class="alert mt-4" data-report-notice hidden></div>
    </div>

@elseif ($section === 'notifications')
    <section
        class="card grid overflow-hidden lg:grid-cols-[320px_1fr]"
        data-notification-center
        data-read-url-template="{{ $notificationReadUrlTemplate }}"
        data-read-all-url="{{ route('notifications.readAll') }}"
    >
        <aside class="border-r border-red-100">
            <div class="p-5">
                <div class="mb-4 flex items-start justify-between">
                    <h1 class="text-2xl font-extrabold leading-tight">Notification<br>Center</h1>
                    <button class="font-bold text-red-700" type="button" data-notification-mark-read>Mark All As Read</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="badge is-active" type="button" data-notification-filter="all">All</button>
                    <button class="badge" type="button" data-notification-filter="unread">Unread (<span data-unread-count>{{ $adminUnreadCount }}</span>)</button>
                    <button class="badge" type="button" data-notification-filter="matching">Matching Alerts</button>
                </div>
                <p class="notification-error" data-notification-error role="status" hidden></p>
            </div>
            @forelse ($notificationRows as $note)
                @php
                    $noteTag = $note['tag'] ?? $note['type'] ?? 'Alert';
                    $noteTime = $note['display_time'] ?? $note['time_label'] ?? $note['time'] ?? 'Unread';
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
                    class="notification-item border-t border-red-100 p-5"
                    data-notification-item
                    data-notification-id="{{ $note['id'] ?? '' }}"
                    data-note-read="{{ $isRead ? 'true' : 'false' }}"
                    data-note-filters="{{ implode(',', $filterTags) }}"
                    data-note-tag="{{ $noteTag }}"
                    data-note-title="{{ $note['title'] }}"
                    data-note-time="{{ $noteTime }}"
                    data-note-body="{{ $noteBody }}"
                    data-note-id="{{ $loop->first ? '#DN-8842-X' : '#DN-'.(8842 + $loop->index).'-X' }}"
                    data-note-status="{{ str_contains(strtolower($noteTag), 'matching') ? 'Verified' : 'Pending Review' }}"
                    role="button"
                    tabindex="0"
                >
                    <div class="mb-2 flex items-center justify-between"><span class="badge">{{ $noteTag }}</span><span class="text-xs text-stone-500" data-note-time>{{ $noteTime }}</span></div>
                    <h2 class="font-bold">{{ $note['title'] }}</h2>
                    <p class="mt-2 text-sm text-stone-600">{{ $noteBody }}</p>
                </article>
            @empty
                <div class="notification-empty border-t border-red-100">
                    <i data-lucide="bell-off"></i>
                    <h3>No notifications yet</h3>
                    <p>New alerts will appear here.</p>
                </div>
            @endforelse
            <div class="notification-empty border-t border-red-100" data-notification-empty hidden>
                <i data-lucide="bell-off"></i>
                <h3>No notifications found</h3>
                <p>This filter does not have any visible notifications.</p>
            </div>
        </aside>
        <article class="p-8" data-notification-detail>
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <span class="badge" data-note-detail-tag>Matching Alert</span>
                    <h1 class="mt-4 text-3xl font-extrabold" data-note-detail-title>O- Donor Match Available</h1>
                    <p class="mt-1 text-stone-500">Received: <span data-note-detail-time>Oct 24, 2023 - 14:32</span></p>
                </div>
                <div class="flex gap-2"><button class="btn-outline" type="button"><i data-lucide="download"></i></button><button class="btn-outline" type="button"><i data-lucide="alert-triangle"></i></button></div>
            </div>
            <div class="rounded-xl border border-red-100 bg-stone-50 p-6">
                <h2 class="font-bold">Donor Match Details</h2>
                <p class="mt-4 text-stone-600" data-note-detail-body>A compatible O- donor has been identified for potential donation scheduling in the South Sector clinic. The donor profile meets preliminary criteria.</p>
                <div class="mt-6 grid gap-4 rounded-lg bg-white p-5 md:grid-cols-2"><div><p class="eyebrow">Donor ID</p><p class="mt-2 font-bold" data-note-detail-id>#DN-8842-X</p></div><div><p class="eyebrow">Eligibility Status</p><p class="mt-2 font-bold" data-note-detail-status>Verified</p></div></div>
                <button class="btn-outline mt-6" type="button">View Full Profile</button>
            </div>
        </article>
    </section>

@elseif ($section === 'map')
    <header class="page-header">
        <h1 class="page-title">Donation Map</h1>
        <p class="page-subtitle">Live clinic and collection point coverage.</p>
    </header>
    <section class="map-frame admin-map-frame">
        <div
            class="admin-leaflet-map"
            data-admin-leaflet-map
            data-admin-leaflet-centers="{{ e(json_encode($mapCenters->values()->all())) }}"
            aria-label="Interactive OpenStreetMap view of donation centers"
        ></div>
        <div class="admin-map-selection" data-admin-map-selection>
            <div>
                <p class="eyebrow">Selected Center</p>
                <h2 data-admin-map-name>{{ $mapCenters->first()['name'] }}</h2>
                <p data-admin-map-address>{{ $mapCenters->first()['address'] }}</p>
            </div>
            <span class="badge" data-admin-map-type>{{ $mapCenters->first()['type'] }}</span>
        </div>
        <div class="static-map" hidden role="img" aria-label="OpenStreetMap view centered on Santa Rosa, Laguna donation coverage">
            <div class="static-map-tiles">
                @foreach ([[1711,941],[1712,941],[1713,941],[1711,942],[1712,942],[1713,942],[1711,943],[1712,943],[1713,943]] as [$x, $y])
                    <img src="https://tile.openstreetmap.org/11/{{ $x }}/{{ $y }}.png" alt="" loading="lazy" referrerpolicy="no-referrer">
                @endforeach
            </div>
            <div class="static-map-marker">
                <span></span>
                <strong>PRC Laguna Chapter</strong>
            </div>
            <div class="static-map-zoom" aria-hidden="true"><span>+</span><span>-</span></div>
            <a class="static-map-attribution" href="https://www.openstreetmap.org/?mlat=14.312&mlon=121.111#map=11/14.312/121.111" target="_blank" rel="noopener">© OpenStreetMap contributors</a>
        </div>
    </section>

@elseif ($section === 'donor-records')
    <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
        <header>
            <h1 class="page-title">Donor Records</h1>
            <p class="page-subtitle">Manage and review blood donor profiles and history.</p>
        </header>
        <button class="btn-primary" data-modal-open="#new-donor-modal" type="button"><i data-lucide="plus"></i> New Donor</button>
    </div>
    <div id="new-donor-modal" class="modal-shell" data-modal hidden>
        <div class="modal-backdrop" data-modal-backdrop></div>
        <form class="modal-panel modal-panel-lg modal-panel-form" method="post" action="{{ route('admin.donors.store') }}">
            @csrf
            <div class="modal-header">
                <h2>Donor Registration</h2>
                <button class="modal-close" type="button" data-modal-close aria-label="Close donor registration">x</button>
            </div>
            <div class="modal-body-scroll">
                <div class="form-grid">
                    <div><label class="label">Full Name</label><input class="input" name="full_name" placeholder="Juan Dela Cruz" required></div>
                    <div><label class="label">Age</label><input class="input" name="age" type="number" min="18" placeholder="Minimum 18 years"></div>
                    <div><label class="label">Blood Type</label><select class="select" name="blood_type" required><option value="">Select blood type</option>@foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
                    <div><label class="label">Weight</label><input class="input" name="weight" type="number" min="40" placeholder="Minimum 50kg"></div>
                    <div><label class="label">Contact Number</label><input class="input" name="contact" placeholder="+63 912 345 6789" required></div>
                    <div><label class="label">Last Donation Date</label><input class="input" name="last_donation_label" type="date"></div>
                </div>
                <div class="mt-4"><label class="label">Residential Address</label><textarea class="textarea" name="address" placeholder="Enter full mailing address"></textarea></div>
                <div class="mt-5">
                    <p class="label">Medical History</p>
                    <div class="medical-grid">
                        <label><input type="checkbox" name="medical_flags[]" value="Diabetes"> Diabetes</label>
                        <label><input type="checkbox" name="medical_flags[]" value="Hypertension"> Hypertension</label>
                        <label><input type="checkbox" name="medical_risk" value="1"> Recent Surgery</label>
                        <label><input type="checkbox" name="medical_flags[]" value="Allergies"> Allergies</label>
                        <label><input type="checkbox" name="medical_flags[]" value="Anemia"> Anemia</label>
                        <label><input type="checkbox" name="medical_flags[]" value="Infectious Disease"> Infectious Disease</label>
                    </div>
                </div>
                <div class="mt-4"><label class="label">Other Medical Details</label><textarea class="textarea" name="medical_notes" placeholder="Describe conditions or medications..."></textarea></div>
                <label class="mt-5 flex items-center gap-3 text-sm text-stone-700"><input type="checkbox" required> I certify that the information provided is true and accurate.</label>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" type="button" data-modal-close>Cancel</button>
                <button class="btn-primary" type="submit">Submit Registration</button>
            </div>
        </form>
    </div>
    <section class="card table-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-red-100 p-4">
            <div class="search-pill block"><i data-lucide="search"></i><input class="!w-[240px]" placeholder="Search donors by name, ID..."></div>
            <div class="flex gap-2"><button class="btn-outline" type="button">Blood Type (All)</button><button class="btn-outline" type="button">Status (All)</button></div>
        </div>
        @include('admin.sections.donor-table', ['donors' => $donorRows, 'embedded' => true])
        <div class="flex items-center justify-between border-t border-red-100 p-4 text-sm"><span>Showing 1 to {{ count($donorRows) }} of 1,248 donors</span><span class="flex gap-2"><button class="btn-outline px-3 py-2" type="button">‹</button><button class="btn-outline bg-red-100 px-3 py-2" type="button">1</button><button class="btn-outline px-3 py-2" type="button">2</button><button class="btn-outline px-3 py-2" type="button">›</button></span></div>
    </section>

@elseif ($section === 'inventory')
    <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
        <header>
            <h1 class="page-title">{{ $isSuper ? 'Welcome, Administrator' : 'Blood Inventory' }}</h1>
            <p class="page-subtitle">{{ $isSuper ? 'Here is the current state of the regional blood inventory.' : 'Live stock monitoring and management.' }}</p>
        </header>
        <div class="flex gap-3"><a class="btn-outline" data-turbo="false" href="{{ route('reports.download', 'inventory') }}"><i data-lucide="download"></i> Generate Report</a><button class="btn-primary" data-modal-open="#add-entry-modal" type="button"><i data-lucide="plus"></i> Add Entry</button></div>
    </div>
    <div id="add-entry-modal" class="modal-shell" data-modal hidden>
        <div class="modal-backdrop" data-modal-backdrop></div>
        <form class="modal-panel modal-panel-lg modal-panel-form" method="post" action="{{ route('admin.inventory.store') }}">
            @csrf
            <div class="modal-header">
                <h2>Add Walk-in Donation</h2>
                <button class="modal-close" type="button" data-modal-close aria-label="Close walk-in donation form">x</button>
            </div>
            <div class="modal-body-scroll">
                <div class="form-grid">
                    <div><label class="label">Donor Name</label><input class="input" name="donor_name" placeholder="Walk-in donor name" required></div>
                    <div><label class="label">Donor ID or Walk-in/New Donor</label><input class="input" name="donor_code" value="Walk-in/New Donor"></div>
                    <div><label class="label">Contact Number</label><input class="input" name="contact_number" placeholder="+63 912 345 6789"></div>
                    <div><label class="label">Blood Type</label><select class="select" name="blood_type" required><option value="">Select blood type</option>@foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
                    <div><label class="label">Units Donated</label><input class="input" name="units" type="number" min="1" value="1"></div>
                    <div><label class="label">Donation Type / Component Type</label><select class="select" name="component_type" required><option value="">Select donation type / component type</option><option>Whole Blood</option><option>Platelets</option><option>Plasma</option></select></div>
                    <div><label class="label">Collection Date</label><input class="input" name="collection_date" type="date" required></div>
                    <div><label class="label">Collection Time</label><input class="input" name="collection_time" type="time" required></div>
                    <div><label class="label">Facility / Collection Site</label><input class="input" name="facility" value="PRC Laguna Chapter - Santa Rosa Branch" required></div>
                    <div><label class="label">Staff/Admin Name</label><input class="input" name="staff_name" value="{{ $profile['full_name'] ?? 'Alex Rivera' }}" required></div>
                    <div><label class="label">Screening Status</label><select class="select" name="screening_status"><option>Passed</option><option>Failed</option></select></div>
                    <div><label class="label">Eligibility Status</label><select class="select" name="eligibility_status"><option>Eligible</option><option>Deferred</option><option>Ineligible</option></select></div>
                </div>
                <div class="mt-4"><label class="label">Notes</label><textarea class="textarea" name="notes" placeholder="Donation notes..."></textarea></div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" type="button" data-modal-close>Cancel</button>
                <button class="btn-primary" type="submit">Save Entry</button>
            </div>
        </form>
    </div>

    <section class="metric-grid">
        <article class="card metric-card is-red"><div class="metric-top"><p class="metric-label">Total Units</p><i data-lucide="database"></i></div><p class="metric-value">{{ number_format($totalUnits ?: 56) }}</p><p class="metric-subtext">+48 since yesterday</p></article>
        <article class="card metric-card"><div class="metric-top"><p class="metric-label">Critical Alerts</p><i data-lucide="alert-triangle" class="red"></i></div><p class="metric-value">3 Groups</p><p class="metric-subtext">Below safety threshold</p></article>
        <article class="card metric-card"><div class="metric-top"><p class="metric-label">Recent Collections</p><i data-lucide="history" class="red"></i></div><p class="metric-value">124 Units</p><p class="metric-subtext">In the last 24 hours</p></article>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($stockRows as $stock)
            <article class="card stock-card">
                <div class="mb-6 flex items-start justify-between"><h2 class="stock-type">{{ $stock['type'] }}</h2><span class="badge {{ $stock['status'] === 'Critical' ? 'is-active' : ($stock['status'] === 'Low' ? 'status-warning' : '') }}">{{ strtoupper($stock['status']) }}</span></div>
                <div class="mb-4 flex items-end justify-between gap-3"><p><span class="stock-units">{{ $stock['units'] }}</span> <span class="text-stone-600">units</span></p><span class="text-xs text-stone-500">Goal: {{ $stock['goal'] }}</span></div>
                <div class="stock-progress"><span style="width: {{ min(100, round(($stock['units'] / $stock['goal']) * 100)) }}%"></span></div>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-4 xl:grid-cols-[1fr_440px]">
        <article class="card p-5">
            <div class="mb-4 flex justify-between"><h2 class="section-title">Stock Movement (7 Days)</h2><p class="font-bold text-red-700">• Inflow &nbsp; • Outflow</p></div>
            <div class="bar-chart">@foreach ([['Mon',58,42],['Tue',42,58],['Wed',75,36],['Thu',36,68],['Fri',68,44],['Sat',44,56],['Today',92,72]] as [$day, $inflow, $outflow])<div class="bar-group"><span class="bar is-soft" data-tooltip="{{ $day }} inflow: {{ $inflow }} units" style="height: {{ $inflow }}%"></span><span class="bar {{ $loop->last ? 'is-muted' : 'is-soft' }}" data-tooltip="{{ $day }} outflow: {{ $outflow }} units" style="height: {{ max(28, $outflow) }}%"></span></div>@endforeach</div>
            <div class="bar-labels"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Today</span></div>
        </article>
        <aside class="card p-5">
            <h2 class="section-title flex items-center gap-2"><i data-lucide="activity" class="red"></i> Storage Status</h2>
            <div class="mt-5 space-y-3">@foreach ([['Alpha (Whole Blood)','4.2°C','Stable','Target: 2-6°C'],['Gamma (Platelets)','24.5°C','High Alert','Target: 20-24°C'],['Beta (Plasma)','-30.0°C','Stable','Target: <-25°C']] as [$name, $temp, $status, $target])<div class="rounded-lg bg-stone-50 p-4"><div class="flex justify-between font-bold"><span>{{ $name }}</span><span class="text-red-700">{{ $temp }}</span></div><div class="mt-2 flex justify-between text-sm"><span class="badge {{ $status === 'High Alert' ? 'is-active' : '' }}">{{ strtoupper($status) }}</span><span>{{ $target }}</span></div></div>@endforeach</div>
        </aside>
    </section>

    <section class="mt-7 inventory-workspace" data-inventory-workspace>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="section-title text-2xl">Blood Stock Detailed View</h2>
                <p class="text-sm text-stone-600">Manage and monitor individual blood component units.</p>
            </div>
            <div class="inventory-toolbar">
                <label class="search-pill block">
                    <i data-lucide="search"></i>
                    <input class="!w-[240px]" data-inventory-search placeholder="Search inventory..." aria-label="Search inventory">
                </label>
                <div class="inventory-filter-wrap">
                    <button class="btn-primary" type="button" data-inventory-filter-toggle aria-expanded="false" aria-controls="inventory-filter-menu">
                        <i data-lucide="sliders-horizontal"></i> Filter
                    </button>
                    <div id="inventory-filter-menu" class="inventory-filter-menu" data-inventory-filter-menu hidden>
                        <label class="label" for="inventory-blood-filter">Blood Type</label>
                        <select id="inventory-blood-filter" class="select" data-inventory-blood-filter>
                            <option value="all">All Blood Types</option>
                            @foreach ($bloodTypes as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach
                        </select>
                        <label class="label mt-4" for="inventory-status-filter">Availability</label>
                        <select id="inventory-status-filter" class="select" data-inventory-status-filter>
                            <option value="all">All Statuses</option>
                            @foreach ($inventoryStatuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach
                        </select>
                        <label class="label mt-4" for="inventory-sort-filter">Sort Records</label>
                        <select id="inventory-sort-filter" class="select" data-inventory-sort-filter>
                            <option value="date-desc">Collection Date: Newest</option>
                            <option value="date-asc">Collection Date: Oldest</option>
                            <option value="alpha-asc">A-Z</option>
                            <option value="alpha-desc">Z-A</option>
                        </select>
                        <button class="btn-outline mt-4 w-full" type="button" data-inventory-filter-reset>Reset Filters</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card table-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Blood Type</th><th>Component</th><th>Unit ID</th><th>Collection Date</th><th>Status</th></tr></thead>
                    <tbody data-inventory-table-body>
                        @foreach ($inventoryRows as $row)
                            @php
                                $collectionTimestamp = !empty($row['collection_date']) ? strtotime((string) $row['collection_date']) : 0;
                                $collectionLabel = $collectionTimestamp ? date('M d, Y', $collectionTimestamp) : 'Not provided';
                            @endphp
                            <tr
                                data-inventory-row
                                data-inventory-blood="{{ $row['blood_type'] }}"
                                data-inventory-status="{{ $row['status'] }}"
                                data-inventory-date="{{ $collectionTimestamp }}"
                                data-inventory-sort="{{ strtolower($row['blood_type'].' '.$row['component_type'].' '.$row['unit_code']) }}"
                            >
                                <td class="font-bold">{{ $row['blood_type'] }}</td>
                                <td>{{ $row['component_type'] }}</td>
                                <td>{{ $row['unit_code'] }}</td>
                                <td>{{ $collectionLabel }}</td>
                                <td><span class="badge {{ strtolower($row['status']) === 'available' ? '' : 'status-warning' }}">{{ $row['status'] }}</span></td>
                            </tr>
                        @endforeach
                        <tr data-inventory-empty hidden><td colspan="5">No inventory units match the current filters.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="mt-7" hidden>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3"><div><h2 class="section-title text-2xl">Blood Stock Detailed View</h2><p class="text-sm text-stone-600">Manage and monitor individual blood component units.</p></div><div class="flex gap-3"><div class="search-pill block"><i data-lucide="search"></i><input class="!w-[240px]" placeholder="Search inventory..."></div><button class="btn-primary" type="button"><i data-lucide="filter"></i> Filter</button></div></div>
        <div class="card table-card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Blood Type</th><th>Component</th><th>Unit ID</th><th>Collection Date</th><th>Status</th></tr></thead><tbody><tr><td class="font-bold">O-Negative</td><td>Whole Blood</td><td>#88219-BC</td><td>Oct 12, 2024</td><td><span class="badge">Expiring Soon</span></td></tr><tr><td class="font-bold">A-Positive</td><td>Platelets</td><td>#44102-XY</td><td>Oct 20, 2024</td><td><span class="badge">Available</span></td></tr><tr><td class="font-bold">B-Positive</td><td>Plasma</td><td>#11293-ZZ</td><td>Oct 18, 2024</td><td><span class="badge">Available</span></td></tr></tbody></table></div></div>
    </section>

@elseif ($section === 'campaigns')
    <div class="mb-7 flex flex-wrap items-start justify-between gap-4"><header><h1 class="page-title">Campaigns</h1><p class="page-subtitle">Regional collection drives and community outreach.</p></header><button class="btn-primary" data-campaign-create data-modal-open="#campaign-modal" type="button"><i data-lucide="plus"></i> Post Campaign</button></div>
    <div id="campaign-modal" class="modal-shell" data-modal hidden>
        <div class="modal-backdrop" data-modal-backdrop></div>
        <form class="modal-panel" method="post" action="{{ route('admin.campaigns.store') }}" data-campaign-form>
            @csrf
            <div class="modal-header">
                <h2 data-campaign-form-title>Post Campaign</h2>
                <button class="modal-close" type="button" data-modal-close aria-label="Close campaign form">x</button>
            </div>
            <div class="form-grid">
                <div><label class="label">Campaign Title</label><input class="input" name="title" data-campaign-field="title" placeholder="Community Heroes Week" required></div>
                <div><label class="label">Status</label><select class="select" name="status" data-campaign-field="status"><option>Upcoming</option><option>Open</option><option>Urgent</option><option>Planning</option></select></div>
                <div><label class="label">Date Range</label><input class="input" name="date_range" data-campaign-field="dateRange" placeholder="August 01 - August 15" required></div>
                <div><label class="label">Locations</label><input class="input" name="locations" data-campaign-field="locations" placeholder="Santa Rosa, Laguna" required></div>
            </div>
            <div class="mt-4"><label class="label">Image URL</label><input class="input" name="image_url" data-campaign-field="imageUrl" type="url" value="{{ $fallbackCampaigns[0]['image_url'] }}" placeholder="https://example.com/campaign.jpg" required></div>
            <div class="mt-4"><label class="label">Description</label><textarea class="textarea" name="description" data-campaign-field="description" placeholder="Describe the campaign..." required></textarea></div>
            <div class="campaign-edit-note mt-4" data-campaign-edit-note hidden>
                Editing existing campaigns is ready in the UI, but no safe update route exists yet. Save is disabled so an existing campaign is not accidentally duplicated.
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" type="button" data-modal-close>Cancel</button>
                <button class="btn-primary" type="submit" data-campaign-submit>Post Campaign</button>
            </div>
        </form>
    </div>
    <section class="campaign-grid">
        @foreach ($campaignRows as $campaign)
            @php
                $campaignId = 'campaign-detail-'.md5(($campaign['id'] ?? $campaign['title'] ?? $loop->index).$loop->index);
            @endphp
            <article
                class="card campaign-card"
                role="button"
                tabindex="0"
                data-campaign-open="#{{ $campaignId }}"
            >
                <div class="relative campaign-card-media"><img src="{{ $campaign['image_url'] }}" alt="{{ $campaign['title'] }}"><span class="badge is-active absolute left-4 top-4">{{ $campaign['status'] }}</span></div>
                <div class="p-6"><h2 class="section-title">{{ $campaign['title'] }}</h2><p class="mt-2 text-sm leading-6 text-stone-600">{{ $campaign['description'] }}</p><p class="mt-5 text-xs font-bold text-stone-500">{{ $campaign['date_range'] }} - {{ $campaign['locations'] }}</p></div>
            </article>
            <div id="{{ $campaignId }}" class="modal-shell" data-modal hidden>
                <div class="modal-backdrop" data-modal-backdrop></div>
                <section class="modal-panel campaign-detail-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $campaignId }}-title">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow">Campaign Detail</p>
                            <h2 id="{{ $campaignId }}-title">{{ $campaign['title'] }}</h2>
                        </div>
                        <button class="modal-close" type="button" data-modal-close aria-label="Close campaign detail">x</button>
                    </div>
                    <img class="campaign-detail-image" src="{{ $campaign['image_url'] }}" alt="{{ $campaign['title'] }}">
                    <div class="detail-grid mt-4">
                        <div><span>Status</span><strong>{{ $campaign['status'] }}</strong></div>
                        <div><span>Date Range</span><strong>{{ $campaign['date_range'] }}</strong></div>
                        <div><span>Locations</span><strong>{{ $campaign['locations'] }}</strong></div>
                    </div>
                    <section class="detail-section mt-4">
                        <h3>Description</h3>
                        <p class="text-sm leading-6 text-stone-600">{{ $campaign['description'] }}</p>
                    </section>
                    <div class="modal-actions">
                        <button class="btn-secondary" type="button" data-modal-close>Close</button>
                        <button
                            class="btn-primary"
                            type="button"
                            data-campaign-edit
                            data-campaign-title="{{ $campaign['title'] }}"
                            data-campaign-status="{{ $campaign['status'] }}"
                            data-campaign-date-range="{{ $campaign['date_range'] }}"
                            data-campaign-locations="{{ $campaign['locations'] }}"
                            data-campaign-image-url="{{ $campaign['image_url'] }}"
                            data-campaign-description="{{ $campaign['description'] }}"
                        >Edit</button>
                    </div>
                </section>
            </div>
        @endforeach
    </section>

@elseif ($section === 'profile')
    @include('admin.sections.staff-profile')

@elseif ($section === 'security')
    <div class="mb-7 flex flex-wrap items-start justify-between gap-4"><header><h1 class="page-title">Audit Logs &amp; Security</h1><p class="page-subtitle">Monitor system integrity and user activity.</p></header><div class="flex gap-3"><div class="search-pill block"><i data-lucide="search"></i><input class="!w-[220px]" placeholder="Search logs..."></div><a class="btn-primary" data-turbo="false" href="{{ route('reports.download', 'security') }}"><i data-lucide="download"></i> Export Report</a></div></div>
    <section class="metric-grid"><article class="card p-5"><p class="eyebrow">System Threat Level</p><p class="mt-3 text-2xl font-extrabold"><span class="text-red-700">•</span> Low</p></article><article class="card p-5"><p class="eyebrow">Firewall Status</p><p class="mt-3 text-2xl font-extrabold">Active</p><p class="mt-3 text-sm text-stone-600">Last block: 12 mins ago</p></article><article class="card p-5"><p class="eyebrow">Active Sessions</p><p class="mt-3 text-2xl font-extrabold">24</p><p class="mt-3 text-sm text-stone-600">4 Admins, 20 Staff</p></article><article class="card p-5"><p class="eyebrow">Last Security Scan</p><p class="mt-3 text-2xl font-extrabold">02:00 AM</p><p class="mt-3 text-sm font-bold text-red-700">0 vulnerabilities found</p></article></section>
    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_415px]"><article class="card table-card"><div class="flex items-center justify-between border-b border-red-100 p-4"><h2 class="font-bold uppercase tracking-wide">Recent Activity Log</h2><a class="text-red-700" href="#">View Detailed Logs</a></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Timestamp</th><th>User & Role</th><th>Action</th><th>Location / IP</th><th>Status</th></tr></thead><tbody>@foreach ($auditRows as $row)<tr class="{{ strtolower($row['status']) === 'failed' ? 'text-red-700' : '' }}"><td>{{ $row['time'] }}</td><td><strong>{{ $row['user'] }}</strong><br><span>{{ $row['role'] }}</span></td><td>{{ $row['action'] }}</td><td>{{ $row['location'] }}</td><td><span class="badge {{ strtolower($row['status']) === 'failed' ? 'is-active' : '' }}">{{ strtoupper($row['status']) }}</span></td></tr>@endforeach</tbody></table></div></article><aside class="card p-5"><h2 class="font-bold uppercase">Quick Actions</h2><div class="mt-4 space-y-2"><form method="post" action="{{ $actionRoute }}">@csrf<input type="hidden" name="action" value="Force Password Reset"><button class="btn-outline w-full justify-start" type="submit"><i data-lucide="lock"></i> Force Password Reset</button></form><a class="btn-outline w-full justify-start" data-turbo="false" href="{{ route('reports.download', 'security') }}"><i data-lucide="download"></i> Download Audit Report</a><form method="post" action="{{ $actionRoute }}">@csrf<input type="hidden" name="action" value="Global logout triggered for all active sessions."><button class="btn-primary w-full justify-start" type="submit"><i data-lucide="log-out"></i> Trigger Global Logout</button></form></div></aside></section>
@endif
@endsection
