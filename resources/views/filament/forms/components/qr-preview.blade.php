<div class="space-y-4">
    @if($qr_url)
        <div class="flex flex-col items-center space-y-4 p-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">QR Code</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Scan to access your short link</p>
            </div>
            
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <img src="{{ $qr_url }}" 
                     alt="QR Code for {{ $short_link ?? 'short link' }}" 
                     class="w-48 h-48 mx-auto"
                     style="image-rendering: pixelated;">
            </div>
            
            @if($short_link)
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Short Link:</p>
                    <a href="{{ $short_link }}" 
                       target="_blank" 
                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium text-sm break-all">
                        {{ $short_link }}
                    </a>
                </div>
            @endif
            
            <div class="flex flex-wrap gap-2 justify-center">
                <button type="button" 
                        onclick="copyToClipboard('{{ $qr_url }}')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Copy QR URL
                </button>
                
                <a href="{{ $qr_url }}" 
                   download="qr-code.png"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download QR
                </a>
            </div>
        </div>
    @else
        <div class="text-center p-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1zm12 0h2a1 1 0 001-1V6a1 1 0 00-1-1h-2a1 1 0 00-1 1v1a1 1 0 001 1zM5 20h2a1 1 0 001-1v-1a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1z"></path>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No QR code available yet</p>
        </div>
    @endif
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show a notification or feedback
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Copied!';
        button.className = button.className.replace('text-gray-700 bg-white hover:bg-gray-50', 'text-green-700 bg-green-50 hover:bg-green-100');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.className = button.className.replace('text-green-700 bg-green-50 hover:bg-green-100', 'text-gray-700 bg-white hover:bg-gray-50');
        }, 2000);
    });
}
</script> 