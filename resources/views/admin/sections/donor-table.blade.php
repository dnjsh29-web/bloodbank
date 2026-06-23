@php
    $tableDonors = collect($donors ?? [])->values();
    $embedded = $embedded ?? false;
    $detailScope = $embedded ? 'records' : 'reports';
    $cleanValue = function ($value) {
        if (is_array($value)) {
            $flattened = collect($value)->flatten()->filter(fn ($item) => filled($item))->implode(', ');
            return $flattened !== '' ? $flattened : 'Not provided';
        }

        return filled($value) ? (string) $value : 'Not provided';
    };
    $detailRows = [];
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
                    $detailId = 'donor-detail-'.$detailScope.'-'.md5(($donor['donor_code'] ?? $donor['id'] ?? $loop->index).$loop->index);
                    $registration = is_array($donor['registration_details'] ?? null) ? $donor['registration_details'] : [];
                    $screening = is_array($donor['screening_details'] ?? null) ? $donor['screening_details'] : [];
                    $formPayload = is_array($donor['form_payload'] ?? null) ? $donor['form_payload'] : [];
                    $formSummary = $formPayload['donor_form'] ?? $formPayload['blood_request_form'] ?? $formPayload;
                    $detailRows[] = compact('donor', 'name', 'initials', 'status', 'detailId', 'registration', 'screening', 'formSummary');
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
                    <td><button class="font-bold text-red-700" data-modal-open="#{{ $detailId }}" type="button">View</button></td>
                </tr>
            @empty
                <tr><td colspan="7">No donor records yet.</td></tr>
            @endforelse
            <tr data-report-empty hidden><td colspan="7">No donor records match this blood type yet.</td></tr>
        </tbody>
    </table>
</div>

@foreach ($detailRows as $detail)
    @php
        $donor = $detail['donor'];
        $registration = $detail['registration'];
        $screening = $detail['screening'];
        $formSummary = is_array($detail['formSummary']) ? $detail['formSummary'] : [];
        $contact = $donor['contact'] ?? $registration['contact'] ?? $registration['contact_number'] ?? null;
        $email = $donor['email'] ?? $registration['email'] ?? null;
    @endphp
    <div id="{{ $detail['detailId'] }}" class="modal-shell" data-modal hidden>
        <div class="modal-backdrop" data-modal-backdrop></div>
        <section class="modal-panel modal-panel-lg donor-detail-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $detail['detailId'] }}-title">
            <div class="modal-header">
                <div class="flex items-center gap-3">
                    <span class="avatar">{{ $detail['initials'] ?: 'RC' }}</span>
                    <div>
                        <p class="eyebrow">Donor Detail</p>
                        <h2 id="{{ $detail['detailId'] }}-title">{{ $detail['name'] }}</h2>
                    </div>
                </div>
                <button class="modal-close" type="button" data-modal-close aria-label="Close donor detail">x</button>
            </div>

            <div class="donor-detail-body">
            <div class="detail-hero">
                <div>
                    <span class="badge is-active">{{ $donor['blood_type'] ?? 'O-' }}</span>
                    <span class="badge {{ $detail['status'] === 'Deferred' ? 'status-warning' : '' }}">{{ $detail['status'] }}</span>
                </div>
                <div class="detail-grid mt-4">
                    <div><span>Donor ID</span><strong>{{ $donor['donor_code'] ?? $donor['id'] ?? 'Not provided' }}</strong></div>
                    <div><span>Email</span><strong>{{ $cleanValue($email) }}</strong></div>
                    <div><span>Contact</span><strong>{{ $cleanValue($contact) }}</strong></div>
                    <div><span>Last Donation</span><strong>{{ $donor['last_donation_label'] ?? $donor['lastDonation'] ?? 'Never' }}</strong></div>
                    <div><span>Units</span><strong>{{ $donor['total_units'] ?? $donor['units'] ?? 0 }}</strong></div>
                </div>
            </div>

            <div class="detail-section-grid">
                <section class="detail-section">
                    <h3>Registration Details</h3>
                    <dl class="detail-list">
                        <div><dt>Age</dt><dd>{{ $cleanValue($registration['age'] ?? $donor['age'] ?? null) }}</dd></div>
                        <div><dt>Weight</dt><dd>{{ $cleanValue($registration['weight'] ?? $donor['weight'] ?? null) }}</dd></div>
                        <div><dt>Address</dt><dd>{{ $cleanValue($registration['address'] ?? $donor['address'] ?? null) }}</dd></div>
                        <div><dt>Last Donation Date</dt><dd>{{ $cleanValue($registration['last_donation_date'] ?? $donor['last_donation_date'] ?? null) }}</dd></div>
                    </dl>
                </section>
                <section class="detail-section">
                    <h3>Screening Details</h3>
                    <dl class="detail-list">
                        <div><dt>Hemoglobin</dt><dd>{{ $cleanValue($screening['hemoglobin'] ?? $donor['hemoglobin'] ?? null) }}</dd></div>
                        <div><dt>Blood Pressure</dt><dd>{{ $cleanValue($screening['blood_pressure'] ?? $donor['blood_pressure'] ?? null) }}</dd></div>
                        <div><dt>Temperature</dt><dd>{{ $cleanValue($screening['temperature'] ?? null) }}</dd></div>
                        <div><dt>Notes</dt><dd>{{ $cleanValue($screening['notes'] ?? $screening['medical_notes'] ?? null) }}</dd></div>
                    </dl>
                </section>
            </div>

            <section class="detail-section">
                <h3>Form Summary</h3>
                @if (!empty($formSummary))
                    <dl class="detail-list detail-list-wide">
                        @foreach ($formSummary as $key => $value)
                            <div>
                                <dt>{{ str($key)->replace('_', ' ')->title() }}</dt>
                                <dd>{{ $cleanValue($value) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="empty-note">No submitted schedule form is linked to this donor yet.</p>
                @endif
            </section>

            </div>

            <div class="modal-actions">
                <button class="btn-secondary" type="button" data-modal-close>Close</button>
            </div>
        </section>
    </div>
@endforeach

@unless ($embedded)
</section>
@endunless
