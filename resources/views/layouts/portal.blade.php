@extends('layouts.app', ['title' => $title ?? 'RedCross Portal'])

@push('head')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('body')
@php
    $activePanel = $active ?? $section ?? 'dashboard';
@endphp

<div class="app-shell" data-portal-shell="{{ $portal ?? 'donor' }}">
    @include('partials.sidebar')
    <main class="portal-main">
        @include('partials.topbar', ['heading' => $heading ?? 'Dashboard'])
        <div class="portal-content motion-fade" data-portal-content data-active-panel="{{ $activePanel }}">
            @include('partials.flash')
            <div class="portal-panel is-active" data-portal-panel="{{ $activePanel }}">
                @yield('portal')
            </div>
        </div>
    </main>
    @if (($portal ?? 'donor') === 'donor')
        @include('partials.notification-drawer')
    @endif
</div>
@endsection
