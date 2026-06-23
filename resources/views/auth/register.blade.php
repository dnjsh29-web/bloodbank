@extends('layouts.app', ['title' => 'Donor Registration'])

@php
    $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
    $medicalOptions = ['Diabetes', 'Hypertension', 'Recent Surgery', 'Allergies', 'Anemia', 'Infectious Disease'];
@endphp

@section('body')
<main class="registration-page">
    <header class="registration-topbar">
        <a class="registration-brand" href="{{ route('landing') }}">
            <i data-lucide="droplet"></i>
            <span>RedCross Blood Bank</span>
        </a>
        <a class="registration-back" href="{{ route('login') }}">Back Home</a>
    </header>

    <section class="registration-hero">
        <h1>Donor Registration</h1>
        <p>Your contribution helps maintain a safe, responsive blood bank network.</p>
    </section>

    <section class="registration-wrap">
        @include('partials.flash')

        @if ($errors->any())
            <div class="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="registration-card" method="post" action="{{ route('register.store') }}">
            @csrf

            <div class="registration-grid">
                <div>
                    <label class="label" for="full_name">Full Name</label>
                    <input id="full_name" class="input" name="full_name" value="{{ old('full_name') }}" placeholder="Juan Dela Cruz" required>
                </div>

                <div>
                    <label class="label" for="email">Email Address</label>
                    <input id="email" class="input" name="email" type="email" value="{{ old('email') }}" placeholder="donor@redcross.test" required>
                </div>

                <div>
                    <label class="label" for="password">Create Password</label>
                    <input id="password" class="input" name="password" type="password" placeholder="Minimum 8 characters" required>
                </div>

                <div>
                    <label class="label" for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" class="input" name="password_confirmation" type="password" placeholder="Repeat password" required>
                </div>

                <div>
                    <label class="label" for="age">Age</label>
                    <input id="age" class="input" name="age" type="number" min="18" max="120" value="{{ old('age') }}" placeholder="Minimum 18 years">
                </div>

                <div>
                    <label class="label" for="blood_type">Blood Type</label>
                    <select id="blood_type" class="select" name="blood_type">
                        <option value="">Select blood type</option>
                        @foreach ($bloodTypes as $bloodType)
                            <option value="{{ $bloodType }}" @selected(old('blood_type') === $bloodType)>{{ $bloodType }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="weight">Weight (kg)</label>
                    <input id="weight" class="input" name="weight" type="number" min="40" max="250" step="0.1" value="{{ old('weight') }}" placeholder="Minimum 50kg">
                </div>

                <div>
                    <label class="label" for="phone">Contact Number</label>
                    <input id="phone" class="input" name="phone" value="{{ old('phone') }}" placeholder="+63 912 345 6789">
                </div>

                <div>
                    <label class="label" for="last_donation_at">Last Donation Date</label>
                    <input id="last_donation_at" class="input" name="last_donation_at" type="date" value="{{ old('last_donation_at') }}">
                </div>

                <div class="registration-span">
                    <label class="label" for="address">Residential Address</label>
                    <textarea id="address" class="textarea" name="address" placeholder="Enter your full mailing address">{{ old('address') }}</textarea>
                </div>
            </div>

            <div class="registration-medical">
                <h2>Medical History</h2>
                <p>Check all that apply for safety screening.</p>

                <div class="registration-checks">
                    @foreach ($medicalOptions as $option)
                        <label>
                            <input type="checkbox" name="medical_flags[]" value="{{ $option }}" @checked(in_array($option, old('medical_flags', []), true))>
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-6">
                    <label class="label" for="medical_notes">Other Medical Conditions</label>
                    <textarea id="medical_notes" class="textarea" name="medical_notes" placeholder="Describe conditions or medications...">{{ old('medical_notes') }}</textarea>
                </div>
            </div>

            <label class="registration-cert">
                <input type="checkbox" name="certify" value="1" @checked(old('certify')) required>
                <span>I certify that the information provided is true and accurate.</span>
            </label>

            <button class="btn-primary registration-submit" type="submit">Submit Registration</button>
        </form>
    </section>
</main>
@endsection
