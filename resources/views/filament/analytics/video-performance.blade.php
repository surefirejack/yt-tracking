<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-1">
            <nav class="flex space-x-1" aria-label="Analytics Views">
                {{-- Overview Tab --}}
                <a 
                    href="{{ \App\Filament\Dashboard\Resources\AnalyticsResource::getUrl('index') }}"
                    class="px-4 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <x-heroicon-o-chart-bar-square class="w-4 h-4 inline mr-2" />
                    Overview
                </a>
                
                {{-- Video Performance Tab (Active) --}}
                <span class="px-4 py-2 text-sm font-medium rounded-md bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 border border-blue-200 dark:border-blue-700">
                    <x-heroicon-o-play class="w-4 h-4 inline mr-2" />
                    Video Performance
                    <span class="ml-2 px-2 py-0.5 text-xs bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-full">Current</span>
                </span>
                
                {{-- URL Performance Tab --}}
                <a 
                    href="{{ \App\Filament\Dashboard\Resources\AnalyticsResource::getUrl('url-performance') }}"
                    class="px-4 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <x-heroicon-o-link class="w-4 h-4 inline mr-2" />
                    URL Performance
                </a>
            </nav>
        </div>
    </div>

    <div class="space-y-6">
        @if($this->selectedVideoId && $selectedVideo = $this->getSelectedVideo())
            {{-- Selected Video Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-start space-x-4">
                    @if($selectedVideo->thumbnail_url)
                        <img 
                            src="{{ $selectedVideo->thumbnail_url }}" 
                            alt="Video thumbnail"
                            class="w-32 h-18 object-cover rounded-lg flex-shrink-0"
                        >
                    @endif
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            {{ $selectedVideo->title }}
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <div>
                                <span class="font-medium">Published:</span><br>
                                {{ $selectedVideo->published_at?->format('M j, Y') ?? 'N/A' }}
                            </div>
                            <div>
                                <span class="font-medium">Views:</span><br>
                                {{ number_format($selectedVideo->views ?? 0) }}
                            </div>
                            <div>
                                <span class="font-medium">Likes:</span><br>
                                {{ number_format($selectedVideo->likes ?? 0) }}
                            </div>
                            <div>
                                <span class="font-medium">Time Period:</span><br>
                                {{ \App\Enums\AnalyticsInterval::from($this->selectedInterval)->label() }}
                            </div>
                        </div>
                        @if($selectedVideo->url)
                            <div class="mt-3">
                                <a 
                                    href="{{ $selectedVideo->url }}" 
                                    target="_blank"
                                    class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    <x-heroicon-o-play class="w-4 h-4 mr-1" />
                                    View on YouTube
                                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3 ml-1" />
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($this->processedMetrics)
                {{-- Key Metrics Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Total Clicks --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                                <x-heroicon-o-cursor-arrow-ripple class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Clicks</h4>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($this->processedMetrics['total_clicks']) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Total Leads --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                                <x-heroicon-o-user-plus class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Leads</h4>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($this->processedMetrics['total_leads']) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $this->processedMetrics['lead_conversion_rate'] }}% conversion
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Total Sales --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
                                <x-heroicon-o-shopping-cart class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sales</h4>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($this->processedMetrics['total_sales']) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $this->processedMetrics['sales_conversion_rate'] }}% conversion
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Revenue --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                                <x-heroicon-o-currency-dollar class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div class="ml-4">
                                <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</h4>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($this->processedMetrics['total_sale_amount'], 2) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    ${{ number_format($this->processedMetrics['revenue_per_click'], 2) }} per click
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Raw Data Table --}}
                @if($this->analyticsData && count($this->analyticsData) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Detailed Analytics Data
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Clicks
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Leads
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Sales
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Revenue
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->analyticsData as $dataPoint)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                @if(isset($dataPoint['date']))
                                                    {{ \Carbon\Carbon::parse($dataPoint['date'])->format('M j, Y') }}
                                                @elseif(isset($dataPoint['start']))
                                                    {{ \Carbon\Carbon::parse($dataPoint['start'])->format('M j, Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ number_format($dataPoint['clicks'] ?? 0) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ number_format($dataPoint['leads'] ?? 0) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ number_format($dataPoint['sales'] ?? 0) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                ${{ number_format($dataPoint['saleAmount'] ?? 0, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @else
                {{-- Loading State --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="text-center py-8">
                        <x-heroicon-o-arrow-path class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin" />
                        <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                            Loading Analytics Data
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Please wait while we fetch the analytics data for this video...
                        </p>
                    </div>
                </div>
            @endif
        @else
            {{-- No Video Selected --}}
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <x-heroicon-o-play class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                    Select a Video to Begin
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Click "Select Video & Time Period" in the header above to choose a YouTube video and analyze its performance.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page> 