@extends('layouts.portal', ['title' => 'Account Security', 'heading' => 'Account Security', 'portal' => 'donor', 'active' => 'account-security'])

@section('portal')
@php
    $showPasswordReset = $errors->has('current_password') || $errors->has('new_password') || old('update_password');
@endphp

<form class="card max-w-4xl p-6" method="post" action="{{ route('donor.security.save') }}">
    @csrf
    <h2 class="section-title mb-5">Account Security</h2>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4 rounded-xl border border-red-100 bg-stone-50 p-5">
            <div class="flex items-center gap-3">
                <i data-lucide="lock" class="text-stone-500"></i>
                <div>
                    <p class="font-bold">Password</p>
                    <p class="text-sm text-stone-600">Last changed {{ $profile['password_updated_label'] ?? '6 months ago' }}</p>
                </div>
            </div>
            <button class="btn-secondary" type="button" data-security-reset="#password-reset-panel">Reset</button>
        </div>

        <div id="password-reset-panel" class="rounded-xl border border-red-100 bg-stone-50 p-5" @if (! $showPasswordReset) hidden @endif>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="label" for="current_password">Current Password</label>
                    <input id="current_password" class="input" name="current_password" type="password" autocomplete="current-password">
                    @error('current_password')
                        <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label" for="new_password">New Password</label>
                    <input id="new_password" class="input" name="new_password" type="password" autocomplete="new-password">
                    @error('new_password')
                        <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label" for="new_password_confirmation">Confirm Password</label>
                    <input id="new_password_confirmation" class="input" name="new_password_confirmation" type="password" autocomplete="new-password">
                </div>
            </div>
            <button class="btn-primary mt-4" name="update_password" value="1" type="submit">Save New Password</button>
        </div>

        <label class="flex items-center justify-between gap-4 rounded-xl border border-red-100 bg-stone-50 p-5">
            <div class="flex items-center gap-3">
                <i data-lucide="lock" class="text-stone-500"></i>
                <div>
                    <p class="font-bold">Two-Factor Authentication</p>
                    <p class="text-sm text-stone-600">Secure your account with 2FA</p>
                </div>
            </div>
            <input class="size-4 rounded border-red-200" type="checkbox" name="two_factor_enabled" value="1" @checked($profile['two_factor_enabled'] ?? false)>
        </label>
    </div>
    <button class="btn-primary mt-6" type="submit">Update Security Settings</button>
</form>
@endsection
