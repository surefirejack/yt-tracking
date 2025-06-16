<x-filament-panels::page>
    {{-- Return to Dashboard Button --}}
    <div class="mb-6">
        <x-filament::button
            tag="a"
            href="{{ route('filament.dashboard.pages.dashboard', ['tenant' => \Filament\Facades\Filament::getTenant()]) }}"
            size="lg"
            color="primary"
            icon="heroicon-o-arrow-left"
        >
            {{ __('Return to Main Dashboard') }}
        </x-filament::button>
    </div>

    {{-- Billing Overview --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Billing Overview') }}
        </x-slot>
        
        <x-slot name="description">
            {{ __('Manage your subscriptions, view payment history, and download invoices.') }}
        </x-slot>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            {{-- Subscriptions Card --}}
            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-fire class="h-6 w-6 text-orange-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ __('Subscriptions') }}
                        </h3>
                        <div class="mt-1">
                            <x-filament::button
                                tag="a"
                                href="{{ route('filament.billing.resources.subscriptions.index', ['tenant' => \Filament\Facades\Filament::getTenant()]) }}"
                                color="primary"
                                size="sm"
                            >
                                {{ __('View Details') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::card>

            {{-- Payments Card --}}
            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-currency-dollar class="h-6 w-6 text-green-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ __('Payments') }}
                        </h3>
                        <div class="mt-1">
                            <x-filament::button
                                tag="a"
                                href="{{ route('filament.billing.resources.transactions.index', ['tenant' => \Filament\Facades\Filament::getTenant()]) }}"
                                color="primary"
                                size="sm"
                            >
                                {{ __('View Details') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::card>

            {{-- Orders Card --}}
            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-rectangle-stack class="h-6 w-6 text-blue-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ __('Orders') }}
                        </h3>
                        <div class="mt-1">
                            <x-filament::button
                                tag="a"
                                href="{{ route('filament.billing.resources.orders.index', ['tenant' => \Filament\Facades\Filament::getTenant()]) }}"
                                color="primary"
                                size="sm"
                            >
                                {{ __('View Details') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        </div>
    </x-filament::section>
</x-filament-panels::page> 