<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Analytics Dashboard
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Analyze your YouTube video and link performance with comprehensive analytics data.
                    </p>
                </div>
                <div class="flex space-x-3">
                    <x-filament::button
                        color="primary"
                        icon="heroicon-o-arrow-path"
                        disabled
                    >
                        Refresh Data
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Dashboard Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Video Performance Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                            <x-heroicon-o-play class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Video Performance
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Analyze individual video analytics
                            </p>
                        </div>
                    </div>
                </div>
                <div class="space-y-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Select any of your YouTube videos to view comprehensive analytics including clicks, leads, sales, and conversion rates for all associated links.
                    </p>
                    <a href="{{ \App\Filament\Dashboard\Resources\AnalyticsResource::getUrl('video-performance') }}">
                        <x-filament::button
                            color="primary"
                            size="sm"
                        >
                            View Video Analytics
                        </x-filament::button>
                    </a>
                </div>
            </div>

            {{-- URL Performance Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                            <x-heroicon-o-link class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                URL Performance
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Analyze destination URL performance
                            </p>
                        </div>
                    </div>
                </div>
                <div class="space-y-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Choose a destination URL to see which of your videos are sending the most traffic and generating the best conversion rates.
                    </p>
                    <x-filament::button
                        color="primary"
                        size="sm"
                        disabled
                    >
                        View URL Analytics
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Coming Soon Notice --}}
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <div class="flex items-center">
                <x-heroicon-o-information-circle class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3" />
                <div>
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Dashboard Under Development
                    </h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        The analytics dashboard is currently being built. Video Performance and URL Performance views will be available soon.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page> 