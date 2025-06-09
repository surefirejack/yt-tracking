(function() {
  // Client embed snippet for YouTube Tracking
  // Usage: Replace 'YOUR_TENANT_UUID' with your actual tenant UUID
  
  window.ytTracking = window.ytTracking || {};
  window.ytTracking.tenantUuid = 'YOUR_TENANT_UUID'; // Client replaces this
  
  // Load universal.js asynchronously
  var script = document.createElement('script');
  script.src = 'https://youtubetracking.test/js/universal.js';
  script.async = true;
  script.defer = true;
  
  // Insert the script
  var firstScript = document.getElementsByTagName('script')[0];
  firstScript.parentNode.insertBefore(script, firstScript);
})(); 