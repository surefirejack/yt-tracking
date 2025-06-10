{{-- Dynamic Asset Prefetching Based on Context --}}
@props(['pageType' => 'default'])

@if(app()->environment('production'))
    
    {{-- Prefetch assets based on current route and page type --}}
    @if(request()->routeIs('blog.*'))
        <link rel="prefetch" href="{{ Vite::asset('resources/js/blog.js') }}" as="script">
    @endif
    
    @if(request()->routeIs('admin.*') || request()->routeIs('filament.*'))
        <link rel="prefetch" href="{{ Vite::asset('resources/js/admin.js') }}" as="script">
        <link rel="prefetch" href="{{ Vite::asset('resources/css/filament/admin/theme.css') }}" as="style">
    @endif
    
    @auth
        {{-- Prefetch dashboard assets for authenticated users --}}
        <link rel="prefetch" href="{{ Vite::asset('resources/css/filament/dashboard/theme.css') }}" as="style">
        
        {{-- Prefetch analytics assets if user has analytics access --}}
        @if(auth()->user()->can('view-analytics'))
            <link rel="prefetch" href="{{ Vite::asset('resources/js/analytics-charts.js') }}" as="script">
        @endif
    @endauth
    
    {{-- Prefetch critical images and assets --}}
    <link rel="prefetch" href="{{ asset('images/favicon.ico') }}" as="image">
    
    {{-- Prefetch common UI components --}}
    @if($pageType === 'interactive')
        <link rel="modulepreload" href="{{ Vite::asset('resources/js/components.js') }}">
    @endif
    
@endif

{{-- Performance hints for better loading --}}
@production
    <meta name="theme-color" content="#3B82F6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
@endproduction 