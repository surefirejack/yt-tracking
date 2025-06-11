@extends('diamonds.layouts.subscriber')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Welcome Section -->
    <div class="mb-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-3">
            Welcome to your exclusive area!
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
            Thanks for being a subscriber! Here's all the exclusive content created just for you.
        </p>
    </div>

    @if($content && $content->count() > 0)
        <!-- Content Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($content as $item)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group">
                    <!-- Card Image/Thumbnail -->
                    <div class="relative h-48 bg-gradient-to-br from-blue-400 to-purple-500 overflow-hidden">
                        @if($item->youtube_video_url)
                            @php
                                // Extract YouTube video ID for thumbnail
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $item->youtube_video_url, $matches);
                                $videoId = $matches[1] ?? null;
                            @endphp
                            
                            @if($videoId)
                                <img 
                                    src="https://img.youtube.com/vi/{{ $videoId }}/maxresdefault.jpg" 
                                    alt="{{ $item->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                    onerror="this.src='https://img.youtube.com/vi/{{ $videoId }}/hqdefault.jpg'"
                                >
                                
                                <!-- Play Button Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 group-hover:bg-opacity-40 transition-all duration-300">
                                    <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-6 h-6 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            @else
                                <!-- Default gradient background -->
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        @else
                            <!-- Default content icon -->
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-white opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        @endif
                        
                        <!-- Content Type Badge -->
                        <div class="absolute top-3 left-3">
                            @if($item->youtube_video_url)
                                <span class="inline-flex items-center px-2 py-1 bg-red-600 text-white text-xs font-medium rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                    Video
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs font-medium rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Content
                                </span>
                            @endif
                        </div>

                        <!-- File Downloads Badge -->
                        @if($item->file_paths && count($item->file_paths) > 0)
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2 py-1 bg-green-600 text-white text-xs font-medium rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 2l-2-2m2 2l2-2m-2-2V6m0 0L8 8m4-2l4 2"></path>
                                    </svg>
                                    {{ count($item->file_paths) }} {{ Str::plural('file', count($item->file_paths)) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Card Content -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2 group-hover:text-blue-600 transition-colors duration-200">
                            {{ $item->title }}
                        </h3>

                        @if($item->content)
                            <div class="text-gray-600 text-sm leading-relaxed mb-4 line-clamp-3">
                                {{ Str::limit(strip_tags($item->content), 120) }}
                            </div>
                        @endif

                        <!-- Metadata -->
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $item->created_at->format('M j, Y') }}
                            </div>
                            
                            @if($item->updated_at != $item->created_at)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Updated {{ $item->updated_at->diffForHumans() }}
                                </div>
                            @endif
                        </div>

                        <!-- Action Button -->
                        <a href="{{ route('subscriber.content', ['channelname' => $channelname, 'slug' => $item->slug]) }}" 
                           class="w-full inline-flex items-center justify-center px-4 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-opacity-25 transition-all duration-200 group">
                            <span class="group-hover:translate-x-0.5 transition-transform duration-200">
                                View Content
                            </span>
                            <svg class="w-4 h-4 ml-2 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination if needed -->
        @if(method_exists($content, 'hasPages') && $content->hasPages())
            <div class="mt-12 flex justify-center">
                {{ $content->links() }}
            </div>
        @endif

    @else
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                
                <h3 class="text-xl font-semibold text-gray-900 mb-3">
                    No content available yet
                </h3>
                
                <p class="text-gray-600 mb-6">
                    {{ $tenant->name ?? 'The creator' }} hasn't published any exclusive content yet. 
                    Check back soon for new updates!
                </p>

                @if($tenant->ytChannel?->channel_url)
                    <a href="{{ $tenant->ytChannel->channel_url }}" 
                       target="_blank"
                       class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500 focus:ring-opacity-25 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        Visit YouTube Channel
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Add loading states for content links
    document.addEventListener('DOMContentLoaded', function() {
        const contentLinks = document.querySelectorAll('a[href*="subscriber.content"]');
        const loadingOverlay = document.getElementById('loading-overlay');
        
        contentLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                    
                    // Hide loading after 5 seconds as fallback
                    setTimeout(() => {
                        loadingOverlay.classList.add('hidden');
                    }, 5000);
                }
            });
        });
    });
</script>
@endpush
@endsection 