(function() {
    'use strict';
    
    // Configuration - can be overridden by setting window.DubConversionConfig
    const defaultConfig = {
        apiEndpoint: '/api/dub/track-conversion',
        eventName: 'Conversion',
        eventQuantity: 1,
        timeout: 5000
    };
    
    // Function to get click ID from URL parameters or local storage
    function getClickId() {
        // First, try to get from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        let clickId = urlParams.get('dub_id') || urlParams.get('click_id') || urlParams.get('dubClickId');
        
        if (clickId) {
            // Store for later use
            try {
                localStorage.setItem('dub_click_id', clickId);
            } catch (e) {
                // Ignore storage errors
            }
            return clickId;
        }
        
        // Try to get from local storage
        try {
            clickId = localStorage.getItem('dub_click_id');
            if (clickId) {
                return clickId;
            }
        } catch (e) {
            // Ignore storage errors
        }
        
        // Try to get from cookies
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'dub_click_id' && value) {
                return decodeURIComponent(value);
            }
        }
        
        return null;
    }
    
    // Function to send conversion data
    function trackConversion(customData = {}) {
        const config = Object.assign({}, defaultConfig, window.DubConversionConfig || {});
        const clickId = getClickId();
        
        if (!clickId) {
            console.warn('Dub Conversion: No click ID found, skipping conversion tracking');
            return;
        }
        
        const conversionData = {
            clickId: clickId,
            eventName: customData.eventName || config.eventName,
            eventQuantity: customData.eventQuantity || config.eventQuantity,
            externalId: customData.externalId || null,
            customerName: customData.customerName || null,
            customerEmail: customData.customerEmail || null,
            customerAvatar: customData.customerAvatar || null,
            metadata: customData.metadata || null
        };
        
        // Use sendBeacon if available (preferred for page unload scenarios)
        if (navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify(conversionData)], {
                type: 'application/json'
            });
            
            const success = navigator.sendBeacon(config.apiEndpoint, blob);
            if (success) {
                console.log('Dub Conversion: Tracked successfully via beacon');
                return;
            }
        }
        
        // Fallback to fetch with timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), config.timeout);
        
        fetch(config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(conversionData),
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (response.ok) {
                console.log('Dub Conversion: Tracked successfully via fetch');
            } else {
                console.error('Dub Conversion: Tracking failed with status:', response.status);
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            if (error.name !== 'AbortError') {
                console.error('Dub Conversion: Tracking error:', error);
            }
        });
    }
    
    // Auto-track conversion on page load unless disabled
    function autoTrack() {
        const config = window.DubConversionConfig || {};
        if (config.autoTrack !== false) {
            trackConversion();
        }
    }
    
    // Expose the tracking function globally
    window.dubTrackConversion = trackConversion;
    
    // Auto-track when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoTrack);
    } else {
        autoTrack();
    }
})(); 