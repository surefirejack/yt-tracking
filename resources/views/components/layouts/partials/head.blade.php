@php($description = isset($description) ? $description : config('app.description'))
<meta name="description" content="{{ $description }}">

@php($canonical = isset($canonical) ? $canonical : url()->current())
<link rel="canonical" href="{{ $canonical }}">

<title>
    @isset($title)
        {{ $title }} | {{ config('app.name', 'SaaSykit') }}
    @else
        {{ config('app.name', 'SaaSykit') }}
    @endisset
</title>

<link rel="shortcut icon" type="image/x-icon" href="{{asset('images/favicon.ico')}}">

@include('components.layouts.partials.social-cards')

<!-- DNS Prefetch for external resources -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

<!-- Prefetch critical assets -->
@if(app()->environment('production'))
    {{-- Prefetch vendor bundle --}}
    <link rel="prefetch" href="{{ Vite::asset('resources/js/components.js') }}" as="script">
    {{-- Prefetch admin assets if user is authenticated and is admin --}}
    @auth
        @if(auth()->user()->hasRole('admin'))
            <link rel="prefetch" href="{{ Vite::asset('resources/js/admin.js') }}" as="script">
        @endif
    @endauth
@endif

<!-- Scripts -->
@vite(['resources/sass/app.scss'])

<!-- Intelligent Asset Prefetching -->
@include('components.layouts.partials.asset-prefetch', ['pageType' => $pageType ?? 'default'])

@stack('head')

@livewireStyles
