@php
    $staffProfile = $profile ?? [];
    $role = (string) ($staffProfile['role'] ?? ($portal === 'super' ? 'super_admin' : 'admin'));
    $roleLabel = $role === 'super_admin' ? 'Super Administrator' : 'Administrator';
    $name = (string) ($staffProfile['full_name'] ?? 'RedCross Administrator');
    $initials = collect(explode(' ', $name))
        ->filter()
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="page-header">
    <h1 class="page-title">My Profile</h1>
    <p class="page-subtitle">Review your staff account and contact details.</p>
</header>

<section class="staff-profile card">
    <div class="staff-profile-summary">
        <span class="staff-profile-avatar">{{ $initials ?: 'RC' }}</span>
        <div>
            <p class="eyebrow">{{ $roleLabel }}</p>
            <h2>{{ $name }}</h2>
            <p>{{ $staffProfile['email'] ?? 'Email not available' }}</p>
        </div>
    </div>

    <div class="staff-profile-readonly">
        <div><span>Role</span><strong>{{ $roleLabel }}</strong></div>
        <div><span>Email</span><strong>{{ $staffProfile['email'] ?? 'Not provided' }}</strong></div>
    </div>

    <form class="staff-profile-form" method="post" action="{{ route('staff.profile.save') }}">
        @csrf
        <h2 class="section-title">Contact Details</h2>
        <div class="form-grid mt-5">
            <div>
                <label class="label" for="staff-full-name">Full Name</label>
                <input id="staff-full-name" class="input" name="full_name" value="{{ $name }}" required>
            </div>
            <div>
                <label class="label" for="staff-phone">Phone Number</label>
                <input id="staff-phone" class="input" name="phone" value="{{ $staffProfile['phone'] ?? '' }}" placeholder="+63 912 345 6789">
            </div>
        </div>
        <div class="mt-5">
            <label class="label" for="staff-address">Address</label>
            <textarea id="staff-address" class="textarea" name="address" placeholder="Office or mailing address">{{ $staffProfile['address'] ?? '' }}</textarea>
        </div>
        <div class="mt-6 flex justify-end">
            <button class="btn-primary" type="submit"><i data-lucide="save"></i> Save Profile</button>
        </div>
    </form>
</section>
