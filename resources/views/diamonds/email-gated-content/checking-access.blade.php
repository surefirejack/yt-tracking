@extends('layouts.public')

@push('styles')
<style>
    :root {
        --accent-color: {{ $tenant->subscriber_accent_color ?? '#3B82F6' }};
        --accent-color-hover: {{ $tenant->subscriber_accent_color ? 'color-mix(in srgb, ' . $tenant->subscriber_accent_color . ' 80%, black)' : '#2563EB' }};
        --accent-color-light: {{ $tenant->subscriber_accent_color ? 'color-mix(in srgb, ' . $tenant->subscriber_accent_color . ' 20%, white)' : '#DBEAFE' }};
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Channel Banner Header -->
    <div class="relative bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-white">
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <div class="relative container mx-auto px-4 py-8">
            <div class="flex items-center space-x-4">
                @if($tenant->ytChannel && $tenant->ytChannel->profile_image_url)
                    <div class="w-16 h-16 rounded-full overflow-hidden border-4 border-white shadow-lg">
                        <img src="{{ $tenant->ytChannel->profile_image_url }}" 
                             alt="{{ $tenant->ytChannel->title }}" 
                             class="w-full h-full object-cover">
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-bold">{{ $tenant->ytChannel->title ?? 'Channel' }}</h1>
                    <p class="text-gray-300">Verifying your access...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <!-- Loading Animation -->
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Checking Your Access</h2>
                    <p class="text-gray-600" id="status-message">Verifying your subscription status...</p>
                </div>

                <!-- Content Preview -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $content->title }}</h3>
                    <p class="text-gray-600 text-sm">You're about to get access to this exclusive content</p>
                </div>

                <!-- Progress Indicator -->
                <div class="mb-6">
                    <div class="flex justify-center items-center space-x-2 text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Cookie found
                        </span>
                        <span class="text-gray-300">•</span>
                        <span class="flex items-center" id="esp-check-status">
                            <svg class="animate-spin w-4 h-4 mr-1 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Checking subscription tags
                        </span>
                    </div>
                </div>

                <!-- Time Indicator -->
                <p class="text-xs text-gray-400" id="time-indicator">
                    This usually takes 2-5 seconds...
                </p>
            </div>
        </div>
    </div>
</div>

<script>
let checkCount = 0;
let startTime = Date.now();
const accessRecordId = {{ $accessRecord->id }};
const maxChecks = 60; // Maximum 60 checks (1 minute)

function updateStatusMessage() {
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const statusMessage = document.getElementById('status-message');
    const timeIndicator = document.getElementById('time-indicator');
    
    if (elapsed < 5) {
        statusMessage.textContent = 'Verifying your subscription status...';
        timeIndicator.textContent = 'This usually takes 2-5 seconds...';
    } else if (elapsed < 15) {
        statusMessage.textContent = 'Still checking your subscription tags...';
        timeIndicator.textContent = `Checking for ${elapsed} seconds...`;
    } else if (elapsed < 30) {
        statusMessage.textContent = 'Taking a bit longer than usual...';
        timeIndicator.textContent = `Still working... (${elapsed}s)`;
    } else {
        statusMessage.textContent = 'Almost done, please wait...';
        timeIndicator.textContent = `Processing... (${elapsed}s)`;
    }
}

function checkAccessStatus() {
    checkCount++;
    updateStatusMessage();
    
    if (checkCount > maxChecks) {
        // Timeout - redirect to access form
        window.location.href = '{{ route("email-gated-content.show", [$channelname, $content->slug]) }}?timeout=1';
        return;
    }
    
    fetch(`/api/check-access-status/${accessRecordId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Access check response:', data);
            
            if (data.status === 'completed') {
                // Update UI to show completion
                document.getElementById('esp-check-status').innerHTML = `
                    <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Subscription verified
                `;
                
                // Redirect based on access result
                setTimeout(() => {
                    if (data.hasAccess) {
                        document.getElementById('status-message').textContent = 'Access granted! Redirecting to content...';
                        window.location.href = data.contentUrl;
                    } else {
                        document.getElementById('status-message').textContent = 'Redirecting to access form...';
                        window.location.href = data.accessFormUrl;
                    }
                }, 1000);
                
            } else if (data.status === 'failed') {
                // Handle failure
                document.getElementById('status-message').textContent = 'Verification failed. Redirecting...';
                setTimeout(() => {
                    window.location.href = data.accessFormUrl;
                }, 2000);
                
            } else {
                // Still processing, check again
                setTimeout(checkAccessStatus, 1000);
            }
        })
        .catch(error => {
            console.error('Error checking access status:', error);
            
            // Retry on error, but limit retries
            if (checkCount < maxChecks) {
                setTimeout(checkAccessStatus, 2000);
            } else {
                window.location.href = '{{ route("email-gated-content.show", [$channelname, $content->slug]) }}?error=1';
            }
        });
}

// Start checking immediately
setTimeout(checkAccessStatus, 1000);
</script>
@endsection 