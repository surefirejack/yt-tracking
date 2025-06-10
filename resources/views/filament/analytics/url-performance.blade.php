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
                
                {{-- Video Performance Tab --}}
                <a 
                    href="{{ \App\Filament\Dashboard\Resources\AnalyticsResource::getUrl('video-performance') }}"
                    class="px-4 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <x-heroicon-o-play class="w-4 h-4 inline mr-2" />
                    Video Performance
                </a>
                
                {{-- URL Performance Tab (Active) --}}
                <span class="px-4 py-2 text-sm font-medium rounded-md bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 border border-green-200 dark:border-green-700">
                    <x-heroicon-o-link class="w-4 h-4 inline mr-2" />
                    URL Performance
                    <span class="ml-2 px-2 py-0.5 text-xs bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 rounded-full">Current</span>
                </span>
            </nav>
        </div>
    </div>

    <div class="space-y-6">
        @if($this->selectedDestinationUrl)
            {{-- Selected Destination URL Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-start space-x-4">
                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg flex-shrink-0">
                        <x-heroicon-o-link class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Destination URL Analytics
                        </h3>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 mb-4">
                            <p class="text-sm font-mono text-gray-800 dark:text-gray-200 break-all">
                                {{ $this->selectedDestinationUrl }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <div>
                                <span class="font-medium">Time Period:</span><br>
                                {{ \App\Enums\AnalyticsInterval::from($this->selectedInterval)->getLabel() }}
                            </div>
                            <div>
                                <span class="font-medium">Links Found:</span><br>
                                {{ count($this->linkBreakdown ?? []) }}
                            </div>
                            <div>
                                <span class="font-medium">Video Associations:</span><br>
                                {{ collect($this->linkBreakdown ?? [])->where('video_title', '!=', 'None')->count() }} of {{ count($this->linkBreakdown ?? []) }}
                            </div>
                        </div>
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

                {{-- Link Breakdown Table --}}
                @if($this->linkBreakdown && count($this->linkBreakdown) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Link Performance Breakdown
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Shows all links pointing to this destination URL, ranked by traffic volume, with their associated YouTube videos.
                        </p>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Rank
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Link Title
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Associated Video
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Conv. Rate
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->linkBreakdown as $index => $linkData)
                                        <tr class="{{ $index === 0 ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                @if($index === 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        #{{ $index + 1 }} Top
                                                    </span>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">#{{ $index + 1 }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                <div class="max-w-xs">
                                                    <p class="font-medium truncate">{{ $linkData['link_title'] }}</p>
                                                    @if($linkData['dub_id'])
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $linkData['dub_id'] }}</p>
                                                    @else
                                                        <p class="text-xs text-red-500">No Dub ID</p>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                <div class="max-w-xs">
                                                    @if($linkData['video_title'] !== 'None')
                                                        <p class="font-medium truncate text-green-600 dark:text-green-400">
                                                            {{ $linkData['video_title'] }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            Video ID: {{ $linkData['video_id'] }}
                                                        </p>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                            No Video
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ number_format($linkData['clicks']) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ number_format($linkData['leads']) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ number_format($linkData['sales']) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                ${{ number_format($linkData['revenue'], 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ number_format($linkData['conversion_rate'], 1) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if(count($this->linkBreakdown) > 5)
                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Showing {{ count($this->linkBreakdown) }} links ranked by click volume
                                </p>
                            </div>
                        @endif
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
                            Please wait while we fetch the analytics data for this destination URL...
                        </p>
                    </div>
                </div>
            @endif
        @else
            {{-- No URL Selected --}}
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <x-heroicon-o-link class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                    Select a Destination URL to Begin
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Click "Select Destination URL & Time Period" in the header above to choose a destination URL and analyze which links and videos are driving traffic to it.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page> 