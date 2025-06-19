@extends('layouts.public')

@section('title', 'Email Verified Successfully! - ' . ($tenant->ytChannel?->title ?? $tenant->name))
@section('description', 'Your email has been verified successfully. You now have access to exclusive content.')

@push('head')
<meta name="robots" content="noindex, nofollow">
<style>
    /* Dynamic accent color styling */
    :root {
        --accent-color: {{ $tenant->subscriber_accent_color ?? '#3b82f6' }};
        --accent-color-hover: {{ $tenant->subscriber_accent_color ? 'color-mix(in srgb, ' . $tenant->subscriber_accent_color . ' 85%, black 15%)' : '#2563eb' }};
        --accent-color-light: {{ $tenant->subscriber_accent_color ? 'color-mix(in srgb, ' . $tenant->subscriber_accent_color . ' 10%, white 90%)' : '#dbeafe' }};
        --success-color: #10b981;
        --success-color-light: #d1fae5;
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
    
    .success-bg {
        background-color: var(--success-color);
    }
    
    .success-bg-light {
        background-color: var(--success-color-light);
    }
    
    .gradient-success {
        background: linear-gradient(135deg, var(--success-color), #059669);
    }
</style>
<!-- Dub Conversion Tracking for Email Verification -->
<script>
window.DubConversionConfig = {
    eventName: 'Email Subscriber - Email Verification',
    eventQuantity: 1,
    metadata: {
        content_title: '{{ $content->title }}',
        content_slug: '{{ $content->slug }}',
        channel_name: '{{ $channelname }}',
        funnel_step: 'email_verification'
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
<!-- Channel Banner Header -->
@if($tenant->ytChannel?->banner_image_url)
    <div class="w-full h-32 md:h-48 lg:h-64 bg-gradient-to-r from-green-600 to-emerald-600 relative overflow-hidden">
        <img 
            src="{{ $tenant->ytChannel->banner_image_url }}" 
            alt="{{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }} Channel Banner"
            class="w-full h-full object-cover"
            loading="lazy"
        >
        <div class="absolute inset-0 bg-green-600 bg-opacity-30"></div>
        
        <!-- Channel Info Overlay -->
        <div class="absolute bottom-4 left-4 right-4 text-white">
            <div class="flex items-center space-x-4">
                @if($tenant->member_profile_image)
                    <img 
                        src="{{ Storage::url($tenant->member_profile_image) }}" 
                        alt="{{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}"
                        class="w-12 h-12 md:w-16 md:h-16 rounded-full border-4 border-white shadow-lg object-cover"
                    >
                @endif
                <div>
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold drop-shadow-lg">
                        {{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}
                    </h1>
                    <p class="text-sm md:text-base opacity-90 drop-shadow">
                        ✅ Email Verified Successfully!
                    </p>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Fallback Header without Banner -->
    <div class="w-full gradient-success py-8 md:py-12">
        <div class="container mx-auto px-4">
            <div class="flex items-center space-x-4 text-white">
                @if($tenant->member_profile_image)
                    <img 
                        src="{{ Storage::url($tenant->member_profile_image) }}" 
                        alt="{{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}"
                        class="w-12 h-12 md:w-16 md:h-16 rounded-full border-4 border-white shadow-lg object-cover"
                    >
                @endif
                <div>
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold">
                        {{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}
                    </h1>
                    <p class="text-sm md:text-base opacity-90">
                        ✅ Email Verified Successfully!
                    </p>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Navigation Bar -->
<div class="bg-white shadow-sm border-b">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- Breadcrumb / Navigation -->
            <nav class="flex items-center space-x-2 text-sm text-gray-600 overflow-hidden">
                <a href="{{ url('/') }}" 
                   class="hover:text-blue-600 transition-colors duration-200 flex items-center accent-text">
                    <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="hidden sm:inline">Home</span>
                </a>
                <span class="text-gray-400 hidden sm:inline">/</span>
                <span class="text-gray-900 font-medium truncate max-w-xs">{{ $content->title }}</span>
                <span class="text-gray-400 hidden sm:inline">/</span>
                <span class="text-green-600 font-medium">Verified</span>
            </nav>

            <!-- User Actions -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <div class="text-sm text-green-600">
                    <span class="hidden sm:inline">Access Granted</span>
                    <span class="sm:hidden">✅</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-6 sm:py-8">
    <div class="max-w-2xl mx-auto">
        
        <!-- Success Card -->
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            
            <!-- Header -->
            <div class="gradient-success text-white p-8 text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
                <h2 class="text-3xl font-bold mb-2">✅ Email Verified!</h2>
                <p class="text-lg opacity-90">You now have access to exclusive content</p>
            </div>

            <!-- Main Content -->
            <div class="p-8 text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">
                    Welcome to: {{ $content->title }}
                </h3>
                
                <p class="text-gray-600 mb-8">
                    {{ $message ?? 'Your email has been verified and you now have access to exclusive content!' }}
                </p>

                @php
                    $verificationRequest = \App\Models\EmailVerificationRequest::where('content_id', $content->id)
                        ->where('tenant_id', $tenant->id)
                        ->whereNotNull('verified_at')
                        ->latest('verified_at')
                        ->first();
                    $hasESPError = $verificationRequest && $verificationRequest->esp_error;
                @endphp

                <!-- Success Checklist -->
                <div class="success-bg-light rounded-lg p-6 mb-8">
                    <h4 class="font-semibold text-green-900 mb-4">✨ What just happened:</h4>
                    <div class="space-y-3 text-left">
                        <div class="flex items-center text-green-800">
                            <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Email address verified successfully</span>
                        </div>
                        <div class="flex items-center {{ $hasESPError ? 'text-yellow-800' : 'text-green-800' }}">
                            @if($hasESPError)
                                <svg class="w-5 h-5 mr-3 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>Email list signup processing...</span>
                            @else
                                <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Added to {{ $tenant->ytChannel?->title ?? $tenant->name }}'s email list</span>
                            @endif
                        </div>
                        <div class="flex items-center text-green-800">
                            <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Required tag assigned for content access</span>
                        </div>
                        <div class="flex items-center text-green-800">
                            <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Browser access saved for {{ $tenant->email_verification_cookie_duration_days ?? 30 }} days</span>
                        </div>
                    </div>
                </div>

                @if($hasESPError)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                📧 Email List Signup Processing
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>You have immediate access to the content! Your email list signup is being processed in the background and will complete shortly. You'll receive all future updates from {{ $tenant->ytChannel?->title ?? $tenant->name }}.</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Access Button -->
                <div class="space-y-4">
                    <a href="{{ $contentUrl }}" 
                       class="inline-block w-full success-bg hover:bg-green-700 text-white font-bold py-4 px-8 rounded-lg transform hover:scale-105 transition-all duration-200 shadow-lg">
                        🚀 Access Your Content Now
                    </a>
                    
                    <p class="text-sm text-gray-500">
                        You'll be redirected to your exclusive content
                    </p>
                </div>
            </div>

            <!-- Footer Information -->
            <div class="px-8 py-6 bg-gray-50 border-t">
                <div class="text-center space-y-3">
                    <h4 class="font-semibold text-gray-900">🎉 Welcome to the Community!</h4>
                    <p class="text-sm text-gray-600">
                        You'll receive updates about new content and exclusive materials from 
                        {{ $tenant->ytChannel?->title ?? $tenant->name }}.
                    </p>
                    
                    <div class="flex items-center justify-center space-x-6 text-xs text-gray-500 mt-4">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Secure & Encrypted
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            Unsubscribe Anytime
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 accent-text" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Quality Content
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-gray-50 border-t mt-12">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center text-sm text-gray-600">
            <p>Powered by {{ config('app.name') }} • Building better connections between creators and fans</p>
        </div>
    </div>
</footer>

<!-- Auto-redirect script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-redirect after 5 seconds
    setTimeout(function() {
        const contentUrl = '{{ $contentUrl }}';
        if (contentUrl) {
            window.location.href = contentUrl;
        }
    }, 5000);
    
    // Update countdown
    let countdown = 5;
    const countdownInterval = setInterval(function() {
        countdown--;
        const button = document.querySelector('a[href="{{ $contentUrl }}"]');
        if (button && countdown > 0) {
            button.textContent = `🚀 Access Your Content Now (${countdown}s)`;
        } else if (countdown <= 0) {
            clearInterval(countdownInterval);
            if (button) {
                button.textContent = '🚀 Redirecting...';
            }
        }
    }, 1000);
});
</script>
@endsection 