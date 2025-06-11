<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Members Area' }} - {{ $tenant->name ?? 'Creator' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Favicon -->
    @if($tenant->member_profile_image)
        <link rel="icon" type="image/png" href="{{ Storage::url($tenant->member_profile_image) }}">
    @endif

    <!-- Meta Tags -->
    <meta name="description" content="Exclusive members-only content from {{ $tenant->name ?? 'Creator' }}">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $title ?? 'Members Area' }} - {{ $tenant->name ?? 'Creator' }}">
    <meta property="og:description" content="Exclusive members-only content">
    @if($tenant->ytChannel?->banner_image_url)
        <meta property="og:image" content="{{ $tenant->ytChannel->banner_image_url }}">
    @endif

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen">
    <!-- Channel Banner Header -->
    @if($tenant->ytChannel?->banner_image_url)
        <div class="w-full h-32 md:h-48 lg:h-64 bg-gradient-to-r from-blue-600 to-purple-600 relative overflow-hidden">
            <img 
                src="{{ $tenant->ytChannel->banner_image_url }}" 
                alt="{{ $tenant->name }} Channel Banner"
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
                            alt="{{ $tenant->name }}"
                            class="w-12 h-12 md:w-16 md:h-16 rounded-full border-4 border-white shadow-lg object-cover"
                        >
                    @endif
                    <div>
                        <h1 class="text-xl md:text-2xl lg:text-3xl font-bold drop-shadow-lg">
                            {{ $tenant->name ?? 'Creator' }}
                        </h1>
                        <p class="text-sm md:text-base opacity-90 drop-shadow">
                            Members Only Area
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
                            alt="{{ $tenant->name }}"
                            class="w-12 h-12 md:w-16 md:h-16 rounded-full border-4 border-white shadow-lg object-cover"
                        >
                    @endif
                    <div>
                        <h1 class="text-xl md:text-2xl lg:text-3xl font-bold">
                            {{ $tenant->name ?? 'Creator' }}
                        </h1>
                        <p class="text-sm md:text-base opacity-90">
                            Members Only Area
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
                <nav class="flex items-center space-x-2 text-sm text-gray-600">
                    <a href="{{ route('subscriber.dashboard', ['channelname' => $channelname]) }}" 
                       class="hover:text-blue-600 transition-colors duration-200">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v1H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    @if(isset($contentTitle))
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-900 font-medium">{{ $contentTitle }}</span>
                    @endif
                </nav>

                <!-- User Actions -->
                <div class="flex items-center space-x-4">
                    @if(isset($subscriber))
                        <div class="hidden md:flex items-center space-x-3 text-sm">
                            @if($subscriber->profile_picture)
                                <img 
                                    src="{{ $subscriber->profile_picture }}" 
                                    alt="{{ $subscriber->name }}"
                                    class="w-8 h-8 rounded-full object-cover"
                                >
                            @endif
                            <span class="text-gray-700">{{ $subscriber->name }}</span>
                        </div>
                    @endif
                    
                    <!-- Logout Form -->
                    <form method="POST" action="{{ route('subscriber.logout', ['channelname' => $channelname]) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors duration-200">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        @if (session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('error') }}
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
                    © {{ date('Y') }} {{ $tenant->name ?? 'Creator' }}. All rights reserved.
                </div>
                
                <!-- Powered by Link -->
                <div class="text-sm">
                    <a href="{{ route('referral.track', ['tenant' => $tenant->uuid]) }}" 
                       target="_blank"
                       class="text-gray-500 hover:text-blue-600 transition-colors duration-200 flex items-center space-x-1">
                        <span>Powered by</span>
                        <span class="font-semibold">{{ config('app.name', 'YT Tracking') }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading States -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    @stack('scripts')
</body>
</html> 