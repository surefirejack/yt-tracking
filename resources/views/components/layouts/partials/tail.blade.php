@stack('tail')

{{-- Load main JavaScript bundle with optimization --}}
@vite(['resources/js/app.js'])

{{-- Preload critical components for better performance --}}
@if(app()->environment('production'))
    <link rel="modulepreload" href="{{ Vite::asset('resources/js/components.js') }}">
@endif

@include('components.layouts.partials.analytics')

@php($skipCookieContentBar = $skipCookieContentBar ?? false)

@if (!$skipCookieContentBar)
    @include('cookie-consent::index')
@endif

@livewireScripts
