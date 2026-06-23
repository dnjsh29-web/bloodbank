@extends('layouts.portal', ['title' => 'Blood Request', 'heading' => 'Blood Request', 'portal' => 'donor', 'active' => 'blood-request'])

@section('portal')
<div class="mb-6">
    <h2 class="text-3xl font-extrabold leading-tight tracking-tight">Blood Request Form</h2>
    <p class="mt-1 max-w-2xl text-base text-stone-600">Initialize clinical blood requirements for medical facilities. Please provide accurate recipient data and medical justification.</p>
</div>
<form class="grid gap-6 lg:grid-cols-[1fr_320px]" method="post" action="{{ route('blood.request.save') }}">
    @csrf
    <div class="space-y-6">
        <section class="card p-6">
            <h2 class="mb-5 border-b border-red-100 pb-3 text-xl font-bold">Patient Details</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="label">Patient Full Name</label><input class="input" name="patient_name" required></div>
                <div><label class="label">Hospital / Medical Center Name</label><input class="input" name="hospital_name" required></div>
                <div><label class="label">Attending Physician's Name</label><input class="input" name="physician_name"></div>
                <div><label class="label">Contact Person & Number</label><input class="input" name="contact_number" required></div>
            </div>
        </section>
        <section class="card p-6">
            <h2 class="mb-5 border-b border-red-100 pb-3 text-xl font-bold">Request Specifications</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="label">Blood Type Needed</label><select class="select" name="blood_type">@foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
                <div><label class="label">Units Needed</label><input class="input" name="units_needed" type="number" min="1" value="1" required></div>
                <div><label class="label">Component Type</label><select class="select" name="component_type"><option>Whole Blood</option><option>Platelets</option><option>Plasma</option><option>Red Blood Cells</option></select></div>
                <div><label class="label">Required By Date/Time</label><input class="input" name="required_at" type="datetime-local"></div>
            </div>
            <div class="mt-4"><label class="label">Urgency Level</label><select class="select" name="urgency"><option value="routine">Routine</option><option value="urgent">Urgent</option><option value="emergency">Emergency</option></select></div>
        </section>
        <section class="card p-6">
            <h2 class="mb-5 border-b border-red-100 pb-3 text-xl font-bold">Medical Information</h2>
            <label class="label">Diagnosis / Reason for Request</label><textarea class="textarea" name="diagnosis" rows="4"></textarea>
            <div class="mt-4 rounded-xl border-2 border-dashed border-red-100 bg-stone-50 p-8 text-center text-stone-600">Click to upload or drag and drop<br><span class="text-xs">PDF, JPG, or DICOM files placeholder</span></div>
        </section>
    </div>
    <aside class="space-y-4">
        <div class="card p-6"><h3 class="mb-4 border-b border-red-100 pb-3 text-xl font-bold">Request Summary</h3><p class="text-sm text-stone-600">Priority Status</p><span class="badge">Pending Selection</span><div class="mt-5 rounded-lg border border-red-100 bg-stone-50 p-4"><p class="label">Estimated Fulfillment</p><p class="text-xl font-bold text-red-700">--:--</p></div><button class="btn-primary mt-6 w-full" type="submit">Submit Blood Request</button></div>
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-5 text-orange-950"><h4 class="font-bold uppercase">24/7 Clinical Support</h4><p class="mt-2 text-sm">For urgent bypass or logistic inquiries, contact the Hematology Command Center.</p></div>
    </aside>
</form>
@endsection
