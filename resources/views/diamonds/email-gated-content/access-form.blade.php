@extends('layouts.public')

@section('title', $content->title . ' - ' . ($tenant->ytChannel?->title ?? $tenant->name))
@section('description', 'Get exclusive access to ' . $content->title . ' by joining our email list.')

@push('head')
<meta name="robots" content="noindex, nofollow">
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
    
    .gradient-accent {
        background: linear-gradient(135deg, var(--accent-color), color-mix(in srgb, var(--accent-color) 80%, #6366f1 20%));
    }
</style>
@endpush

@section('content')
<!-- Channel Banner Header -->
@if($tenant->ytChannel?->banner_image_url)
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
                        Exclusive Email Content Access
                    </p>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Fallback Header without Banner -->
    <div class="w-full gradient-accent py-8 md:py-12">
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
                        Exclusive Email Content Access
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
            </nav>

            <!-- User Actions -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <div class="text-sm text-gray-500">
                    <span class="hidden sm:inline">Exclusive Content</span>
                    <span class="sm:hidden">🔒</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-6 sm:py-8">
    <div class="max-w-2xl mx-auto">
        
        <!-- Video Thumbnail (if from YouTube) -->
        @if($videoThumbnail && $videoTitle)
        <div class="mb-6 accent-bg-light rounded-lg p-6 border accent-border">
            <div class="flex items-center space-x-4">
                <img src="{{ $videoThumbnail }}" 
                     alt="{{ $videoTitle }}"
                     class="w-24 h-18 rounded-lg object-cover shadow-md">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Coming from YouTube video:</p>
                    <p class="font-semibold text-gray-900">{{ $videoTitle }}</p>
                    <p class="text-xs accent-text mt-1">📺 Thanks for watching!</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            
            <!-- Header -->
            <div class="gradient-accent text-white p-8 text-center">
                <div class="mb-4">
                    <h2 class="text-3xl font-bold mb-4">
                        🔓 You're About to Get Access to
                    </h2>
                    <h3 class="text-xl font-semibold mb-2">{{ $content->title }}</h3>
                    
                    @if($tagName && $tagName !== $content->required_tag_id)
                    <p class="text-sm opacity-90">
                        Required tag: <span class="font-medium">{{ $tagName }}</span>
                    </p>
                    @endif
                </div>
            </div>

            <!-- Content Access Form -->
            <div class="p-8">
                <!-- Email Form -->
                <form id="email-access-form" onsubmit="return false;" class="space-y-6">
                    @csrf
                    <input type="hidden" name="utm_content" value="{{ $utmContent }}">
                    
                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Enter your email to get instant access
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required
                               placeholder="yourbestemail@gmail.com"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 accent-ring focus:border-transparent text-lg">
                        <p class="mt-2 text-sm text-gray-500">
                            We'll send you a quick email to verify your address
                        </p>
                    </div>
                    <br />

                    <!-- Submit Button -->
                    <button type="button" 
                            id="submit-btn"
                            class="w-full accent-bg accent-hover font-bold py-4 px-6 rounded-lg text-white transform hover:scale-105 transition-all duration-200 shadow-lg">
                        <span id="btn-text">✨ Get Instant Access</span>
                        <span id="btn-loading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending verification email...
                        </span>
                    </button>

                    <!-- Subscription Agreement -->
                    <div class="accent-bg-light rounded-lg p-4">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" 
                                   id="subscribe_agreed" 
                                   name="subscribe_agreed" 
                                   required
                                   checked
                                   class="mt-1 h-4 w-4 accent-text focus:ring-2 accent-ring border-gray-300 rounded">
                            <div class="text-sm">
                                <span class="font-medium text-gray-900">
                                    Yes, I want to join {{ $tenant->ytChannel?->title ?? $tenant->name }}'s email list
                                </span>
                                <p class="text-gray-600 mt-1">
                                    Get exclusive content, updates, and valuable insights delivered to your inbox. 
                                    You can unsubscribe at any time.
                                </p>
                            </div>
                        </label>
                    </div>
                </form>

                <!-- Success/Error Messages -->
                <div id="message-container" class="mt-6 hidden">
                    <div id="success-message" class="hidden bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800" id="success-title">Check your email!</h3>
                                <div class="mt-1 text-sm text-green-700" id="success-text">
                                    We've sent you a verification link. Click it to get instant access to your content.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="error-message" class="hidden bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">There was a problem</h3>
                                <div class="mt-1 text-sm text-red-700" id="error-text">
                                    Please try again or contact support if the problem persists.
                                </div>
                            </div>
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
            <p>Powered by {{ config('app.name') }} • The ultimate software for growing your YouTube channel</p>
        </div>
    </div>
</footer>

<!-- Modal for Agreement Explanation -->
<div id="agreement-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 m-4 max-w-md">
        <h3 class="text-lg font-semibold mb-4">Why Join Our Email List?</h3>
        <p class="text-gray-600 mb-4">
            To get the content, you need to join the {{ $tenant->ytChannel?->title ?? $tenant->name }} email list. 
        </p>
        <p class="text-gray-600 mb-4">
            When you join, in addition to getting the content, you'll receive:
        </p>
        <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
            <li>Exclusive content</li>
            <li>Valuable insights and tips</li>
            <li>Early access to new content</li>
            <li>And more!</li>
        </ul>
        <p class="text-gray-600 mb-4">
            You can unsubscribe at any time using the link in any email we send you.
        </p>
        <div class="flex justify-end space-x-3">
            <button onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
            <button onclick="acceptAndCloseModal()" class="accent-bg text-white px-4 py-2 rounded-lg accent-hover">I Understand</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('email-access-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const messageContainer = document.getElementById('message-container');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const subscribeCheckbox = document.getElementById('subscribe_agreed');

    // Handle checkbox unchecking
    subscribeCheckbox.addEventListener('change', function() {
        if (!this.checked) {
            showAgreementModal();
        }
    });

    function handleSubmit() {
        if (!subscribeCheckbox.checked) {
            showAgreementModal();
            return false;
        }

        const formData = new FormData(form);
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        
        // Hide previous messages
        messageContainer.classList.add('hidden');
        successMessage.classList.add('hidden');
        errorMessage.classList.add('hidden');

        fetch('{{ route('email-gated-content.submit-email', ['channelname' => $channelname, 'slug' => $content->slug]) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            messageContainer.classList.remove('hidden');
            
            if (data.success) {
                // Fire analytics event for email entry
                if (typeof window.dubTrackConversion === 'function') {
                    window.dubTrackConversion({
                        eventName: 'Email Subscriber - Email Entry',
                        eventQuantity: 1,
                        customerEmail: formData.get('email'),
                        metadata: {
                            content_title: '{{ $content->title }}',
                            content_slug: '{{ $content->slug }}',
                            channel_name: '{{ $channelname }}',
                            utm_content: '{{ $utmContent }}',
                            esp_status: data.esp_status || 'unknown',
                            funnel_step: 'email_entry',
                            action_type: data.action || 'verification_sent'
                        }
                    });
                }
                
                successMessage.classList.remove('hidden');
                document.getElementById('success-text').textContent = data.message;
                
                if (data.action === 'immediate_access') {
                    // Fire additional event for immediate access
                    if (typeof window.dubTrackConversion === 'function') {
                        window.dubTrackConversion({
                            eventName: 'Email Subscriber - Immediate Access',
                            eventQuantity: 1,
                            customerEmail: formData.get('email'),
                            metadata: {
                                content_title: '{{ $content->title }}',
                                content_slug: '{{ $content->slug }}',
                                channel_name: '{{ $channelname }}',
                                utm_content: '{{ $utmContent }}',
                                funnel_step: 'immediate_access',
                                skip_verification: true
                            }
                        });
                    }
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } else {
                errorMessage.classList.remove('hidden');
                document.getElementById('error-text').textContent = data.message || 'Please try again.';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageContainer.classList.remove('hidden');
            errorMessage.classList.remove('hidden');
            document.getElementById('error-text').textContent = 'An error occurred. Please try again.';
        })
        .finally(() => {
            // Reset loading state
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        });
        
        return false;
    }

    // Handle both form submit and button click
    submitBtn.addEventListener('click', handleSubmit);
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        handleSubmit();
        return false;
    });
});

function showAgreementModal() {
    document.getElementById('agreement-modal').classList.remove('hidden');
    document.getElementById('agreement-modal').classList.add('flex');
}

function closeModal() {
    document.getElementById('agreement-modal').classList.add('hidden');
    document.getElementById('agreement-modal').classList.remove('flex');
    // Re-check the checkbox
    document.getElementById('subscribe_agreed').checked = true;
}

function acceptAndCloseModal() {
    closeModal();
    document.getElementById('subscribe_agreed').checked = true;
}
</script>
@endsection 