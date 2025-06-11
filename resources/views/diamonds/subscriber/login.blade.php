@extends('layouts.subscriber')

@section('content')
<!-- Google Fonts and Styles directly in the template -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Reenie+Beanie&display=swap" rel="stylesheet">

<style>
.signature-font {
    font-family: 'Reenie Beanie', 'Brush Script MT', 'Lucida Handwriting', cursive !important;
    font-size: 1.5rem !important;
    line-height: 1.2 !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3) !important;
    font-weight: normal !important;
    letter-spacing: 0.5px !important;
}

/* More specific selector to override any conflicting styles */
div.signature-font,
.signature-font * {
    font-family: 'Reenie Beanie', 'Brush Script MT', 'Lucida Handwriting', cursive !important;
}
</style>

@if(isset($isPreview) && $isPreview)
    <!-- Preview Mode Banner -->
    <div class="mb-6 bg-orange-100 border-l-4 border-orange-500 p-4 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div>
                <p class="text-orange-800 font-medium">Preview Mode</p>
                <p class="text-orange-700 text-sm">This is how your subscribers will see the login page. The login button is disabled in preview mode.</p>
            </div>
        </div>
    </div>
@endif

<div class="max-w-lg mx-auto">
    <!-- Login Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Card Header -->
        @php
            $accentColor = $tenant->subscriber_accent_color ?? '#3b82f6';
            // Create a darker shade for the gradient by adjusting the hex color
            $r = hexdec(substr($accentColor, 1, 2));
            $g = hexdec(substr($accentColor, 3, 2));
            $b = hexdec(substr($accentColor, 5, 2));
            // Darken by 20%
            $r = max(0, $r - 40);
            $g = max(0, $g - 40);
            $b = max(0, $b - 40);
            $darkerColor = sprintf('#%02x%02x%02x', $r, $g, $b);
        @endphp
        <div class="px-8 py-6 text-center" style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $darkerColor }});">
            <div class="flex flex-col items-center space-y-4">
                <div class="text-white">
                    <h2 class="text-2xl font-bold">{{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}</h2>
                    <p class="text-blue-100 text-sm">Subscribers Only Content</p>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="px-8 py-8">
            @if($contentTitle)
                <!-- Content Being Accessed -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center text-blue-800">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2-2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium">You're trying to access:</p>
                            <p class="font-semibold">{{ $contentTitle }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Login Instructions -->
            <div class="text-center mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">
                    Welcome, Subscriber!
                </h3>
            </div>

            <!-- Login Button -->
            <div class="text-center">
                @if(isset($isPreview) && $isPreview)
                    <!-- Disabled Login Button for Preview -->
                    <div class="inline-flex items-center justify-center w-full px-6 py-4 bg-gray-300 border-2 border-gray-300 rounded-lg text-gray-500 font-semibold cursor-not-allowed transition-all duration-200 shadow-md">
                        <!-- Google Logo (Grayed out) -->
                        <svg class="w-6 h-6 mr-3 opacity-50" viewBox="0 0 24 24">
                            <path fill="#9CA3AF" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#9CA3AF" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#9CA3AF" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#9CA3AF" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Continue with Google (Preview)</span>
                    </div>
                @else
                    <!-- Active Login Button -->
                    <a href="{{ $oauthUrl }}" 
                       class="inline-flex items-center justify-center w-full px-6 py-4 bg-white border-2 border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-opacity-25 transition-all duration-200 shadow-md hover:shadow-lg group">
                        <!-- Google Logo -->
                        <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-200">
                            Continue with Google
                        </span>
                    </a>
                @endif
            </div>

            <!-- Security Notice -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-start text-gray-600 text-sm">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0 text-green-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-700">Secure & Private</p>
                        <p>This step is to ensure you are a subscriber to the channel and to give you access to the content.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Footer with Avatar and Login Text -->
        @if($tenant->member_profile_image || $loginText)
            <div class="px-8 py-6" style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $darkerColor }});">
                <div class="flex items-center space-x-3">
                    <!-- Avatar taking up 1/3 width -->
                    @if($tenant->member_profile_image)
                        <div class="w-1/3 flex flex-col items-center">
                            <img 
                                src="{{ Storage::url($tenant->member_profile_image) }}" 
                                alt="{{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}"
                                class="w-16 h-16 rounded-full border-2 border-white shadow-lg object-cover"
                            >
                            <!-- Cursive name below avatar with inline styles as backup -->
                            @if($tenant->member_signature_name)
                                <div class="mt-2 text-white signature-font" 
                                     style="font-family: 'Reenie Beanie', 'Brush Script MT', 'Lucida Handwriting', cursive !important; 
                                            font-size: 1.5rem !important; 
                                            letter-spacing: 0.5px !important;">
                                    {{ $tenant->member_signature_name }}
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    <!-- Login text taking up 2/3 width -->
                    @if($loginText)
                        <div class="w-2/3 text-white">
                            <div class="text-blue-100 leading-relaxed">
                                {!! nl2br(e($loginText)) !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Additional Information -->
    <div class="mt-8 text-center">
        <div class="text-sm text-gray-600">
            <p class="mb-2">Not subscribed to {{ $tenant->ytChannel->name ?? $tenant->name ?? 'this channel' }} yet?</p>
            @if($tenant->ytChannel?->channel_url)
                <a href="{{ $tenant->ytChannel->channel_url }}" 
                   target="_blank" 
                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Subscribe on YouTube
                </a>
            @endif
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
    // Show loading state when clicking login button
    document.addEventListener('DOMContentLoaded', function() {
        const loginButton = document.querySelector('a[href*="auth/google"]');
        const loadingOverlay = document.getElementById('loading-overlay');
        
        if (loginButton && loadingOverlay) {
            loginButton.addEventListener('click', function() {
                loadingOverlay.classList.remove('hidden');
                
                // Hide loading after 10 seconds as fallback
                setTimeout(() => {
                    loadingOverlay.classList.add('hidden');
                }, 10000);
            });
        }
    });
</script>
@endpush
@endsection 