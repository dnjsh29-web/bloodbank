@extends('layouts.app', ['title' => 'RedCross Blood Bank'])

@section('body')
<div>
    <header class="sticky top-0 z-30 border-b border-red-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <div class="text-xl font-extrabold tracking-tight"><span class="text-red-700">bloodtype</span> RedCross Blood Bank</div>
            <div class="flex gap-3">
                <a class="btn-ghost" href="{{ route('login', ['portal' => 'admin']) }}">Staff Login</a>
                <a class="btn-primary" href="{{ route('login') }}">Register Now</a>
            </div>
        </div>
    </header>

    <section class="mx-auto grid min-h-[620px] max-w-7xl items-center gap-12 px-6 py-16 md:grid-cols-2">
        <div>
            <p class="mb-4 text-sm font-bold uppercase tracking-[0.22em] text-red-700">Blood bank management</p>
            <h1 class="max-w-xl text-5xl font-extrabold leading-tight tracking-tight md:text-6xl">
                Every Drop Counts. <span class="text-red-700">Save a Life Today.</span>
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-stone-600">
                A full donor, inventory, request, and admin command system for regional blood bank operations.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a class="btn-primary px-8 py-4 text-base" href="{{ route('login') }}">Access Portal</a>
                <a class="btn-secondary px-8 py-4 text-base" href="{{ route('login') }}">Submit Blood Request</a>
            </div>
        </div>
        <div class="relative">
            <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-red-100 bg-gradient-to-br from-red-100 via-white to-stone-100 shadow-2xl">
                <div class="grid h-full place-items-center p-10">
                    <div class="w-full rounded-2xl bg-white/85 p-8 shadow-xl">
                        <div class="mb-8 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest text-stone-500">Inventory health</p>
                                <p class="mt-1 text-4xl font-extrabold text-red-700">1,842</p>
                            </div>
                            <span class="text-5xl text-red-700">♢</span>
                        </div>
                        <div class="mt-6 flex h-44 items-end justify-between gap-3 border-t border-red-100 px-1 pt-6">
                            @foreach ([70,45,90,38,76,46,86] as $i => $h)
                                <div class="flex flex-1 flex-col items-center gap-2">
                                    <div class="flex h-32 items-end gap-1">
                                        <span class="bar-hover w-3 rounded-t bg-red-700" data-tip="Inflow {{ $h * 6 }}" style="height:{{ max(18, $h) }}px"></span>
                                        <span class="bar-hover w-3 rounded-t bg-orange-800/80" data-tip="Outflow {{ $h * 4 }}" style="height:{{ max(18, $h * .65) }}px"></span>
                                    </div>
                                    <span class="text-xs text-stone-600">{{ ['Mon','Tue','Wed','Thu','Fri','Sat','Today'][$i] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="absolute -bottom-6 -left-4 rounded-xl bg-red-600 p-5 text-white shadow-xl">
                <div class="text-2xl">♡</div>
                <p class="mt-2 text-sm font-bold">Certified Care</p>
            </div>
        </div>
    </section>

    <section class="border-y border-red-100 bg-stone-50 py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Individual Donors', 'Schedule appointments, track eligibility, and view donation history.'],
                    ['Clinical Requests', 'Hospitals can request blood products with supporting documentation.'],
                    ['Admin Operations', 'Manage inventory, donor records, reports, alerts, maps, and audits.'],
                ] as [$title, $text])
                    <div class="card p-7">
                        <h3 class="text-xl font-bold">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-6 text-stone-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            <section class="mt-12">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-red-700">Campaigns</p>
                        <h2 class="mt-2 text-3xl font-extrabold">Current Blood Donation Campaigns</h2>
                    </div>
                    <div class="flex gap-3">
                        <button aria-label="Previous campaign" class="icon-btn" data-spin type="button">&larr;</button>
                        <button aria-label="Next campaign" class="icon-btn" data-spin type="button">&rarr;</button>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                    @forelse ($campaigns as $campaign)
                        <article class="card campaign-card group overflow-hidden text-left transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="relative h-36 overflow-hidden bg-red-50">
                                <img class="campaign-image h-full w-full object-cover" src="{{ $campaign['image_url'] ?? 'https://images.unsplash.com/photo-1615461066841-6116e61058f4?auto=format&fit=crop&w=900&q=80' }}" alt="{{ $campaign['title'] }}">
                                <span class="absolute left-3 top-3 rounded-full bg-red-700 px-3 py-1 text-xs font-extrabold uppercase text-white">{{ $campaign['status'] ?? 'Upcoming' }}</span>
                            </div>
                            <div class="p-5">
                                <h3 class="text-lg font-extrabold">{{ $campaign['title'] }}</h3>
                                <p class="mt-2 line-clamp-3 text-sm leading-6 text-stone-600">{{ $campaign['description'] }}</p>
                                <p class="mt-4 text-xs font-bold text-stone-500">{{ $campaign['date_range'] ?? '' }} - {{ $campaign['locations'] ?? '' }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="card p-7">No campaigns posted yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
</div>
@endsection
