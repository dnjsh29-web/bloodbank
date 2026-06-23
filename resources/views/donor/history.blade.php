@extends('layouts.portal', ['title' => 'History', 'heading' => 'History', 'portal' => 'donor', 'active' => 'history'])

@section('portal')
@php
    $rows = collect($appointments)->map(fn ($appointment) => [
        $appointment['scheduled_date'] ?? 'Today',
        $appointment['center_name'] ?? 'PRC Laguna Chapter - Santa Rosa Branch',
        $appointment['service_type'] ?? 'Whole Blood',
        $appointment['status'] ?? 'Confirmed',
    ])->values()->all();
@endphp

<header class="page-header">
    <h2 class="page-title">Donation History</h2>
    <p class="page-subtitle">Track your contribution journey and impact over time.</p>
</header>

<section class="card table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Date</th><th>Facility</th><th>Type</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse ($rows as [$date, $facility, $type, $status])
                    <tr>
                        <td>{{ $date }}</td>
                        <td>{{ $facility }}</td>
                        <td>{{ $type }}</td>
                        <td><span class="badge">{{ $status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-stone-500">No donation history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
