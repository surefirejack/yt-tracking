@extends('layouts.public')

@section('title', 'Verification Link Expired' . ($tenant ? ' - ' . ($tenant->ytChannel?->title ?? $tenant->name) : ''))
@section('description', 'This verification link has expired. Request a new one to access your exclusive content.')

@push('head')
<meta name="robots" content="noindex, nofollow">
<style>
    /* Dynamic accent color styling */
    :root {
        --accent-color: {{ $tenant?->subscriber_accent_color ?? '#3b82f6' }};
        @if($tenant?->subscriber_accent_color)
            @if(str_starts_with($tenant->subscriber_accent_color, 'rgb'))
                --accent-color-hover: {{ str_replace('rgb(', 'rgba(', rtrim($tenant->subscriber_accent_color, ')')) }}, 0.85);
                --accent-color-light: {{ str_replace('rgb(', 'rgba(', rtrim($tenant->subscriber_accent_color, ')')) }}, 0.1);
            @else
                --accent-color-hover: color-mix(in srgb, {{ $tenant->subscriber_accent_color }} 85%, black 15%);
                --accent-color-light: color-mix(in srgb, {{ $tenant->subscriber_accent_color }} 10%, white 90%);
            @endif
        @else
            --accent-color-hover: #2563eb;
            --accent-color-light: #dbeafe;
        @endif
        --error-color: #ef4444;
        --error-color-light: #fee2e2;
        --warning-color: #f59e0b;
        --warning-color-light: #fef3c7;
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
    
    .error-bg {
        background-color: var(--error-color);
    }
    
    .error-bg-light {
        background-color: var(--error-color-light);
    }
    
    .warning-bg-light {
        background-color: var(--warning-color-light);
    }
    
    .gradient-error {
        background: linear-gradient(135deg, var(--error-color), #dc2626);
    }
</style>
@endpush

@section('content')
<!-- DEBUG: View updated at {{ now() }} -->
<!-- Channel Banner Header -->
@if($tenant?->ytChannel?->banner_image_url)
    <div class="w-full h-32 md:h-48 lg:h-64 bg-gradient-to-r from-blue-600 to-purple-600 relative overflow-hidden">
        <img 
            src="{{ $tenant->ytChannel->banner_image_url }}" 
            alt="{{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }} Channel Banner"
            class="w-full h-full object-cover"
            loading="lazy"
        >
        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
        
        <!-- Channel Info Overlay -->
        <div class="absolute bottom-4 left-4 right-4 text-white">
            <div class="flex items-center space-x-4">
                @if($tenant?->member_profile_image)
                    <img 
                        src="{{ Storage::url($tenant->member_profile_image) }}" 
                        alt="{{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}"
                        class="w-12 h-12 md:w-16 md:h-16 rounded-full border-4 border-white shadow-lg object-cover"
                    >
                @endif
                <div>
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold drop-shadow-lg">
                        {{ $tenant?->ytChannel?->name ?? $tenant?->name ?? 'Creator' }}
                    </h1>
                    <p class="text-sm md:text-base opacity-90 drop-shadow">
                        ⏰ Verification Link Expired
                    </p>
                </div>
            </div>
        </div>
    </div>
@elseif($tenant)
    <!-- Fallback Header without Banner -->
    <div class="w-full bg-gradient-to-r from-blue-600 to-purple-600 py-8 md:py-12">
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
                        ⏰ Verification Link Expired
                    </p>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Generic Header when no tenant context -->
    <div class="w-full bg-gradient-to-r from-blue-600 to-purple-600 py-8 md:py-12">
        <div class="container mx-auto px-4">
            <div class="flex items-center space-x-4 text-white">
                <div>
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold">
                        Email Verification
                    </h1>
                    <p class="text-sm md:text-base opacity-90">
                        ⏰ Verification Link Expired
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
                <span class="text-red-600 font-medium">Link Expired</span>
            </nav>

            <!-- User Actions -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <div class="text-sm text-red-600">
                    <span class="hidden sm:inline">Expired</span>
                    <span class="sm:hidden">⏰</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-6 sm:py-8">
    <div class="max-w-2xl mx-auto">
        
        <!-- Error Card -->
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            
            <!-- Header -->
            <div class="accent-bg text-white p-8 text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
                <h2 class="text-3xl font-bold mb-2">⏰ Link Expired</h2>
                <p class="text-lg opacity-90">This verification link is no longer valid</p>
            </div>

            <!-- Main Content -->
            <div class="p-8 text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">
                    Oops! Something went wrong
                </h3>
                
                <div class="warning-bg-light border border-orange-200 rounded-lg p-6 mb-8">
                    <div class="flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <p class="text-orange-800 font-medium mb-2">
                        {{ $message ?? 'This verification link has expired or is invalid.' }}
                    </p>
                    <p class="text-orange-700 text-sm">
                        Verification links expire after 2 hours for security reasons.
                    </p>
                </div>

                <!-- What you can do -->
                <div class="accent-bg-light rounded-lg p-6 mb-8">
                    <h4 class="font-semibold text-gray-900 mb-4">💡 What you can do:</h4>
                    <div class="space-y-3 text-left">
                        <div class="flex items-center text-gray-800">
                            <svg class="w-5 h-5 mr-3 accent-text" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                            </svg>
                            <span>Go back to the content page and request a new verification email</span>
                        </div>
                        <div class="flex items-center text-gray-800">
                            <svg class="w-5 h-5 mr-3 accent-text" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            <span>Check your email for a more recent verification link</span>
                        </div>
                        <!-- <div class="flex items-center text-gray-800">
                            <svg class="w-5 h-5 mr-3 accent-text" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            <span>Contact support if you continue to have issues</span>
                        </div> -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-12 py-12">
                    <button onclick="history.back()" 
                            class="w-full accent-bg accent-hover text-white font-bold py-4 px-8 rounded-lg transform hover:scale-105 transition-all duration-200 shadow-lg">
                        ← Go Back & Try Again
                    </button>
                    
                    
                </div>
            </div>

            <!-- Help Information -->
            <div class="px-8 py-6 bg-gray-50 border-t">
            <div class="container mx-auto px-4">
        <div class="text-center text-sm text-gray-600">
            <p>Powered by <a href="https://videostats.ai" target="_blank">{{ config('app.name') }}</a> • The ultimate software for growing your YouTube channel</p>
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
            <p>Powered by <a href="https://videostats.ai" target="_blank">{{ config('app.name') }}</a> • The ultimate software for growing your YouTube channel</p>
        </div>
    </div>
</footer>

<!-- Auto-refresh warning -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Warn user if they try to refresh/reload this page
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = 'This link has already expired. Refreshing will not help. Go back to request a new verification email.';
    });
});
</script>
@endsection 