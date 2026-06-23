@php
    $tableDonors = collect($donors ?? [])->values();
    $embedded = $embedded ?? false;
@endphp

@unless ($embedded)
<section class="card table-card mt-6">
    <div class="flex items-center justify-between border-b border-red-100 p-5">
        <h3 class="section-title">Recent Donor Records</h3>
        <button class="text-sm font-bold text-red-700" type="button">View Details</button>
    </div>
@endunless

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Donor Name</th>
                <th>Blood Type</th>
                <th>Contact Info</th>
                <th>Last Donation</th>
                <th>Status</th>
                <th>Units</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableDonors as $donor)
                @php
                    $name = $donor['full_name'] ?? $donor['name'] ?? 'Unnamed Donor';
                    $initials = collect(explode(' ', $name))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                    $status = $donor['eligibility_status'] ?? $donor['status'] ?? 'Eligible';
                @endphp
                <tr data-donor-blood="{{ $donor['blood_type'] ?? 'O-' }}">
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="avatar">{{ $initials ?: 'RC' }}</span>
                            <div>
                                <p class="font-bold">{{ $name }}</p>
                                <p class="text-xs text-stone-500">ID: {{ $donor['donor_code'] ?? $donor['id'] ?? 'D-00000' }}</p>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge">{{ $donor['blood_type'] ?? 'O-' }}</span></td>
                    <td class="text-stone-600">
                        {{ $donor['email'] ?? $donor['contact'] ?? 'No contact' }}
                        @if (!empty($donor['contact']) && !empty($donor['email']))
                            <br>{{ $donor['contact'] }}
                        @endif
                    </td>
                    <td>{{ $donor['last_donation_label'] ?? $donor['lastDonation'] ?? 'Never' }}</td>
                    <td><span class="badge {{ $status === 'Deferred' ? 'status-warning' : '' }}">{{ $status }}</span></td>
                    <td class="font-bold">{{ $donor['total_units'] ?? $donor['units'] ?? 0 }}</td>
                    <td><button class="font-bold text-red-700" data-toggle="#view-{{ md5(($donor['donor_code'] ?? $donor['id'] ?? $loop->index).$loop->index) }}" type="button">View</button></td>
                </tr>
                <tr id="view-{{ md5(($donor['donor_code'] ?? $donor['id'] ?? $loop->index).$loop->index) }}" data-donor-blood="{{ $donor['blood_type'] ?? 'O-' }}" data-donor-detail-row hidden>
                    <td colspan="7">
                        <pre class="whitespace-pre-wrap rounded-lg bg-stone-50 p-4 text-xs">{{ json_encode(['registration' => $donor['registration_details'] ?? [], 'screening' => $donor['screening_details'] ?? []], JSON_PRETTY_PRINT) }}</pre>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No donor records yet.</td></tr>
            @endforelse
            <tr data-report-empty hidden><td colspan="7">No donor records match this blood type yet.</td></tr>
        </tbody>
    </table>
</div>

@unless ($embedded)
</section>
@endunless
