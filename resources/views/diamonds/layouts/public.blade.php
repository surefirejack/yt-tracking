<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Content Access') - {{ isset($tenant) ? ($tenant->ytChannel?->title ?? $tenant->name ?? 'Creator') : 'Creator' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Favicon -->
    @if(isset($tenant) && $tenant->member_profile_image)
        <link rel="icon" type="image/png" href="{{ Storage::url($tenant->member_profile_image) }}">
    @elseif(isset($tenant) && $tenant->ytChannel?->thumbnail_url)
        <link rel="icon" type="image/png" href="{{ $tenant->ytChannel->thumbnail_url }}">
    @endif

    <!-- Meta Tags -->
    <meta name="description" content="@yield('meta_description', 'Get access to exclusive content from ' . (isset($tenant) ? ($tenant->ytChannel?->title ?? $tenant->name ?? 'Creator') : 'Creator'))">
    
    <!-- Open Graph -->
    <meta property="og:title" content="@yield('title', 'Content Access') - {{ isset($tenant) ? ($tenant->ytChannel?->title ?? $tenant->name ?? 'Creator') : 'Creator' }}">
    <meta property="og:description" content="@yield('meta_description', 'Get access to exclusive content')">
    @if(isset($tenant) && $tenant->ytChannel?->banner_image_url)
        <meta property="og:image" content="{{ $tenant->ytChannel->banner_image_url }}">
    @endif

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- Custom Styles -->
    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
        
        .animate-pulse-subtle {
            animation: pulse-subtle 2s infinite;
        }
        
        /* Loading spinner enhancement */
        .loading-spinner {
            animation: spin 1s linear infinite, pulse-subtle 2s infinite;
        }
        
        /* Custom scrollbar for better mobile experience */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        /* Mobile-specific improvements */
        @media (max-width: 640px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            /* Improve tap targets for mobile */
            button, a {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>
    
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen">
    
    @yield('content')
    
    @stack('scripts')
    
    <!-- JavaScript for email access form -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('email-access-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const submitBtn = document.getElementById('submit-btn');
                    const btnText = document.getElementById('btn-text');
                    const btnLoading = document.getElementById('btn-loading');
                    const messageContainer = document.getElementById('message-container');
                    const successMessage = document.getElementById('success-message');
                    const errorMessage = document.getElementById('error-message');
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    btnText.classList.add('hidden');
                    btnLoading.classList.remove('hidden');
                    
                    // Hide previous messages
                    messageContainer.classList.add('hidden');
                    successMessage.classList.add('hidden');
                    errorMessage.classList.add('hidden');
                    
                    const formData = new FormData(form);
                    
                    fetch(form.action || window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageContainer.classList.remove('hidden');
                            successMessage.classList.remove('hidden');
                            
                            if (data.message) {
                                document.getElementById('success-text').textContent = data.message;
                            }
                        } else {
                            messageContainer.classList.remove('hidden');
                            errorMessage.classList.remove('hidden');
                            
                            if (data.message) {
                                document.getElementById('error-text').textContent = data.message;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        messageContainer.classList.remove('hidden');
                        errorMessage.classList.remove('hidden');
                        document.getElementById('error-text').textContent = 'An unexpected error occurred. Please try again.';
                    })
                    .finally(() => {
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.classList.remove('hidden');
                        btnLoading.classList.add('hidden');
                    });
                });
            }
        });
    </script>
</body>
</html> 