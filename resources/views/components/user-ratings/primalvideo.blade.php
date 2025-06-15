@props(['src', 'alt' => 'rating'])

<img {{ $attributes->merge(['class' => 'object-cover rounded-full border border-white ms-[-10px] w-25 h-25 cursor-pointer hover:scale-110 transition'])  }}
     src="{{ $src }}"
     alt="{{ $alt }}"
/>
