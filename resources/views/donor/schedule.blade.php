@extends('layouts.portal', ['title' => 'Schedule', 'heading' => 'Schedule', 'portal' => 'donor', 'active' => 'schedule'])

@section('portal')
@php
    $profile = $profile ?? [];
    $centerFallbacks = [
        ['id' => 'CTR-SR-LAGUNA', 'name' => 'PRC Laguna Chapter - Santa Rosa Branch', 'address' => 'Rotary Lane, Brgy. Tagapo, City of Santa Rosa, Laguna', 'type' => 'Santa Rosa Red Cross', 'tag' => 'Santa Rosa, Laguna', 'lat' => 14.31554, 'lng' => 121.11104],
        ['id' => 'CTR-MAKATI-HQ', 'name' => 'Makati National HQ', 'address' => '37 EDSA corner Boni Avenue, Mandaluyong City', 'type' => 'Collection Point', 'tag' => 'Makati HQ', 'lat' => 14.5764, 'lng' => 121.0359],
    ];
    $centerRows = collect($centers)->map(fn ($center) => [
        'id' => $center['id'] ?? 'CTR-SR-LAGUNA',
        'name' => $center['name'] ?? 'PRC Laguna Chapter - Santa Rosa Branch',
        'address' => $center['address'] ?? 'Rotary Lane, Brgy. Tagapo, City of Santa Rosa, Laguna',
        'type' => $center['center_type'] ?? 'Major Clinic',
        'tag' => $center['city'] ?? $center['area'] ?? ($center['center_type'] ?? 'Santa Rosa, Laguna'),
        'lat' => (float) ($center['latitude'] ?? 14.31554),
        'lng' => (float) ($center['longitude'] ?? 121.11104),
    ])->filter(fn ($center) => $center['lat'] !== 0.0 && $center['lng'] !== 0.0)->values();
    $scheduleCenters = $centerRows->isNotEmpty() ? $centerRows : collect($centerFallbacks);
    $selectedCenter = $scheduleCenters->firstWhere('id', 'CTR-SR-LAGUNA') ?? $scheduleCenters->first();
    $scheduleMapCenters = $scheduleCenters->map(fn ($center) => [
        ...$center,
        'pin' => number_format((float) $center['lat'], 5, '.', '').', '.number_format((float) $center['lng'], 5, '.', ''),
    ])->values();
    $selectedCenter = [
        ...$selectedCenter,
        'pin' => number_format((float) $selectedCenter['lat'], 5, '.', '').', '.number_format((float) $selectedCenter['lng'], 5, '.', ''),
    ];
    $defaultDate = old('scheduled_date', now()->addDay()->format('Y-m-d'));
    $defaultTime = old('scheduled_time', '10:30 AM');
    $confirmed = session('appointment_confirmed');
    $profileName = trim((string) ($profile['full_name'] ?? ''));
    $profileEmail = trim((string) ($profile['email'] ?? ''));
    $donorName = old('donor_full_name', $profileName !== '' && strtolower($profileName) !== 'redcross user' ? $profileName : '');
    $donorBloodType = old('donor_blood_type', $profile['blood_type'] ?? '');
@endphp

<div>
    <header class="page-header">
        <h2 class="page-title">Book Your Life-Saving Appointment</h2>
        <p class="page-subtitle">Choose your preferred slot below.</p>
    </header>

    @if ($confirmed)
        <section class="schedule-confirmed card">
            <div class="schedule-confirmed-flag">
                <i data-lucide="check"></i>
                <strong>{{ ($confirmed['service_type'] ?? '') === 'Blood Request' ? 'Request' : 'Appointment' }}<br>Submitted</strong>
            </div>
            <div class="schedule-confirmed-copy">
                <h2>Salamat, {{ $profileName !== '' ? explode(' ', $profileName)[0] : 'Donor' }}!</h2>
                <p>{{ ($confirmed['service_type'] ?? '') === 'Blood Request' ? 'Your blood request has been submitted for review.' : 'Your appointment is scheduled and waiting for staff review.' }}</p>
                <button class="btn-primary mt-6" type="button" data-schedule-reset>Book Another</button>
            </div>
        </section>
    @endif

    <div class="schedule-wizard {{ $confirmed ? 'is-confirmed' : '' }}" data-schedule-wizard @if($confirmed) hidden @endif>
        <div class="card mb-6 schedule-steps" data-schedule-steps>
            @foreach (['Eligibility','Service','Forms','Location','Date & Time','Confirmation'] as $index => $label)
                <div class="schedule-step {{ $index === 0 ? 'is-active' : '' }}" data-schedule-step-indicator="{{ $index + 1 }}">
                    <span>{{ $index + 1 }}</span>
                    <strong>{{ $label }}</strong>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
            <form method="post" action="{{ route('donor.schedule.save') }}" class="card p-6" enctype="multipart/form-data" data-schedule-form>
                @csrf
                <input type="hidden" name="_active_tab" value="schedule">
                <input type="hidden" name="service_type" value="{{ old('service_type', 'Donor Appointment') }}" data-schedule-service-input>
                <input type="hidden" name="center_name" value="{{ $selectedCenter['name'] }}" data-schedule-center-input>
                <input type="hidden" name="donation_center_id" value="{{ $selectedCenter['id'] }}" data-schedule-center-id-input>
                <input type="hidden" name="scheduled_time" value="{{ $defaultTime }}" data-schedule-time-input>

                <section data-schedule-panel="1">
                    <h2 class="section-title mb-5">Eligibility Screening</h2>
                    <div class="schedule-deferred-alert {{ $errors->has('eligibility') ? 'is-visible' : '' }}" data-schedule-deferred-alert role="alert" @unless($errors->has('eligibility')) hidden @endunless>
                        <strong>Deferred</strong>
                        <span>{{ $errors->first('eligibility') ?: 'One or more screening answers require staff review before you can continue with a donation booking.' }}</span>
                    </div>
                    <div class="space-y-3">
                        @foreach ([
                            'fever' => 'Do you currently have a fever or feel unwell?',
                            'surgery' => 'Have you had surgery in the last 6 months?',
                            'tattoo' => 'Recent tattoo or piercing within the last 6 months?',
                            'antibiotics' => 'Are you taking antibiotics or under treatment for infection?',
                            'infection_test' => 'Have you ever tested positive for HIV, Hepatitis B, or Hepatitis C?',
                        ] as $name => $question)
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-red-100 bg-stone-50 p-4 text-base">
                                <span>{{ $question }}</span>
                                <input class="size-4 rounded border-stone-400" type="checkbox" name="{{ $name }}" value="1" data-schedule-risk-answer @checked(old($name))>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">Hemoglobin</label>
                            <input class="input" name="hemoglobin" value="{{ old('hemoglobin', '14.2') }}">
                        </div>
                        <div>
                            <label class="label">Blood Pressure</label>
                            <input class="input" name="blood_pressure" value="{{ old('blood_pressure', '118/76') }}">
                        </div>
                    </div>

                    <button class="btn-primary mt-6" type="button" data-schedule-next>Confirm and Proceed</button>
                </section>

                <section data-schedule-panel="2" hidden>
                    <h2 class="section-title mb-5">Choose Service Type</h2>
                    <div class="schedule-service-grid">
                        <button class="schedule-choice is-selected" type="button" data-schedule-service="Donor Appointment">
                            <i data-lucide="droplet"></i>
                            <strong>Donor Appointment</strong>
                            <span>Schedule a life-saving blood donation.</span>
                        </button>
                        <button class="schedule-choice" type="button" data-schedule-service="Blood Request">
                            <i data-lucide="clipboard-list"></i>
                            <strong>Blood Request</strong>
                            <span>Request blood units for patients or facilities.</span>
                        </button>
                    </div>
                    <div class="schedule-actions">
                        <button class="btn-outline" type="button" data-schedule-back>Back</button>
                        <button class="btn-primary" type="button" data-schedule-next>Next: Forms</button>
                    </div>
                </section>

                <section data-schedule-panel="3" hidden>
                    <div data-schedule-service-form="Donor Appointment">
                        <div class="text-center">
                            <h2 class="page-title text-3xl">Donor Registration</h2>
                            <p class="page-subtitle">Your contribution is a lifeline. Complete your donor details before choosing a center.</p>
                        </div>
                        <div class="schedule-form-card mt-7">
                            <div class="form-grid">
                                <div><label class="label">Full Name</label><input class="input" name="donor_full_name" value="{{ $donorName }}" placeholder="Enter your full legal name"></div>
                                <div><label class="label">Age</label><input class="input" name="donor_age" type="number" min="18" value="{{ old('donor_age') }}" placeholder="Minimum 18 years"></div>
                                <div>
                                    <label class="label">Blood Type</label>
                                    <select class="select" name="donor_blood_type">
                                        <option value="">Select blood type</option>
                                        @foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $type)
                                            <option value="{{ $type }}" @selected($donorBloodType === $type)>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div><label class="label">Weight (kg)</label><input class="input" name="donor_weight_kg" type="number" min="40" step="0.1" value="{{ old('donor_weight_kg') }}" placeholder="Minimum 50kg"></div>
                                <div><label class="label">Contact Number</label><input class="input" name="donor_contact_number" value="{{ old('donor_contact_number', $profile['phone'] ?? '') }}" placeholder="+63 912 345 6789"></div>
                                <div><label class="label">Last Donation Date</label><input class="input" name="donor_last_donation_date" type="date" value="{{ old('donor_last_donation_date') }}"></div>
                            </div>
                            <div class="mt-4"><label class="label">Residential Address</label><textarea class="textarea" name="donor_address" placeholder="Enter your full mailing address">{{ old('donor_address', $profile['address'] ?? '') }}</textarea></div>
                            <div class="mt-5 border-t border-red-100 pt-5">
                                <h3 class="section-title">Medical History</h3>
                                <p class="mt-1 text-sm text-stone-600">Check all that apply for safety screening.</p>
                                <div class="medical-grid mt-4">
                                    @foreach (['Diabetes','Hypertension','Recent Surgery','Allergies','Anemia','Infectious Disease'] as $flag)
                                        <label><input type="checkbox" name="donor_medical_flags[]" value="{{ $flag }}" @checked(in_array($flag, old('donor_medical_flags', []), true))> {{ $flag }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4"><label class="label">Other Medical Conditions / Details</label><textarea class="textarea" name="donor_medical_notes" placeholder="Describe any other conditions or medications...">{{ old('donor_medical_notes') }}</textarea></div>
                            <label class="mt-5 flex items-start gap-3 text-sm text-stone-700">
                                <input class="mt-1" type="checkbox" name="donor_certified" value="1" @checked(old('donor_certified'))>
                                <span>I certify that the information provided is true and accurate to the best of my knowledge.</span>
                            </label>
                        </div>
                    </div>

                    <div data-schedule-service-form="Blood Request" hidden>
                        <h2 class="section-title text-2xl">Blood Request Form</h2>
                        <p class="page-subtitle">Initialize blood requirements for medical facilities with accurate recipient data.</p>
                        <div class="schedule-request-grid mt-6">
                            <div class="space-y-4">
                                <section class="schedule-form-card">
                                    <h3 class="section-title flex items-center gap-2"><i data-lucide="user"></i> Patient Details</h3>
                                    <div class="form-grid mt-4">
                                        <div><label class="label">Patient Full Name</label><input class="input" name="patient_name" value="{{ old('patient_name') }}" placeholder="Enter recipient's full legal name"></div>
                                        <div><label class="label">Hospital / Medical Center Name</label><input class="input" name="hospital_name" value="{{ old('hospital_name') }}" placeholder="e.g., Central General Hospital"></div>
                                        <div><label class="label">Attending Physician's Name</label><input class="input" name="physician_name" value="{{ old('physician_name') }}" placeholder="Dr. Smith"></div>
                                        <div><label class="label">Contact Person & Number</label><input class="input" name="request_contact_person" value="{{ old('request_contact_person') }}" placeholder="Nurse or request coordinator"></div>
                                        <div><label class="label">Contact Number</label><input class="input" name="contact_number" value="{{ old('contact_number') }}" placeholder="+1 (555) 000-0000"></div>
                                    </div>
                                </section>
                                <section class="schedule-form-card">
                                    <h3 class="section-title flex items-center gap-2"><i data-lucide="droplet"></i> Request Specifications</h3>
                                    <div class="form-grid mt-4">
                                        <div>
                                            <label class="label">Blood Type Needed</label>
                                            <select class="select" name="blood_type">
                                                <option value="">Select blood type</option>
                                                @foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $type)
                                                    <option value="{{ $type }}" @selected(old('blood_type') === $type)>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div><label class="label">Units Needed</label><input class="input" name="units_needed" type="number" min="1" value="{{ old('units_needed') }}" placeholder="Quantity of units"></div>
                                        <div>
                                            <label class="label">Component Type</label>
                                            <select class="select" name="component_type">
                                                <option value="">Select component type</option>
                                                @foreach(['Whole Blood','Packed RBC','Platelets','Plasma','Cryoprecipitate'] as $component)
                                                    <option value="{{ $component }}" @selected(old('component_type') === $component)>{{ $component }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div><label class="label">Required By Date/Time</label><input class="input" name="required_at" type="datetime-local" value="{{ old('required_at') }}"></div>
                                    </div>
                                    <div class="urgency-grid mt-4">
                                        @foreach (['routine' => 'Routine', 'urgent' => 'Urgent', 'emergency' => 'Emergency'] as $value => $label)
                                            <label><input type="radio" name="urgency" value="{{ $value }}" @checked(old('urgency', 'routine') === $value)> {{ $label }}</label>
                                        @endforeach
                                    </div>
                                </section>
                                <section class="schedule-form-card">
                                    <h3 class="section-title flex items-center gap-2"><i data-lucide="file-text"></i> Medical Information</h3>
                                    <div class="mt-4"><label class="label">Diagnosis / Reason for Request</label><textarea class="textarea" name="diagnosis" placeholder="Detailed clinical diagnosis and reason for transfusion...">{{ old('diagnosis') }}</textarea></div>
                                    <div class="mt-4"><label class="label">Medical Justification</label><textarea class="textarea" name="medical_justification" placeholder="Clinical justification and notes...">{{ old('medical_justification') }}</textarea></div>
                                    <div class="mt-4"><label class="label">Supporting Document</label><input class="input" name="supporting_document" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                                    <div class="mt-4"><label class="label">Document Description</label><input class="input" name="document_description" value="{{ old('document_description') }}" placeholder="e.g., laboratory result or physician order"></div>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="schedule-actions">
                        <button class="btn-outline" type="button" data-schedule-back>Back</button>
                        <button class="btn-primary" type="button" data-schedule-next>Next: Location</button>
                    </div>
                </section>

                <section data-schedule-panel="4" hidden>
                    <h2 class="section-title mb-5">Select Donation Center</h2>
                    <div class="schedule-map-card">
                        <div
                            class="schedule-leaflet-map"
                            data-schedule-leaflet-map
                            data-centers="{{ $scheduleMapCenters->toJson() }}"
                            data-selected-center="{{ $selectedCenter['id'] }}"
                            aria-label="OpenStreetMap view centered on selected donation center"
                        ></div>
                        <div class="schedule-center-details">
                            <div>
                                <p class="eyebrow red">Selected Center</p>
                                <select class="select mt-3" data-schedule-center-select aria-label="Select donation center">
                                    @foreach ($scheduleMapCenters as $center)
                                        <option
                                            data-id="{{ $center['id'] }}"
                                            value="{{ $center['name'] }}"
                                            data-address="{{ $center['address'] }}"
                                            data-type="{{ $center['type'] }}"
                                            data-tag="{{ $center['tag'] }}"
                                            data-pin="{{ $center['pin'] }}"
                                            data-lat="{{ $center['lat'] }}"
                                            data-lng="{{ $center['lng'] }}"
                                            @selected($center['name'] === $selectedCenter['name'])
                                        >{{ $center['name'] }}</option>
                                    @endforeach
                                </select>
                                <h3 class="mt-4 text-xl font-extrabold" data-schedule-center-name>{{ $selectedCenter['name'] }}</h3>
                                <p class="mt-1 text-stone-600" data-schedule-center-address>{{ $selectedCenter['address'] }}</p>
                                <p class="mt-2 text-sm font-bold text-stone-500">Map pin: <span data-schedule-center-pin>{{ $selectedCenter['pin'] }}</span></p>
                            </div>
                            <span class="badge is-active" data-schedule-center-tag>{{ $selectedCenter['tag'] }}</span>
                        </div>
                    </div>
                    <div class="schedule-actions">
                        <button class="btn-outline" type="button" data-schedule-back>Back</button>
                        <button class="btn-primary" type="button" data-schedule-next>Next: Date and Time</button>
                    </div>
                </section>

                <section data-schedule-panel="5" hidden>
                    <h2 class="section-title mb-5">Select Date and Time</h2>
                    <div class="schedule-date-grid">
                        <div class="schedule-date-card">
                            <label class="label" for="scheduled_date">Appointment Date</label>
                            <input id="scheduled_date" class="input" type="date" name="scheduled_date" value="{{ $defaultDate }}" data-schedule-date-input required>
                            <div class="schedule-selected-date">Selected: <span data-schedule-date-label>{{ date('F j, Y', strtotime($defaultDate)) }}</span></div>
                        </div>
                        <div class="schedule-time-grid" data-schedule-time-options>
                            @foreach (['09:00 AM', '10:30 AM', '01:00 PM', '02:30 PM'] as $time)
                                <button class="schedule-time {{ $time === $defaultTime ? 'is-selected' : '' }}" type="button" data-schedule-time="{{ $time }}">{{ $time }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="schedule-actions">
                        <button class="btn-outline" type="button" data-schedule-back>Back</button>
                        <button class="btn-primary" type="button" data-schedule-next>Review Selection</button>
                    </div>
                </section>

                <section data-schedule-panel="6" hidden>
                    <h2 class="section-title mb-5">Confirm Booking</h2>
                    <div class="schedule-review">
                        <div>
                            <p class="eyebrow">Service</p>
                            <strong data-schedule-review-service>Donor Appointment</strong>
                            <span data-schedule-review-form-summary>Donor registration details ready.</span>
                        </div>
                        <div>
                            <p class="eyebrow">Location</p>
                            <strong data-schedule-review-center>{{ $selectedCenter['name'] }}</strong>
                            <span data-schedule-review-address>{{ $selectedCenter['address'] }}</span>
                        </div>
                        <div>
                            <p class="eyebrow">Date and Time</p>
                            <strong data-schedule-review-date>{{ date('F j, Y', strtotime($defaultDate)) }}</strong>
                            <span><span data-schedule-review-time>{{ $defaultTime }}</span></span>
                        </div>
                    </div>
                    <div class="schedule-actions">
                        <button class="btn-ghost" type="button" data-schedule-edit>Edit Selection</button>
                        <button class="btn-primary" type="submit">Confirm and Book</button>
                    </div>
                </section>
            </form>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h3 class="font-bold text-red-700">Prep Tips</h3>
                    <p class="mt-2 text-sm text-stone-600">Hydrate and bring a valid ID before your visit.</p>
                </div>
                <div class="rounded-xl bg-red-700 p-5 text-white shadow-lg">
                    <p class="text-xs font-bold uppercase">Your lifetime impact</p>
                    <p class="mt-2 text-2xl font-extrabold">12 Lives Saved</p>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
