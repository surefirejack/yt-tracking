@extends('diamonds.layouts.public')

@section('title', 'Get Access to: ' . $content->title)

@push('head')
<!-- Dub Conversion Tracking for Email Gated Content -->
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
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Channel Banner -->
        @if($tenant->ytChannel?->banner_image_url)
        <div class="mb-8 rounded-lg overflow-hidden shadow-lg">
            <img src="{{ $tenant->ytChannel->banner_image_url }}" 
                 alt="{{ $tenant->ytChannel->title ?? $tenant->name }}" 
                 class="w-full h-32 object-cover">
        </div>
        @endif

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-8 text-center">
                <div class="flex items-center justify-center mb-4">
                    @if($tenant->ytChannel?->thumbnail_url)
                    <img src="{{ $tenant->ytChannel->thumbnail_url }}" 
                         alt="{{ $tenant->ytChannel->title ?? $tenant->name }}"
                         class="w-16 h-16 rounded-full border-4 border-white shadow-lg mr-4">
                    @endif
                    <div>
                        <h1 class="text-2xl font-bold">{{ $tenant->ytChannel?->title ?? $tenant->name }}</h1>
                        <p class="opacity-90">Exclusive Content Access</p>
                    </div>
                </div>
            </div>

            <!-- Video Thumbnail (if from YouTube) -->
            @if($videoThumbnail && $videoTitle)
            <div class="p-6 bg-gray-50 border-b">
                <div class="flex items-center space-x-4">
                    <img src="{{ $videoThumbnail }}" 
                         alt="{{ $videoTitle }}"
                         class="w-24 h-18 rounded-lg object-cover shadow-md">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Coming from YouTube video:</p>
                        <p class="font-semibold text-gray-900">{{ $videoTitle }}</p>
                        <p class="text-xs text-blue-600 mt-1">📺 Thanks for watching!</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Content Access Form -->
            <div class="p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">
                        🔓 You're About to Get Access to
                    </h2>
                    <h3 class="text-xl font-semibold text-indigo-600 mb-2">{{ $content->title }}</h3>
                    
                    @if($tagName && $tagName !== $content->required_tag_id)
                    <p class="text-sm text-gray-600">
                        Required tag: <span class="font-medium text-indigo-600">{{ $tagName }}</span>
                    </p>
                    @endif
                </div>

                <!-- Email Form -->
                <form id="email-access-form" class="space-y-6">
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
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg">
                        <p class="mt-2 text-sm text-gray-500">
                            We'll send you a quick email to verify your address
                        </p>
                    </div>

                    <!-- Subscription Agreement -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" 
                                   id="subscribe_agreed" 
                                   name="subscribe_agreed" 
                                   required
                                   checked
                                   class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
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

                    <!-- Submit Button -->
                    <button type="submit" 
                            id="submit-btn"
                            class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold py-4 px-6 rounded-lg hover:from-indigo-700 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                        <span id="btn-text">✨ Get Instant Access</span>
                        <span id="btn-loading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending verification email...
                        </span>
                    </button>
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

                <!-- Trust Indicators -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-center space-x-6 text-sm text-gray-500">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Secure & Encrypted
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            No Spam Guarantee
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Instant Access
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Powered by {{ config('app.name') }} • Protecting your privacy since day one</p>
        </div>
    </div>
</div>

<!-- Modal for Agreement Explanation -->
<div id="agreement-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 m-4 max-w-md">
        <h3 class="text-lg font-semibold mb-4">About Email Subscription</h3>
        <p class="text-gray-600 mb-4">
            By checking this box, you're agreeing to join {{ $tenant->ytChannel?->title ?? $tenant->name }}'s email list. 
            This means you'll receive:
        </p>
        <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
            <li>Exclusive content notifications</li>
            <li>Updates about new videos and content</li>
            <li>Special offers and insights</li>
        </ul>
        <p class="text-sm text-gray-500 mb-4">
            You can unsubscribe at any time using the link in any email.
        </p>
        <div class="flex space-x-3">
            <button onclick="closeAgreementModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
                Cancel
            </button>
            <button onclick="acceptAgreement()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                I Understand
            </button>
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

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!subscribeCheckbox.checked) {
            showAgreementModal();
            return;
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
    });
});

function showAgreementModal() {
    document.getElementById('agreement-modal').classList.remove('hidden');
    document.getElementById('agreement-modal').classList.add('flex');
}

function closeAgreementModal() {
    document.getElementById('agreement-modal').classList.add('hidden');
    document.getElementById('agreement-modal').classList.remove('flex');
    // Re-check the checkbox
    document.getElementById('subscribe_agreed').checked = true;
}

function acceptAgreement() {
    closeAgreementModal();
    document.getElementById('subscribe_agreed').checked = true;
}
</script>
@endsection 