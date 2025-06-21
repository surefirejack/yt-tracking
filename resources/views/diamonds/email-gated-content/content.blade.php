@extends('layouts.subscriber', ['contentTitle' => $content->title, 'hideNavigation' => true])

@push('head')
<style>
    /* Dynamic accent color styling */
    :root {
        --accent-color: {{ $tenant->subscriber_accent_color ?? '#3b82f6' }};
        --accent-color-hover: {{ $tenant->subscriber_accent_color ? 'color-mix(in srgb, ' . $tenant->subscriber_accent_color . ' 85%, black 15%)' : '#2563eb' }};
        --accent-color-light: {{ $tenant->subscriber_accent_color ? 'color-mix(in srgb, ' . $tenant->subscriber_accent_color . ' 10%, white 90%)' : '#dbeafe' }};
    }
    
    .accent-bg {
        background-color: var(--accent-color);
    }
    
    .accent-bg-light {
        background-color: var(--accent-color-light);
    }
    
    .accent-text {
        color: var(--accent-color);
    }
    
    .accent-border {
        border-color: var(--accent-color);
    }
    
    .accent-ring {
        --tw-ring-color: var(--accent-color);
    }
    
    .accent-hover:hover {
        background-color: var(--accent-color-hover);
    }
</style>
<!-- Dub Conversion Tracking for Content Access -->
<script>
window.DubConversionConfig = {
    eventName: 'Email Subscriber - Content Access',
    eventQuantity: 1,
    metadata: {
        content_title: '{{ $content->title }}',
        content_slug: '{{ $content->slug }}',
        channel_name: '{{ $channelname }}',
        funnel_step: 'content_access',
        access_type: 'verified_subscriber'
    }
};
</script>
<script>
(function(d,s,id,domain){
    if(d.getElementById(id)) return;
    var js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
    js.id=id;js.async=true;
    js.src='https://'+domain+'/js/dub-conversion.js';
    fjs.parentNode.insertBefore(js,fjs);
})(document,'script','dub-conversion-js','{{ request()->getHost() }}');
</script>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Access Confirmation Banner -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">🎉 Access Granted!</h3>
                <div class="mt-1 text-sm text-green-700">
                    Welcome to exclusive content from {{ $tenant->ytChannel?->title ?? $tenant->name }}. 
                    You're now subscribed and have access to this content.
                </div>
            </div>
        </div>
    </div>

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

            <div class="flex items-center">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                Email Subscriber Content
            </div>

            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                ✓ Verified Access
            </span>
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
                                    <a href="{{ route('email-gated-content.download', ['channelname' => $channelname, 'slug' => $content->slug, 'filename' => $filename]) }}" 
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

    <!-- Call to Action Video (if available) -->
    @if($ctaVideo)
        <div class="mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Watch This Next</h3>
            
            <div class="max-w-md">
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200 group">
                    <a href="{{ $ctaVideo['url'] }}" target="_blank" class="block">
                        <div class="relative h-48 bg-gradient-to-br from-blue-400 to-purple-500">
                            <img 
                                src="{{ $ctaVideo['thumbnail_url'] }}" 
                                alt="{{ $ctaVideo['title'] }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            >
                            <!-- Play button overlay -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="bg-red-600 rounded-full p-4 group-hover:bg-red-700 transition-colors duration-200 shadow-lg">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-3 text-lg group-hover:text-blue-600 transition-colors duration-200">
                            {{ $ctaVideo['title'] }}
                        </h4>
                        
                        <p class="text-sm text-gray-600 mb-4">
                            Watch this video on {{ $tenant->ytChannel?->title ?? $tenant->name }}'s YouTube channel and discover more great content!
                        </p>
                        
                        <a href="{{ $ctaVideo['url'] }}" 
                           target="_blank"
                           class="inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500 focus:ring-opacity-25 transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                            Watch on YouTube
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
</div>

<!-- Cookie Notice (if first time accessing) -->
@if(!request()->cookie('email_access_notice_shown'))
<div id="cookie-notice" class="fixed bottom-4 right-4 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm z-50">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 accent-text" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="ml-3">
            <h4 class="text-sm font-medium text-gray-900">Access Saved</h4>
            <p class="mt-1 text-xs text-gray-500">
                We've saved your access for {{ $tenant->email_verification_cookie_duration_days ?? 30 }} days so you won't need to verify again.
            </p>
            <button onclick="closeCookieNotice()" class="mt-2 text-xs accent-text hover:opacity-80 font-medium">
                Got it
            </button>
        </div>
        <button onclick="closeCookieNotice()" class="ml-2 text-gray-400 hover:text-gray-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>
</div>

<script>
function closeCookieNotice() {
    document.getElementById('cookie-notice').style.display = 'none';
    
    // Set cookie to remember this notice was shown
    document.cookie = 'email_access_notice_shown=1; path=/; max-age=' + (60*60*24*30); // 30 days
}

// Auto-hide after 10 seconds
setTimeout(function() {
    const notice = document.getElementById('cookie-notice');
    if (notice) {
        notice.style.display = 'none';
    }
}, 10000);
</script>
@endif
@endsection 