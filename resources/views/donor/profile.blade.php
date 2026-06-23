@extends('layouts.portal', ['title' => 'Personal Info', 'heading' => 'Personal Info', 'portal' => 'donor', 'active' => 'personal-info'])

@section('portal')
@php
    $name = $profile['full_name'] ?? 'Juan Dela Cruz';
    $initials = collect(explode(' ', $name))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
@endphp

<div class="space-y-6">
    <section class="card flex max-w-3xl flex-col gap-6 p-6 sm:flex-row sm:items-center">
        <div class="grid size-24 place-items-center rounded-full bg-red-100 text-2xl font-extrabold text-red-700">{{ $initials ?: 'JD' }}</div>
        <div>
            <h1 class="text-3xl font-extrabold tracking-[-0.03em]">{{ $name }}</h1>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="badge">Verified Donor</span>
                <span class="badge is-active">Blood Type: {{ $profile['blood_type'] ?? 'O+' }}</span>
            </div>
            <p class="mt-3 text-stone-600">Dedicated donor since 2019. Active contributor to Red Cross Philippines.</p>
        </div>
    </section>

    <form class="card max-w-5xl p-6" method="post" action="{{ route('donor.profile.save') }}">
        @csrf
        <h2 class="section-title mb-5">Personal Details</h2>
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="label">Full Name</label><input class="input" name="full_name" value="{{ $name }}" required></div>
            <div><label class="label">Phone Number</label><input class="input" name="phone" value="{{ $profile['phone'] ?? '+63 912 345 6789' }}"></div>
            <div><label class="label">Blood Type</label><select class="select" name="blood_type">@foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $type)<option @selected(($profile['blood_type'] ?? 'O+') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div><label class="label">Residential Address</label><input class="input" name="address" value="{{ $profile['address'] ?? 'Santa Rosa, Laguna' }}"></div>
        </div>
        <button class="btn-primary mt-6" type="submit">Save Changes</button>
    </form>
</div>
@endsection
