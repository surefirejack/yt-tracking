@php
    use Filament\Support\Enums\IconSize;
@endphp

<div class="flex items-center gap-2">
    @if($is_connected)
        <x-filament::badge 
            color="success" 
            icon="heroicon-m-check-circle"
            size="md"
        >
            Connected
        </x-filament::badge>
        
        @if($email)
            <span class="text-sm text-gray-600 dark:text-gray-400">
                ({{ $email }})
            </span>
        @endif
    @else
        <x-filament::badge 
            color="gray" 
            icon="heroicon-m-x-circle"
            size="md"
        >
            Not Connected
        </x-filament::badge>
    @endif
</div> 