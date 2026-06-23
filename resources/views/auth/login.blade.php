@extends('layouts.app', ['title' => 'RedCross Portal - Interactive Registration & Login'])

@php
    $slides = [
        [
            'title' => 'Save Lives Today',
            'text' => 'Every donation can save up to three lives. Your contribution makes a direct impact in your community.',
            'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDX95p7cBihcJwMwTB-QWFzRghozp6gzKOuI4dwY_Hd0gdS46STq6EoMPYP3iQHuulBGDbiOwyz3Kd7vgFbcZxHIMnrQnwwXDAdGv9nrseI5PZf786ZOYyC5zvzQYSR3UHo3wpa3JQT9C63P4PlGbiIwE2fJDKU7ZYzUX2HAjfhOuoOQf7iP56ImjUDx_CsfAwEpJnqJHadUNmMudKyUGwzgLWVS_JKL99wHYz3UJfwbvFDMUBhXTlGjSrJKy-Fh6MFEiHR6D4_G0w',
        ],
        [
            'title' => 'Real-time Supply Tracking',
            'text' => 'Stay informed about urgent blood type needs and track the journey of your donated units.',
            'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBRugzP9wGrYoqHab5jozufSA4_T5ZjREGPt8W2kYdBp3uAjERW-1h_lbe29D_3FihJakRylna5TQIBZY3F7AcGRhTKatg5Cowyb6nXyZevWUP4dqPi9f5pvo2zT6876wCREEvjFRU9HqunHiHx9W_cgj8W_4DhhhXzllRIPu55Nrjc1dgNBAvYfvaf3MgGYvV8MxtYJT1WvQ_CQddhLtNmxIcaml4sazmLShV9QCkcKCmjHWQGdn_B4SVYsHAsnTTOmBN66Op91Fw',
        ],
    ];
    $activeSlideIndex = $portal === 'admin' ? 1 : 0;
@endphp

@section('body')
<main class="login-body">
    <div class="browser-title">
        <span class="browser-dot"></span>
        <span>RedCross Portal - Interactive Registration &amp; Login</span>
    </div>

    <section class="login-frame">
        <div class="login-inner">
            <header class="login-brand">
                <div class="login-brand-title">
                    <span class="font-medium text-red-600">bloodtype</span>
                    <span class="font-extrabold text-black">RedCross Blood Bank</span>
                </div>
                <p class="mt-2 text-xs text-[#3a0a00]">Saving Lives Through Every Donation</p>
            </header>

            <div class="login-stage">
                <section class="login-card">
                    @include('partials.flash')

                    @if (session('unconfirmed_email'))
                        <form method="post" action="{{ route('confirmation.resend') }}" class="mb-5 rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                            @csrf
                            <input type="hidden" name="email" value="{{ session('unconfirmed_email') }}">
                            <p class="font-extrabold">Your account still needs email confirmation.</p>
                            <p class="mt-1">Check {{ session('unconfirmed_email') }} or send a new confirmation link.</p>
                            <button class="mt-2 font-extrabold text-red-700 hover:underline" type="submit">Resend confirmation email</button>
                        </form>
                    @endif

                    <div class="login-tabs">
                        <a class="login-tab {{ $portal !== 'admin' ? 'is-active' : '' }}" href="{{ route('login', ['portal' => 'donor']) }}">Donor Portal</a>
                        <a class="login-tab {{ $portal === 'admin' ? 'is-active' : '' }}" href="{{ route('login', ['portal' => 'admin']) }}">Admin Access</a>
                    </div>

                    <h1 class="text-[20px] font-extrabold tracking-[-0.03em]">
                        {{ $portal === 'admin' ? 'Clinical Admin Login' : 'Welcome to the Donor Portal' }}
                    </h1>
                    <p class="mt-4 max-w-[250px] text-xs leading-[1.55] text-[#5f3f3a]">
                        {{ $portal === 'admin' ? 'Access staff resources and laboratory management tools.' : 'Sign in to manage your appointments and view your contribution history.' }}
                    </p>

                    <form method="post" action="{{ route('login.store') }}" class="mt-7 space-y-[18px]">
                        @csrf
                        <input type="hidden" name="portal" value="{{ $portal === 'admin' ? 'admin' : 'donor' }}">

                        @if ($portal === 'admin')
                            <div>
                                <label class="label">Staff ID</label>
                                <input class="input" name="staff_id" value="{{ old('staff_id') }}" placeholder="E.g. RC-44920">
                            </div>
                            <div>
                                <label class="label">Secure Admin Password</label>
                                <input class="input" name="password" type="password" placeholder="........">
                            </div>
                        @else
                            <div>
                                <label class="label">Email Address</label>
                                <input class="input" name="email" type="email" value="{{ old('email', 'donor@redcross.org') }}" placeholder="donor@redcross.org">
                            </div>
                            <div>
                                <div class="mb-2 flex items-end justify-between gap-3">
                                    <label class="label mb-0">Password</label>
                                    <button class="text-xs font-extrabold text-red-600 hover:underline" type="submit" formaction="{{ route('password.forgot') }}">Forgot password?</button>
                                </div>
                                <input class="input" name="password" type="password" placeholder="........">
                            </div>
                        @endif

                        <label class="flex items-center gap-3 pt-1 text-xs text-[#5f3f3a]">
                            <input class="h-4 w-4 rounded border-[#e9bcb5] bg-[#f6f3f2] text-red-600" type="checkbox" name="remember">
                            Remember this device
                        </label>

                        <button class="btn-primary login-submit w-full py-[14px] text-sm" type="submit">
                            {{ $portal === 'admin' ? 'Verify Admin Credentials' : 'Access Portal Login' }}
                        </button>
                    </form>

                    @if ($portal !== 'admin')
                        <p class="mt-[18px] text-center text-xs text-[#5f3f3a]">
                            Don't have an account?
                            <a class="font-extrabold text-red-600 hover:underline" href="{{ route('register') }}">Register as a Donor</a>
                        </p>
                    @endif

                    <footer class="mt-10 border-t border-[#e9bcb5]/25 pt-7 text-center text-xs text-[#5f3f3a]">
                        Need help with your donor account?<br>
                        <button class="font-extrabold text-red-600 hover:underline" type="button">Contact Donor Support</button>
                    </footer>
                </section>

                <aside class="login-slide login-carousel" data-login-carousel aria-label="Donation highlights">
                    @foreach ($slides as $slide)
                        <article class="login-carousel-slide {{ $loop->index === $activeSlideIndex ? 'is-active' : '' }}" data-carousel-slide>
                            <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] }}">
                            <div class="login-slide-copy">
                                <h2 class="text-[18px] font-extrabold">{{ $slide['title'] }}</h2>
                                <p class="mt-3 max-w-[310px] text-xs font-semibold leading-[1.55]">{{ $slide['text'] }}</p>
                            </div>
                        </article>
                    @endforeach

                    <button class="carousel-arrow is-left" type="button" data-carousel-control="prev" aria-label="Previous slide">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <button class="carousel-arrow is-right" type="button" data-carousel-control="next" aria-label="Next slide">
                        <i data-lucide="chevron-right"></i>
                    </button>

                    <div class="login-carousel-dots" role="tablist" aria-label="Slide selector">
                        @foreach ($slides as $slide)
                            <button
                                class="login-carousel-dot {{ $loop->index === $activeSlideIndex ? 'is-active' : '' }}"
                                type="button"
                                data-carousel-dot="{{ $loop->index }}"
                                aria-label="Show {{ $slide['title'] }}"
                                aria-selected="{{ $loop->index === $activeSlideIndex ? 'true' : 'false' }}"
                            ></button>
                        @endforeach
                    </div>
                </aside>
            </div>

            <footer class="login-status">
                <div class="flex items-center gap-6">
                    <span class="inline-flex items-center gap-2"><span class="login-status-dot"></span> Clinical Network Active</span>
                    <span>Verified_User</span>
                    <span>HIPAA Compliant</span>
                </div>
                <span class="text-red-300">RedCross Vita-Portal v2.1</span>
            </footer>
        </div>
    </section>
</main>
@endsection
