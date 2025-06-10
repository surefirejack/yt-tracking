# YouTube Integration Documentation

## Overview

This YouTube integration allows users to connect their YouTube accounts to the application, enabling automated tracking of video analytics and performance metrics. The integration handles OAuth authentication, automatic token refresh, and provides easy-to-use services for interacting with the YouTube Data API v3.

## Features

- ✅ **OAuth Authentication** - Secure connection to YouTube accounts
- ✅ **Automatic Token Refresh** - Seamless handling of expired access tokens
- ✅ **Multi-tenant Support** - Works within Filament tenant context
- ✅ **User-friendly Interface** - Connect/disconnect functionality in settings
- ✅ **Comprehensive Logging** - Detailed logs for debugging and monitoring
- ✅ **API Abstraction** - Easy-to-use services for YouTube API calls

## Architecture

### Core Components

1. **YouTubeIntegrationController** - Handles OAuth flow (redirect, callback, disconnect)
2. **YouTubeTokenService** - Manages token lifecycle and automatic refresh
3. **YouTubeApiService** - Provides high-level methods for common YouTube API operations
4. **Settings Page Integration** - UI for connecting/disconnecting YouTube accounts

### Database Storage

User YouTube data is stored in the `user_parameters` table with the following keys:
- `youtube_connected` - Boolean indicating connection status
- `youtube_token` - Current access token
- `youtube_refresh_token` - Refresh token for obtaining new access tokens
- `youtube_token_expires_at` - Timestamp when the access token expires
- `youtube_email` - User's YouTube/Google email
- `youtube_user_id` - YouTube user ID
- `youtube_nickname` - YouTube channel name/nickname

## Setup and Configuration

### 1. Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the **YouTube Data API v3**
4. Create OAuth 2.0 credentials:
   - Application type: Web application
   - Authorized redirect URIs:
     - `https://yourdomain.com/integrations/youtube/callback`
     - `https://expose-url.com/integrations/youtube/callback` (for local testing)

### 2. Environment Configuration

Add the following to your `.env` file:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# For local testing with expose/ngrok
TESTING_URL=https://your-expose-url.com
```

### 3. Services Configuration

Ensure your `config/services.php` includes:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],

'youtube' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', '/integrations/youtube/callback'),
],

'local' => [
    'base_url' => env('TESTING_URL'),
],
```

### 4. Package Installation

Ensure you have the required packages:

```bash
composer require socialiteproviders/youtube
```

### 5. Event Listener Registration

In `app/Providers/AppServiceProvider.php`:

```php
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\YouTube\Provider;

public function boot()
{
    Event::listen(function (SocialiteWasCalled $event) {
        $event->extendSocialite('youtube', Provider::class);
    });
}
```

## Usage

### Basic Token Management

```php
use App\Services\YouTubeTokenService;

$tokenService = app(YouTubeTokenService::class);
$user = auth()->user();

// Get a valid access token (automatically refreshes if needed)
$accessToken = $tokenService->getValidAccessToken($user);

// Check if user has a valid YouTube connection
$isConnected = $tokenService->hasValidConnection($user);
```

### Using the YouTube API Service

```php
use App\Services\YouTubeApiService;

$apiService = app(YouTubeApiService::class);
$user = auth()->user();

// Get channel information
$channelInfo = $apiService->getChannelInfo($user);

// Get channel videos
$videos = $apiService->getChannelVideos($user, $maxResults = 25);

// Get video statistics
$stats = $apiService->getVideoStats($user, 'VIDEO_ID_HERE');

// Get multiple video statistics
$multipleStats = $apiService->getMultipleVideoStats($user, ['VIDEO_ID_1', 'VIDEO_ID_2']);
```

### Custom API Requests

```php
use App\Services\YouTubeTokenService;

$tokenService = app(YouTubeTokenService::class);
$user = auth()->user();

// Make a custom YouTube API request
$searchResults = $tokenService->makeYouTubeApiRequest($user, 'search', [
    'part' => 'snippet',
    'q' => 'Laravel tutorial',
    'type' => 'video',
    'maxResults' => 10
]);

// Get playlist details
$playlistInfo = $tokenService->makeYouTubeApiRequest($user, 'playlists', [
    'part' => 'snippet,contentDetails',
    'mine' => 'true'
]);
```

## User Interface

### Settings Page Integration

The YouTube integration is available in the user settings under the "Integrations" tab:

- **Connection Status** - Shows whether YouTube is connected with email if available
- **Connect Button** - Initiates OAuth flow to connect YouTube account
- **Disconnect Button** - Removes YouTube connection and clears stored tokens

### User Flow

1. User clicks "Connect YouTube" in settings
2. Redirected to Google OAuth consent screen
3. User grants permissions for YouTube access
4. Redirected back to settings with success message
5. Integration status shows as "Connected"

## Routes

The integration uses the following routes:

```php
// OAuth initiation
GET /integrations/youtube/redirect

// OAuth callback
GET /integrations/youtube/callback

// Disconnect integration
POST /integrations/youtube/disconnect/{tenant?}
```

## Token Management Details

### Access Token Lifecycle

1. **Initial Grant** - 1-hour access token obtained via OAuth
2. **Automatic Refresh** - Service checks expiry before each API call
3. **5-Minute Buffer** - Tokens are refreshed 5 minutes before expiry
4. **Refresh Token** - Long-lived token used to obtain new access tokens

### Error Handling

- **Token Refresh Failure** - Logged with detailed error information
- **API Request Failures** - Logged with endpoint and response details
- **Missing Tokens** - Graceful degradation with warning logs

## API Permissions

The integration requests the following YouTube API scope:

- `https://www.googleapis.com/auth/youtube.force-ssl` - Full read/write access to YouTube account

This scope allows:
- Reading channel information
- Managing videos, playlists, and channel settings
- Accessing analytics data
- Uploading and updating videos

## Common Use Cases

### 1. Channel Analytics Dashboard

```php
$apiService = app(YouTubeApiService::class);
$user = auth()->user();

// Get channel stats
$channelInfo = $apiService->getChannelInfo($user);
$subscriberCount = $channelInfo['items'][0]['statistics']['subscriberCount'] ?? 0;
$totalViews = $channelInfo['items'][0]['statistics']['viewCount'] ?? 0;

// Get recent videos
$recentVideos = $apiService->getChannelVideos($user, 10);
```

### 2. Video Performance Tracking

```php
$videoIds = ['VIDEO_ID_1', 'VIDEO_ID_2', 'VIDEO_ID_3'];
$videoStats = $apiService->getMultipleVideoStats($user, $videoIds);

foreach ($videoStats['items'] as $video) {
    $title = $video['snippet']['title'];
    $views = $video['statistics']['viewCount'];
    $likes = $video['statistics']['likeCount'];
    // Store or display stats
}
```

### 3. Automated Content Management

```php
$tokenService = app(YouTubeTokenService::class);

// Update video details
$response = $tokenService->makeYouTubeApiRequest($user, 'videos', [
    'part' => 'snippet',
    'id' => 'VIDEO_ID',
    // Add update data
]);
```

## Troubleshooting

### Common Issues

#### 1. "Driver [youtube] not supported"
**Solution:** Ensure SocialiteProviders YouTube package is installed and event listener is registered.

#### 2. "Missing required parameter: client_id"
**Solution:** Check that `GOOGLE_CLIENT_ID` is set in your `.env` file.

#### 3. "Invalid redirect URI"
**Solution:** Verify redirect URIs in Google Cloud Console match your application URLs.

#### 4. "Access denied" or "insufficient permissions"
**Solution:** Ensure user grants all requested permissions during OAuth flow.

#### 5. Token refresh failures
**Solution:** Check that `GOOGLE_CLIENT_SECRET` is correctly set and matches Google Cloud Console.

### Debugging

Enable detailed logging by checking Laravel logs during integration:

```bash
tail -f storage/logs/laravel.log | grep "YouTube Integration"
```

Look for these log entries:
- `YouTube Integration: redirect method called`
- `YouTube Integration: callback method called`
- `YouTube token refreshed successfully`
- `YouTube Integration: disconnect method called`

### Testing Token Refresh

To test automatic token refresh:

1. Connect YouTube account
2. Manually update `youtube_token_expires_at` to a past date in database
3. Make an API call - should automatically refresh token

## Security Considerations

- **Token Storage** - Tokens are encrypted in database via Laravel's built-in encryption
- **CSRF Protection** - All forms include CSRF tokens
- **Tenant Isolation** - Each tenant's data is isolated in multi-tenant setup
- **Scope Limitation** - Only request minimum required YouTube permissions
- **Secure Redirect** - OAuth callbacks use HTTPS in production

## Performance Optimization

- **Token Caching** - Tokens are cached until near expiry
- **Batch API Calls** - Use multiple video IDs in single request when possible
- **Rate Limiting** - YouTube API has quota limits; implement rate limiting if needed
- **Async Processing** - Consider queue jobs for bulk video processing

## Maintenance

### Regular Tasks

1. **Monitor Token Refresh** - Check logs for refresh failures
2. **API Quota Usage** - Monitor YouTube API quota consumption
3. **Update Dependencies** - Keep SocialiteProviders package updated
4. **Clean Expired Data** - Remove old disconnected user tokens

### Backup Considerations

- User parameters table contains sensitive token data
- Ensure proper encryption of database backups
- Consider token rotation for enhanced security

## Support

For issues related to:
- **OAuth Setup** - Check Google Cloud Console configuration
- **API Limits** - Review YouTube Data API quotas and pricing
- **Token Issues** - Check application logs for detailed error messages
- **UI Problems** - Verify Filament and Livewire configuration

## API Documentation References

- [YouTube Data API v3](https://developers.google.com/youtube/v3)
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [SocialiteProviders YouTube](https://socialiteproviders.com/YouTube/)
- [Laravel Socialite](https://laravel.com/docs/socialite) 