@extends('diamonds.layouts.public')

@section('title', $content->title)

@push('head')
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
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Channel Header -->
        <div class="bg-white rounded-lg shadow-sm mb-8 overflow-hidden">
            @if($tenant->ytChannel?->banner_image_url)
            <div class="h-32 bg-cover bg-center" style="background-image: url('{{ $tenant->ytChannel->banner_image_url }}');">
                <div class="h-full bg-black bg-opacity-30 flex items-center justify-center">
                    <div class="text-center text-white">
                        @if($tenant->ytChannel?->thumbnail_url)
                        <img src="{{ $tenant->ytChannel->thumbnail_url }}" 
                             alt="{{ $tenant->ytChannel->title ?? $tenant->name }}"
                             class="w-16 h-16 rounded-full border-4 border-white shadow-lg mx-auto mb-2">
                        @endif
                        <h1 class="text-xl font-bold">{{ $tenant->ytChannel?->title ?? $tenant->name }}</h1>
                    </div>
                </div>
            </div>
            @else
            <div class="p-6 text-center border-b">
                <div class="flex items-center justify-center mb-4">
                    @if($tenant->ytChannel?->thumbnail_url)
                    <img src="{{ $tenant->ytChannel->thumbnail_url }}" 
                         alt="{{ $tenant->ytChannel->title ?? $tenant->name }}"
                         class="w-16 h-16 rounded-full shadow-lg mr-4">
                    @endif
                    <h1 class="text-2xl font-bold text-gray-900">{{ $tenant->ytChannel?->title ?? $tenant->name }}</h1>
                </div>
            </div>
            @endif
        </div>

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

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <!-- Content Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $content->title }}</h2>
                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.414-1.414L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                Published {{ $content->created_at->format('M j, Y') }}
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                Email Subscriber Content
                            </span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            ✓ Verified Access
                        </span>
                    </div>
                </div>
            </div>

            <!-- Content Body -->
            <div class="px-6 py-8">
                <div class="prose prose-lg max-w-none">
                    {!! nl2br(e($content->content)) !!}
                </div>
            </div>

            <!-- Content Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Your access expires {{ $accessRecord->last_verified_at->addDays($tenant->email_verification_cookie_duration_days ?? 30)->format('M j, Y') }}
                    </div>
                    <div class="text-sm text-gray-500">
                        Subscribed to: {{ $tenant->ytChannel?->title ?? $tenant->name }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Information -->
        <div class="mt-8 bg-blue-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">🚀 What's Next?</h3>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div class="bg-white rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Stay Connected</h4>
                    <p class="text-gray-600">
                        You'll receive emails about new exclusive content and updates from 
                        {{ $tenant->ytChannel?->title ?? $tenant->name }}.
                    </p>
                </div>
                <div class="bg-white rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Future Access</h4>
                    <p class="text-gray-600">
                        Your browser will remember you for {{ $tenant->email_verification_cookie_duration_days ?? 30 }} days, 
                        so you won't need to verify again.
                    </p>
                </div>
            </div>
        </div>

        <!-- Share & Feedback -->
        <div class="mt-6 text-center">
            <div class="inline-flex items-center space-x-4 text-sm text-gray-500">
                <span>Found this valuable?</span>
                <a href="{{ $tenant->ytChannel?->channel_url ?? '#' }}" 
                   target="_blank" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    Visit YouTube Channel
                </a>
                <span>•</span>
                <span>Powered by {{ config('app.name') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Cookie Notice (if first time accessing) -->
@if(!request()->cookie('email_access_notice_shown'))
<div id="cookie-notice" class="fixed bottom-4 right-4 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm z-50">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="ml-3">
            <h4 class="text-sm font-medium text-gray-900">Access Saved</h4>
            <p class="mt-1 text-xs text-gray-500">
                We've saved your access for {{ $tenant->email_verification_cookie_duration_days ?? 30 }} days so you won't need to verify again.
            </p>
            <button onclick="closeCookieNotice()" class="mt-2 text-xs text-blue-600 hover:text-blue-800 font-medium">
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
    // Set a cookie to remember the notice was shown
    document.cookie = 'email_access_notice_shown=1; path=/; max-age=' + (30 * 24 * 60 * 60);
}

// Auto-hide after 10 seconds
setTimeout(closeCookieNotice, 10000);
</script>
@endif
@endsection 