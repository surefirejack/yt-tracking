<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Overview Widgets --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->getWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>

        {{-- Content Performance Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Content Performance
            </x-slot>
            
            <x-slot name="description">
                Individual performance metrics for each piece of email-gated content
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Content Title</th>
                            <th class="px-6 py-3">Required Tag</th>
                            <th class="px-6 py-3">Total Requests</th>
                            <th class="px-6 py-3">Verified</th>
                            <th class="px-6 py-3">Conversion Rate</th>
                            <th class="px-6 py-3">Created</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getViewData()['contentAnalytics'] as $content)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $content['title'] }}
                                    <div class="text-xs text-gray-500">/p/{{ Filament::getTenant()->getChannelName() ?? 'channel' }}/{{ $content['slug'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($content['required_tag_id'])
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                            {{ $content['required_tag_id'] }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">None</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
                                        {{ $content['total_requests'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                        {{ $content['verified_requests'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $rate = $content['conversion_rate'];
                                        $colorClass = $rate >= 70 ? 'text-green-600' : ($rate >= 40 ? 'text-yellow-600' : 'text-red-600');
                                    @endphp
                                    <span class="font-semibold {{ $colorClass }}">{{ $rate }}%</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $content['created_at']->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('filament.dashboard.resources.email-subscriber-content.edit', ['tenant' => Filament::getTenant()->uuid, 'record' => $content['id']]) }}" 
                                           class="text-blue-600 hover:text-blue-900 text-xs">
                                            Edit
                                        </a>
                                        <a href="{{ route('email-gated-content.show', ['channelname' => Filament::getTenant()->getChannelName() ?? 'channel', 'slug' => $content['slug']]) }}" 
                                           target="_blank"
                                           class="text-green-600 hover:text-green-900 text-xs">
                                            Preview
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium mb-2">No email-gated content yet</p>
                                        <p class="text-sm">Create your first piece of email-gated content to see analytics here.</p>
                                        <a href="{{ route('filament.dashboard.resources.email-subscriber-content.create', ['tenant' => Filament::getTenant()->uuid]) }}" 
                                           class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                            Create Email-Gated Content
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Tag Performance Section --}}
        @if ($this->getViewData()['tagPerformance']->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                Tag Performance
            </x-slot>
            
            <x-slot name="description">
                Performance metrics grouped by required tags
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->getViewData()['tagPerformance'] as $tagStats)
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $tagStats->required_tag_id }}
                            </h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                Tag
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Content pieces:</span>
                                <span class="text-sm font-medium">{{ $tagStats->content_count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total verifications:</span>
                                <span class="text-sm font-medium text-green-600">{{ $tagStats->total_verifications }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Avg per content:</span>
                                <span class="text-sm font-medium">{{ $tagStats->content_count > 0 ? round($tagStats->total_verifications / $tagStats->content_count, 1) : 0 }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
        @endif

        {{-- Recent Activity --}}
        <x-filament::section>
            <x-slot name="heading">
                Recent Email Verifications
            </x-slot>
            
            <x-slot name="description">
                Latest verified email subscribers across all content
            </x-slot>

            @php
                $recentVerifications = \App\Models\EmailVerificationRequest::where('tenant_id', Filament::getTenant()->id)
                    ->whereNotNull('verified_at')
                    ->with('content')
                    ->orderBy('verified_at', 'desc')
                    ->limit(10)
                    ->get();
            @endphp

            <div class="space-y-3">
                @forelse ($recentVerifications as $verification)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    New verification for "{{ $verification->content->title ?? 'Unknown Content' }}"
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $verification->verified_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $verification->verified_at->format('M j, g:i A') }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <p>No email verifications yet.</p>
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page> 