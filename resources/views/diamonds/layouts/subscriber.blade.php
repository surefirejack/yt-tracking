<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Subscribers Area' }} - {{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Favicon -->
    @if($tenant->member_profile_image)
        <link rel="icon" type="image/png" href="{{ Storage::url($tenant->member_profile_image) }}">
    @endif

    <!-- Meta Tags -->
    <meta name="description" content="Exclusive subscribers-only content from {{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $title ?? 'Subscribers Area' }} - {{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}">
    <meta property="og:description" content="Exclusive subscribers-only content">
    @if($tenant->ytChannel?->banner_image_url)
        <meta property="og:image" content="{{ $tenant->ytChannel->banner_image_url }}">
    @endif

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- Custom Animations -->
    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        @keyframes slide-in-right {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
        
        .animate-pulse-subtle {
            animation: pulse-subtle 2s infinite;
        }
        
        .animate-slide-in-right {
            animation: slide-in-right 0.3s ease-out;
        }
        
        /* Loading spinner enhancement */
        .loading-spinner {
            animation: spin 1s linear infinite, pulse-subtle 2s infinite;
        }
        
        /* Smooth transitions for interactive elements */
        .transition-all-enhanced {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
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
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen">
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
                            Subscribers Only Area
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
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
                            Subscribers Only Area
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Bar -->
    @if(!isset($hideNavigation) || !$hideNavigation)
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Breadcrumb / Navigation -->
                <nav class="flex items-center space-x-2 text-sm text-gray-600 overflow-hidden">
                    <a href="{{ route('subscriber.dashboard', ['channelname' => $channelname]) }}" 
                       class="hover:text-blue-600 transition-colors duration-200 flex items-center">
                        <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v1H8V5z"></path>
                        </svg>
                        <span class="hidden sm:inline">Dashboard</span>
                        <span class="sm:hidden">Home</span>
                    </a>
                    @if(isset($contentTitle))
                        <span class="text-gray-400 hidden sm:inline">/</span>
                        <span class="text-gray-900 font-medium truncate max-w-xs">{{ $contentTitle }}</span>
                    @endif
                </nav>

                <!-- User Actions -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    @if(isset($subscriber))
                        <div class="hidden lg:flex items-center space-x-3 text-sm">
                            @if($subscriber->profile_picture)
                                <img 
                                    src="{{ $subscriber->profile_picture }}" 
                                    alt="{{ $subscriber->name }}"
                                    class="w-8 h-8 rounded-full object-cover"
                                >
                            @endif
                            <span class="text-gray-700 max-w-xs truncate">{{ $subscriber->name }}</span>
                        </div>
                        
                        <!-- Logout Form - Only show when authenticated -->
                        <form method="POST" action="{{ route('subscriber.logout', ['channelname' => $channelname]) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors duration-200 flex items-center">
                                <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    @else
                        <!-- Show login status when not authenticated -->
                        <div class="text-sm text-gray-500">
                            <span class="hidden sm:inline">Not authenticated</span>
                            <span class="sm:hidden">⚠️</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Simplified header for email-gated content -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex items-center h-16">
                @if(isset($contentTitle))
                    <nav class="flex items-center space-x-2 text-sm text-gray-600">
                        <span class="text-gray-900 font-medium">{{ $contentTitle }}</span>
                    </nav>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6 sm:py-8">
        @if (session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg animate-fade-in" role="alert">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="font-medium">Success!</p>
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-4 text-green-500 hover:text-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg animate-fade-in" role="alert">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="font-medium">Error!</p>
                        <p class="text-sm">{{ session('error') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-4 text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session('warning'))
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg animate-fade-in" role="alert">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="font-medium">Warning!</p>
                        <p class="text-sm">{{ session('warning') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-4 text-yellow-500 hover:text-yellow-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-gray-600 text-sm mb-4 md:mb-0">
                    © {{ date('Y') }} {{ $tenant->ytChannel->name ?? $tenant->name ?? 'Creator' }}. All rights reserved.
                </div>
                
                <!-- Powered by Link -->
                <div class="text-sm">
                    <a href="{{ route('referral.track', ['tenant' => $tenant->uuid]) }}" 
                       target="_blank"
                       class="text-gray-500 hover:text-blue-600 transition-colors duration-200 flex items-center space-x-1">
                        <span>Powered by</span>
                        <span class="font-semibold">{{ config('app.name', 'VideoStats.ai') }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading States -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-4 sm:p-6 flex flex-col items-center space-y-3 max-w-sm w-full mx-4">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-center">
                <span class="text-gray-700 font-medium text-sm sm:text-base" id="loading-text">Loading...</span>
                <p class="text-gray-500 text-xs sm:text-sm mt-1" id="loading-subtext">Please wait while we process your request</p>
            </div>
        </div>
    </div>

    <!-- Network Error State -->
    <div id="network-error" class="hidden fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg z-50 max-w-sm">
        <div class="flex items-start">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <div class="flex-1">
                <p class="font-medium text-sm">Connection Error</p>
                <p class="text-xs">Please check your internet connection and try again.</p>
            </div>
            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-2 text-red-500 hover:text-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    @stack('scripts')

    <!-- Enhanced Loading States Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadingOverlay = document.getElementById('loading-overlay');
            const loadingText = document.getElementById('loading-text');
            const loadingSubtext = document.getElementById('loading-subtext');
            const networkError = document.getElementById('network-error');

            // Enhanced loading function with custom messages
            window.showLoading = function(message = 'Loading...', subtext = 'Please wait while we process your request') {
                if (loadingText) loadingText.textContent = message;
                if (loadingSubtext) loadingSubtext.textContent = subtext;
                if (loadingOverlay) loadingOverlay.classList.remove('hidden');
            };

            window.hideLoading = function() {
                if (loadingOverlay) loadingOverlay.classList.add('hidden');
            };

            window.showNetworkError = function() {
                if (networkError) {
                    networkError.classList.remove('hidden');
                    setTimeout(() => {
                        networkError.classList.add('hidden');
                    }, 5000);
                }
            };

            // Auto-hide loading after timeout
            let loadingTimeout;
            window.showLoadingWithTimeout = function(message, subtext, timeout = 10000) {
                showLoading(message, subtext);
                clearTimeout(loadingTimeout);
                loadingTimeout = setTimeout(() => {
                    hideLoading();
                    showNetworkError();
                }, timeout);
            };

            // Enhanced navigation loading
            document.querySelectorAll('a[href*="subscriber"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Don't show loading for external links or downloads
                    if (this.target === '_blank' || this.hasAttribute('download')) {
                        return;
                    }
                    
                    showLoadingWithTimeout('Loading page...', 'Taking you to your content');
                });
            });

            // Form submission loading
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    showLoadingWithTimeout('Processing...', 'Please wait while we handle your request');
                });
            });

            // Download tracking with feedback
            document.querySelectorAll('a[href*="download"]').forEach(link => {
                link.addEventListener('click', function() {
                    // Brief loading for download initiation
                    showLoading('Preparing download...', 'Your file will start downloading shortly');
                    setTimeout(() => {
                        hideLoading();
                    }, 2000);
                });
            });

            // Network connection monitoring
            window.addEventListener('online', function() {
                if (networkError && !networkError.classList.contains('hidden')) {
                    networkError.classList.add('hidden');
                }
            });

            window.addEventListener('offline', function() {
                hideLoading();
                showNetworkError();
            });
        });
    </script>
</body>
</html> 