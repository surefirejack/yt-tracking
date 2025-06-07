<div class="fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        {{ \Filament\Support\Facades\FilamentView::renderHook('header.start') }}

        <div class="fi-header-heading">
            <h1 class="fi-header-title text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                Edit Link
            </h1>
        </div>

        @if(filled($breadcrumbs))
            <nav class="fi-breadcrumbs mb-2 hidden sm:block">
                <ol class="fi-breadcrumbs-list flex flex-wrap items-center gap-x-2">
                    @foreach ($breadcrumbs as $url => $label)
                        <li class="fi-breadcrumbs-item flex gap-x-2">
                            @if (! $loop->last)
                                <a 
                                    href="{{ $url }}" 
                                    class="fi-breadcrumbs-item-label text-sm font-medium text-gray-500 transition duration-75 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    {{ $label }}
                                </a>
                                
                                <svg class="fi-breadcrumbs-item-separator flex h-5 w-5 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <span class="fi-breadcrumbs-item-label text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ $label }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook('header.end') }}
    </div>

    <!-- Header Actions with Unsaved Changes Badge -->
    <div class="fi-header-actions flex shrink-0 items-center gap-3">
        {{ \Filament\Support\Facades\FilamentView::renderHook('header.actions.start') }}
        
        <!-- Unsaved Changes Badge - positioned all the way to the right -->
        <div wire:dirty class="unsaved-changes-badge">
            <x-filament::badge 
                color="warning" 
                icon="heroicon-m-exclamation-triangle"
                size="sm"
            >
                You have unsaved changes
            </x-filament::badge>
        </div>
        
        @if (method_exists($this, 'getHeaderActions'))
            @foreach ($this->getHeaderActions() as $action)
                {{ $action }}
            @endforeach
        @endif
        
        {{ \Filament\Support\Facades\FilamentView::renderHook('header.actions.end') }}
    </div>
</div>

<style>
/* Smooth animation for the unsaved changes badge */
.unsaved-changes-badge {
    animation: fadeInSlide 0.3s ease-in-out;
}

@keyframes fadeInSlide {
    from { 
        opacity: 0; 
        transform: translateX(10px); 
    }
    to { 
        opacity: 1; 
        transform: translateX(0); 
    }
}

/* Ensure badge is only visible when form is dirty */
[wire\:dirty] .unsaved-changes-badge {
    display: block;
}

:not([wire\:dirty]) .unsaved-changes-badge {
    display: none;
}
</style> 