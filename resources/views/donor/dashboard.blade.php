@extends('layouts.portal', ['title' => 'Donor Dashboard', 'heading' => 'Dashboard', 'portal' => 'donor', 'active' => 'dashboard'])

@section('portal')
@php
    $profile = $profile ?? [];
    $profileEmail = strtolower((string) ($profile['email'] ?? ''));
    $profileName = trim((string) ($profile['full_name'] ?? ''));
    $demoNames = ['juan', 'juan dela cruz', 'donor'];
    $firstName = $profileName !== '' && ! in_array(strtolower($profileName), $demoNames, true)
        ? explode(' ', $profileName)[0]
        : ($profileEmail !== '' ? explode('@', $profileEmail)[0] : 'Donor');

    $appointmentRows = collect($appointments ?? []);
    $approvedStatuses = ['approved', 'completed'];
    $pendingStatuses = ['confirmed', 'pending', 'booked', 'submitted'];
    $approvedAppointments = $appointmentRows
        ->filter(fn ($appointment) => in_array(strtolower((string) ($appointment['status'] ?? '')), $approvedStatuses, true)
            && ! str_contains(strtolower((string) ($appointment['service_type'] ?? '')), 'request'))
        ->values();
    $nextAppointment = $appointmentRows
        ->first(fn ($appointment) => in_array(strtolower((string) ($appointment['status'] ?? '')), $pendingStatuses, true));

    $ownDonorUnits = collect($donors ?? [])
        ->filter(fn ($donor) => ($donor['profile_id'] ?? null) === ($profile['id'] ?? null)
            || ($profileEmail !== '' && strtolower((string) ($donor['email'] ?? '')) === $profileEmail))
        ->filter(fn ($donor) => strtolower((string) ($donor['eligibility_status'] ?? '')) === 'eligible')
        ->sum(fn ($donor) => (int) ($donor['total_units'] ?? 0));

    $approvedCount = max($approvedAppointments->count(), $ownDonorUnits);
    $livesSaved = $approvedCount * 3;
    $latestApproved = $approvedAppointments->first();
    $latestAnswers = is_array($latestApproved['eligibility_answers'] ?? null) ? $latestApproved['eligibility_answers'] : [];

    $nextEligibleAt = null;
    if ($latestApproved) {
        $dateSource = $latestApproved['scheduled_at'] ?? $latestApproved['scheduled_date'] ?? null;
        if ($dateSource) {
            try {
                $nextEligibleAt = \Carbon\Carbon::parse($dateSource)->addDays(90);
            } catch (\Throwable) {
                $nextEligibleAt = null;
            }
        }
    }

    $daysRemaining = $nextEligibleAt ? max(0, now()->startOfDay()->diffInDays($nextEligibleAt->copy()->startOfDay(), false)) : null;
@endphp

<div>
    <header class="page-header">
        <h2 class="page-title">Welcome back, {{ $firstName }}</h2>
        <p class="page-subtitle">Here is your donation overview and health metrics.</p>
    </header>

    <section class="metric-grid">
        <article class="card metric-card is-red">
            <div class="metric-top">
                <p class="metric-label">Total Lives Saved</p>
                <i data-lucide="heart-handshake"></i>
            </div>
            <p class="metric-value">{{ $livesSaved }}</p>
        </article>
        <article class="card metric-card">
            <div class="metric-top">
                <p class="metric-label">Next Eligibility</p>
                <i data-lucide="calendar" class="red"></i>
            </div>
            <p class="metric-value">{{ $nextEligibleAt ? $nextEligibleAt->format('M j') : 'Not set' }}</p>
            <p class="metric-subtext">{{ $daysRemaining === null ? 'After an approved donation' : "{$daysRemaining} days remaining" }}</p>
        </article>
        <article class="card metric-card">
            <div class="metric-top">
                <p class="metric-label">Total Donations</p>
                <i data-lucide="check-circle-2" class="red"></i>
            </div>
            <p class="metric-value">{{ $approvedCount }} Units</p>
            <p class="metric-subtext">{{ $approvedCount > 0 ? 'Status: Eligible' : 'No approved donations yet' }}</p>
        </article>
    </section>

    <div class="mt-6 split-grid">
        <div class="space-y-6">
            <section class="card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="section-title">Upcoming Appointment</h2>
                    <a class="text-sm font-bold text-red-700" href="{{ route('donor.shell') }}#schedule" data-portal-tab="schedule" data-panel-url="{{ route('donor.panel', 'schedule') }}" data-title="Schedule">Book New</a>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-stone-50 p-4">
                    <div class="flex items-center gap-4">
                        <span class="grid size-12 place-items-center rounded-lg bg-red-700 text-white">
                            <i data-lucide="droplet"></i>
                        </span>
                        <div>
                            <p class="font-bold">{{ $nextAppointment['service_type'] ?? 'No appointment booked' }}</p>
                            <p class="text-sm text-stone-600">
                                {{ $nextAppointment ? (($nextAppointment['scheduled_date'] ?? '').' - '.($nextAppointment['scheduled_time'] ?? '')) : 'Choose a donation slot when ready.' }}
                            </p>
                        </div>
                    </div>
                    @if ($nextAppointment)
                        <span class="badge">{{ $nextAppointment['status'] }}</span>
                    @endif
                </div>
            </section>

            <section class="card p-6">
                <h2 class="section-title mb-4">Health Overview</h2>
                @if ($latestApproved)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg bg-stone-50 p-4 text-center text-sm font-bold">Hemoglobin {{ $latestAnswers['hemoglobin'] ?? 'Not recorded' }}{{ isset($latestAnswers['hemoglobin']) ? ' g/dL' : '' }}</div>
                        <div class="rounded-lg bg-stone-50 p-4 text-center text-sm font-bold">Blood Pressure {{ $latestAnswers['blood_pressure'] ?? 'Not recorded' }}</div>
                    </div>
                @else
                    <div class="rounded-lg bg-stone-50 p-4 text-sm font-semibold text-stone-600">
                        Health metrics will appear after staff approve your donation screening.
                    </div>
                @endif
            </section>
        </div>

        <section class="card p-6">
            <h3 class="mb-5 flex items-center gap-2 text-xl font-bold text-red-700">
                <i data-lucide="activity"></i>
                Recent Activity
            </h3>
            <div class="space-y-4 text-sm">
                @if ($latestApproved)
                    <div class="flex gap-3">
                        <i data-lucide="check-circle-2" class="mt-0.5 text-red-700"></i>
                        <div>
                            <p class="font-bold">Eligibility Approved</p>
                            <p class="text-stone-600">Your reviewed donation has been approved.</p>
                        </div>
                    </div>
                @endif
                <div class="flex gap-3">
                    <i data-lucide="{{ $nextAppointment ? 'check-circle-2' : 'calendar-plus' }}" class="mt-0.5 text-red-700"></i>
                    <div>
                        <p class="font-bold">{{ $nextAppointment ? 'Appointment Booked' : 'Ready to Schedule' }}</p>
                        <p class="text-stone-600">{{ $nextAppointment ? 'Your appointment is waiting for staff review.' : 'No upcoming appointment yet.' }}</p>
                    </div>
                </div>
            </div>
            <a class="btn-secondary mt-10 w-full" href="{{ route('donor.shell') }}#history" data-portal-tab="history" data-panel-url="{{ route('donor.panel', 'history') }}" data-title="History">View Full History</a>
        </section>
    </div>
</div>
@endsection
