<div class="space-y-6">
    <!-- Loading header with progress indicator -->
    <div class="text-center py-6 border-b border-gray-200 dark:border-gray-600">
        <div class="inline-flex items-center space-x-3 mb-4">
            <svg class="animate-spin h-6 w-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-lg font-medium text-gray-700 dark:text-gray-200">Loading your videos...</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-300">
            We're fetching your latest videos from YouTube. This may take a few moments.
        </p>
        <div class="mt-3 max-w-xs mx-auto">
            <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full animate-pulse" style="width: 45%"></div>
            </div>
        </div>
    </div>
    
    <!-- Skeleton table structure -->
    <div class="overflow-hidden">
        <!-- Table header -->
        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
            <div class="flex items-center space-x-6">
                <div class="w-20 h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse"></div>
                <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse"></div>
                <div class="w-16 h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse"></div>
                <div class="w-16 h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse"></div>
                <div class="w-16 h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse"></div>
                <div class="w-20 h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse"></div>
                <div class="w-16 h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse"></div>
            </div>
        </div>
        
        <!-- Skeleton rows -->
        <div class="divide-y divide-gray-200 dark:divide-gray-600">
            @for ($i = 0; $i < 8; $i++)
                <div class="flex items-center py-4 px-6 space-x-6" style="animation-delay: {{ $i * 0.1 }}s">
                    <!-- Thumbnail skeleton -->
                    <div class="bg-gray-200 dark:bg-gray-500 rounded-lg w-20 h-12 flex-shrink-0 animate-pulse"></div>
                    
                    <!-- Content skeleton with more spacing -->
                    <div class="flex-1 space-y-3 pr-4">
                        <!-- Title skeleton -->
                        <div class="h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse" style="width: {{ 60 + ($i % 3) * 15 }}%"></div>
                        <!-- Channel skeleton -->
                        <div class="h-3 bg-gray-200 dark:bg-gray-500 rounded animate-pulse" style="width: {{ 40 + ($i % 2) * 20 }}%"></div>
                    </div>
                    
                    <!-- Stats columns skeleton with better spacing -->
                    <div class="w-20 flex justify-center">
                        <div class="h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse w-16"></div>
                    </div>
                    <div class="w-16 flex justify-center">
                        <div class="h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse w-12"></div>
                    </div>
                    <div class="w-24 flex justify-center">
                        <div class="h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse w-20"></div>
                    </div>
                    <div class="w-20 flex justify-center">
                        <div class="h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse w-16"></div>
                    </div>
                    <div class="w-16 flex justify-center">
                        <div class="h-4 bg-gray-200 dark:bg-gray-500 rounded animate-pulse w-10"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
    
    <!-- Auto-refresh indicator -->
    <div class="text-center py-4 border-t border-gray-200 dark:border-gray-600">
        <div class="inline-flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-300">
            <div class="w-2 h-2 bg-green-500 dark:bg-green-400 rounded-full animate-pulse"></div>
            <span>Auto-refreshing every 1.5 seconds</span>
        </div>
    </div>
</div>

<!-- Enhanced styling for better dark mode -->
<style>
    @keyframes shimmer {
        0% {
            background-position: -200px 0;
        }
        100% {
            background-position: calc(200px + 100%) 0;
        }
    }
    
    .animate-pulse {
        background: linear-gradient(90deg, 
            theme('colors.gray.200') 25%, 
            theme('colors.gray.100') 37%, 
            theme('colors.gray.200') 63%
        );
        background-size: 200px 100%;
        animation: shimmer 1.5s infinite ease-in-out;
    }
    
    .dark .animate-pulse {
        background: linear-gradient(90deg, 
            theme('colors.gray.500') 25%, 
            theme('colors.gray.400') 37%, 
            theme('colors.gray.500') 63%
        );
    }
</style> 