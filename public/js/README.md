# Dub.co Conversion Tracking Integration

This integration allows your clients to track conversions through Dub.co analytics by adding simple code snippets to their websites.

## Client Integration

### 1. General Page Tracking Snippet
Your clients should add this snippet to all pages where they want to track clicks and engagement:

```html
<!-- Dub.co Analytics Tracking -->
<script>
(function(d,s,id,domain){
    if(d.getElementById(id)) return;
    var js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
    js.id=id;js.async=true;
    js.src='https://'+domain+'/js/dub-tracking.js';
    fjs.parentNode.insertBefore(js,fjs);
})(document,'script','dub-tracking-js','yourdomain.com');
</script>
```

### 2. Conversion Tracking Snippet
Your clients should add this snippet to thank you pages, signup completion pages, or anywhere they want to track conversions:

```html
<!-- Dub.co Conversion Tracking -->
<script>
// Optional: Configure the conversion event
window.DubConversionConfig = {
    eventName: 'Sign up',     // Customize this for each client
    eventQuantity: 1
};
</script>
<script>
(function(d,s,id,domain){
    if(d.getElementById(id)) return;
    var js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
    js.id=id;js.async=true;
    js.src='https://'+domain+'/js/dub-conversion.js';
    fjs.parentNode.insertBefore(js,fjs);
})(document,'script','dub-conversion-js','yourdomain.com');
</script>
```

### 3. Advanced Conversion Tracking with Custom Data
For more advanced tracking with customer information:

```html
<!-- Dub.co Advanced Conversion Tracking -->
<script>
window.DubConversionConfig = {
    eventName: 'Purchase',
    eventQuantity: 1,
    customerName: 'John Doe',           // Optional
    customerEmail: 'john@example.com',  // Optional
    externalId: 'user_12345',           // Optional: your internal customer ID
    metadata: {                         // Optional: additional data
        product: 'Premium Plan',
        value: 99.99,
        currency: 'USD'
    }
};
</script>
<script>
(function(d,s,id,domain){
    if(d.getElementById(id)) return;
    var js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
    js.id=id;js.async=true;
    js.src='https://'+domain+'/js/dub-conversion.js';
    fjs.parentNode.insertBefore(js,fjs);
})(document,'script','dub-conversion-js','yourdomain.com');
</script>
```

## How to Provide to Clients

### Basic Setup Instructions
Give your clients these instructions:

1. **Replace `yourdomain.com`** with your actual domain in all snippets
2. **Add the general tracking snippet** to all pages in the `<head>` section
3. **Add the conversion tracking snippet** to success/thank you pages
4. **Customize the `eventName`** for each conversion type (e.g., 'Sign up', 'Purchase', 'Download')

### Template for Clients

```html
<!DOCTYPE html>
<html>
<head>
    <!-- Other head content -->
    
    <!-- Add this to ALL pages for general tracking -->
    <script>
    (function(d,s,id,domain){
        if(d.getElementById(id)) return;
        var js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
        js.id=id;js.async=true;
        js.src='https://'+domain+'/js/dub-tracking.js';
        fjs.parentNode.insertBefore(js,fjs);
    })(document,'script','dub-tracking-js','yourdomain.com');
    </script>
</head>
<body>
    <!-- Page content -->
</body>
</html>
```

```html
<!DOCTYPE html>
<html>
<head>
    <!-- Other head content -->
    
    <!-- Add this to CONVERSION/THANK YOU pages -->
    <script>
    window.DubConversionConfig = {
        eventName: 'Sign up'  // Customize this!
    };
    </script>
    <script>
    (function(d,s,id,domain){
        if(d.getElementById(id)) return;
        var js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
        js.id=id;js.async=true;
        js.src='https://'+domain+'/js/dub-conversion.js';
        fjs.parentNode.insertBefore(js,fjs);
    })(document,'script','dub-conversion-js','yourdomain.com');
    </script>
</head>
<body>
    <!-- Thank you page content -->
</body>
</html>
```

## Features

### Click ID Detection
The conversion script automatically detects click IDs from:
1. URL parameters: `dub_id`, `click_id`, or `dubClickId`
2. Local storage (if previously stored)
3. Cookies named `dub_click_id`

### Data Transmission
- Uses `navigator.sendBeacon()` as primary method (works even if user leaves page)
- Falls back to `fetch()` with timeout protection
- Sends data to your platform's API endpoint

### Error Handling
- Gracefully handles missing click IDs
- Logs warnings and errors to browser console
- Includes timeout protection for network requests
- Cross-origin compatible

## Testing

Clients can test the implementation by:
1. Adding the tracking snippet to a test page
2. Visiting the page with `?dub_id=test123` in the URL
3. Going to a page with the conversion snippet
4. Checking the browser console for success messages

## Support Notes

- Scripts load asynchronously and won't block page rendering
- Compatible with all modern browsers
- Designed to fail gracefully if your API is temporarily unavailable
- CORS headers are configured for cross-origin requests 