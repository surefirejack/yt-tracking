(function() {
    'use strict';
    
    // Function to load the Dub analytics script
    function loadDubAnalytics() {
        // Check if script is already loaded
        if (document.querySelector('script[src*="dubcdn.com/analytics/script.js"]')) {
            return;
        }
        
        // Create script element
        const script = document.createElement('script');
        script.src = 'https://www.dubcdn.com/analytics/script.js';
        script.setAttribute('data-domains', JSON.stringify({"refer": "ytlnk.com"}));
        script.defer = true;
        
        // Append to head
        document.head.appendChild(script);
    }
    
    // Load immediately if DOM is ready, otherwise wait for it
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadDubAnalytics);
    } else {
        loadDubAnalytics();
    }
})(); 