@props(['elementType' => 'a'])

@php
    $class = 'inline-block drop-shadow-xl cursor-pointer leading-6 focus:ring-4 focus:outline-none rounded-lg text-sm font-semibold px-4 py-2 text-center transition hover:scale-103 ';
@endphp

@if($elementType === 'a')
<a
    {{ $attributes->merge(['class' => $class]) }}
    {{ $attributes }}
>
    {{ $slot }}
</a>
@else
<button
    {{ $attributes->merge(['class' => $class]) }}
    {{ $attributes }}
>
    {{ $slot }}
</button>
@endif
