@extends('layouts.subscriber')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Content Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">
            {{ $content->title }}
        </h1>
        
        <!-- Content Meta -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-600 mb-6">
            <div class="flex items-center">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="hidden sm:inline">Published </span>{{ $content->created_at->format('M j, Y') }}
            </div>

            @if($content->file_paths && count($content->file_paths) > 0)
                <div class="flex items-center">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"></path>
                    </svg>
                    {{ count($content->file_paths) }} {{ Str::plural('download', count($content->file_paths)) }}
                </div>
            @endif
        </div>
    </div>

    <!-- File Downloads Section - Full Width -->
    @if($content->file_paths && count($content->file_paths) > 0)
        <div class="mb-8">
            <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-xl p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($content->file_paths as $index => $filePath)
                        @php
                            $filename = basename($filePath);
                            
                            // Use human-readable filename if available, otherwise use cleaned up filename
                            $displayName = $filename;
                            if ($content->file_names && isset($content->file_names[$index])) {
                                $displayName = $content->file_names[$index];
                            } else {
                                // Remove timestamp prefix if present (e.g., 20250611063000_original.pdf -> original.pdf)
                                $displayName = preg_replace('/^\d{14}_/', '', $filename);
                            }
                            
                            $extension = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
                            $filesize = null;
                            
                            // Try to get file size
                            if (Storage::exists($filePath)) {
                                $filesize = Storage::size($filePath);
                            }
                            
                            // Get file icon based on extension
                            $iconClass = match($extension) {
                                'PDF' => 'text-red-600',
                                'ZIP', 'RAR' => 'text-purple-600',
                                'JPG', 'JPEG', 'PNG', 'GIF' => 'text-blue-600',
                                default => 'text-gray-600'
                            };
                        @endphp
                        
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                            <div class="flex flex-col space-y-3">
                                <div class="flex items-center min-w-0 flex-1">
                                    <div class="flex-shrink-0 mr-3">
                                        <svg class="w-8 h-8 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $displayName }}
                                        </p>
                                        
                                        <div class="flex items-center text-xs text-gray-500 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-800 text-xs font-medium rounded-full mr-2">
                                                {{ $extension }}
                                            </span>
                                            
                                            @if($filesize)
                                                <span>{{ number_format($filesize / 1024 / 1024, 2) }} MB</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="w-full">
                                    <a href="{{ route('subscriber.download', ['channelname' => $channelname, 'slug' => $content->slug, 'filename' => $filename]) }}" 
                                       class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-500 focus:ring-opacity-25 transition-all duration-200 group"
                                       download>
                                        <svg class="w-4 h-4 mr-2 group-hover:translate-y-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"></path>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content Area - Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column (2/3) - Content Text -->
        <div class="lg:col-span-2">
            <!-- Content Body -->
            @if($content->content)
                <div class="mb-8">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-8">
                            <div class="prose prose-lg max-w-none prose-headings:text-gray-900 prose-p:text-gray-700 prose-strong:text-gray-900 prose-em:text-gray-700 prose-blockquote:text-gray-800 prose-blockquote:border-blue-500 prose-a:text-blue-600 hover:prose-a:text-blue-800 prose-ul:text-gray-700 prose-ol:text-gray-700 prose-li:text-gray-700">
                                <style>
                                    .prose p {
                                        margin-bottom: 1.25rem !important;
                                    }
                                </style>
                                {!! $content->content !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column (1/3) - YouTube Video -->
        <div class="lg:col-span-1">
            @if($content->youtube_video_url)
                @php
                    // Extract YouTube video ID and convert to embed URL
                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $content->youtube_video_url, $matches);
                    $videoId = $matches[1] ?? null;
                @endphp
                
                @if($videoId)
                    <div class="mb-8">
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                            <div class="aspect-video">
                                <iframe 
                                    src="https://www.youtube.com/embed/{{ $videoId }}?rel=0&modestbranding=1" 
                                    title="{{ $videoTitle ?? $content->title }}"
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                    allowfullscreen
                                    class="w-full h-full">
                                </iframe>
                            </div>
                            @if($videoTitle)
                                <div class="p-4">
                                    <h3 class="text-sm font-medium text-gray-900">
                                        {{ $videoTitle }}
                                    </h3>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Content Actions -->
    <div class="mb-8">
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 sm:p-6">
            <div class="flex flex-col gap-4 justify-between items-start">
                <div class="text-center sm:text-left w-full sm:w-auto">
                    <h4 class="font-semibold text-gray-900 mb-2">Enjoying this content?</h4>
                    <p class="text-sm text-gray-600">
                        Check out more exclusive content on the dashboard or visit the YouTube channel.
                    </p>
                </div>
                
                <div class="flex flex-col w-full sm:w-auto sm:flex-row gap-3">
                    <a href="{{ route('subscriber.dashboard', ['channelname' => $channelname]) }}" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-opacity-25 transition-all duration-200 text-sm sm:text-base">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v1H8V5z"></path>
                        </svg>
                        More Content
                    </a>
                    
                    @if($tenant->ytChannel?->channel_url)
                        <a href="{{ $tenant->ytChannel->channel_url }}" 
                           target="_blank"
                           class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500 focus:ring-opacity-25 transition-all duration-200 text-sm sm:text-base">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                            YouTube Channel
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Related Content (if available) -->
    @if($relatedContent && $relatedContent->count() > 0)
        <div class="mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">More Exclusive Content</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($relatedContent as $related)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200 group">
                        <div class="relative h-32 bg-gradient-to-br from-blue-400 to-purple-500">
                            @if($related->youtube_video_url)
                                @php
                                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $related->youtube_video_url, $matches);
                                    $relatedVideoId = $matches[1] ?? null;
                                @endphp
                                
                                @if($relatedVideoId)
                                    <img 
                                        src="https://img.youtube.com/vi/{{ $relatedVideoId }}/hqdefault.jpg" 
                                        alt="{{ $related->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    >
                                @endif
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <div class="p-4">
                            <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors duration-200">
                                {{ $related->title }}
                            </h4>
                            
                            @if($related->content)
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    {{ Str::limit(strip_tags($related->content), 80) }}
                                </p>
                            @endif
                            
                            <a href="{{ route('subscriber.content', ['channelname' => $channelname, 'slug' => $related->slug]) }}" 
                               class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors duration-200">
                                Read More
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Track file downloads
    document.addEventListener('DOMContentLoaded', function() {
        const downloadLinks = document.querySelectorAll('a[href*="download"]');
        
        downloadLinks.forEach(link => {
            link.addEventListener('click', function() {
                // You could add analytics tracking here
                console.log('File download initiated:', this.href);
            });
        });
    });
</script>
@endpush
@endsection 