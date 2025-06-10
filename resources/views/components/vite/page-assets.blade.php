{{-- Page-Specific Asset Loading Component --}}
@props([
    'assets' => [],
    'preload' => [],
    'defer' => false,
    'async' => false,
    'critical' => false
])

@php
    // Convert single asset to array for consistency
    if (is_string($assets)) {
        $assets = [$assets];
    }
    if (is_string($preload)) {
        $preload = [$preload];
    }
@endphp

{{-- Preload assets for better performance --}}
@if(!empty($preload) && app()->environment('production'))
    @foreach($preload as $asset)
        @if(str_ends_with($asset, '.js'))
            <link rel="modulepreload" href="{{ Vite::asset($asset) }}">
        @elseif(str_ends_with($asset, '.css'))
            <link rel="preload" href="{{ Vite::asset($asset) }}" as="style">
        @endif
    @endforeach
@endif

{{-- Load the actual assets --}}
@if(!empty($assets))
    @if($critical)
        {{-- Critical assets loaded immediately --}}
        @vite($assets)
    @else
        {{-- Non-critical assets can be deferred --}}
        @push($defer ? 'tail' : 'head')
            @vite($assets)
        @endpush
    @endif
@endif 