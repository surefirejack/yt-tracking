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
                        color="gray"
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
                            icon="heroicon-o-chart-bar"
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
                        Choose a destination URL to see which of your videos and links are sending the most traffic and generating the best conversion rates.
                    </p>
                    <a href="{{ \App\Filament\Dashboard\Resources\AnalyticsResource::getUrl('url-performance') }}">
                        <x-filament::button
                            color="primary"
                            size="sm"
                            icon="heroicon-o-chart-bar"
                        >
                            View URL Analytics
                        </x-filament::button>
                    </a>
                </div>
            </div>
        </div>

        {{-- Analytics Features Summary --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-start">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 mt-0.5" />
                <div>
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Analytics Dashboard Features
                    </h4>
                    <div class="text-sm text-blue-700 dark:text-blue-300 mt-2 space-y-1">
                        <p>• <strong>Video Performance:</strong> Track clicks, leads, sales, and revenue for each YouTube video</p>
                        <p>• <strong>URL Performance:</strong> Analyze which links and videos drive traffic to your destination URLs</p>
                        <p>• <strong>Time Filtering:</strong> View data for different time periods (24h, 7d, 30d, 90d, 1y, etc.)</p>
                        <p>• <strong>Cross-Reference Analysis:</strong> See the relationship between your videos and destination URLs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page> 