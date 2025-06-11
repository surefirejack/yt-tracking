@extends('layouts.subscriber')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Access Denied Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-red-500 to-pink-500 px-8 py-6 text-center">
            <div class="flex flex-col items-center space-y-4">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2-2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                
                <div class="text-white">
                    <h2 class="text-2xl font-bold">Access Restricted</h2>
                    <p class="text-red-100 text-sm">Subscribers Only Content</p>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="px-8 py-8">
            @if($slug)
                <!-- Content Being Accessed -->
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center text-red-800">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium">You're trying to access exclusive content from:</p>
                            <p class="font-semibold">{{ $tenant->name ?? 'Creator' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Access Denied Message -->
            <div class="text-center mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">
                    Oops! This content is for subscribers only
                </h3>
                
                <div class="text-gray-600 mb-6 leading-relaxed space-y-3">
                    <p>
                        It looks like you're not currently subscribed to 
                        <strong>{{ $tenant->name ?? 'this channel' }}</strong>, 
                        or your subscription status couldn't be verified.
                    </p>
                    
                    <p class="text-sm">
                        To access this exclusive content, you'll need to:
                    </p>
                </div>
            </div>

            <!-- Steps to Access -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h4 class="font-semibold text-blue-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    How to get access:
                </h4>
                
                <div class="space-y-3 text-blue-800">
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</span>
                        <div>
                            <p class="font-medium">Subscribe to the YouTube channel</p>
                            @if($youtubeChannelUrl)
                                <a href="{{ $youtubeChannelUrl }}" 
                                   target="_blank" 
                                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium text-sm mt-1 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Visit {{ $tenant->name ?? 'Channel' }} on YouTube
                                </a>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</span>
                        <p>Wait a few minutes for the subscription to be processed</p>
                    </div>
                    
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</span>
                        <p>Come back and try again!</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
                @if($youtubeChannelUrl)
                    <!-- Subscribe Button -->
                    <a href="{{ $youtubeChannelUrl }}" 
                       target="_blank"
                       class="w-full inline-flex items-center justify-center px-6 py-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500 focus:ring-opacity-25 transition-all duration-200 shadow-md hover:shadow-lg group">
                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-200">
                            Subscribe on YouTube
                        </span>
                    </a>
                @endif

                <!-- Try Again Button -->
                <a href="{{ $tryAgainUrl }}" 
                   class="w-full inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-opacity-25 transition-all duration-200 shadow-sm hover:shadow-md group">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="group-hover:translate-x-0.5 transition-transform duration-200">
                        Try Again
                    </span>
                </a>
            </div>

            <!-- Help Text -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-start text-gray-600 text-sm">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-700 mb-1">Already subscribed?</p>
                        <p>Sometimes it takes a few minutes for YouTube to update subscription status. Try refreshing or waiting a moment before trying again.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Home Link -->
    <div class="mt-6 text-center">
        <a href="{{ route('home') }}" 
           class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors duration-200">
            ← Back to {{ config('app.name', 'Home') }}
        </a>
    </div>
</div>

@push('scripts')
<script>
    // Show loading state when clicking try again button
    document.addEventListener('DOMContentLoaded', function() {
        const tryAgainButton = document.querySelector('a[href*="try-again"]');
        const loadingOverlay = document.getElementById('loading-overlay');
        
        if (tryAgainButton && loadingOverlay) {
            tryAgainButton.addEventListener('click', function() {
                loadingOverlay.classList.remove('hidden');
                
                // Hide loading after 8 seconds as fallback
                setTimeout(() => {
                    loadingOverlay.classList.add('hidden');
                }, 8000);
            });
        }
    });
</script>
@endpush
@endsection 