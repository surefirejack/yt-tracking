<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class YouTubeApiService
{
    private YouTubeTokenService $tokenService;
    
    public function __construct(YouTubeTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }
    
    /**
     * Get user's YouTube channel information
     */
    public function getChannelInfo(User $user): ?array
    {
        return $this->tokenService->makeYouTubeApiRequest($user, 'channels', [
            'part' => 'snippet,statistics',
            'mine' => 'true'
        ]);
    }
    
    /**
     * Get user's YouTube videos
     */
    public function getChannelVideos(User $user, int $maxResults = 50): ?array
    {
        // First get the channel ID
        $channelInfo = $this->getChannelInfo($user);
        
        if (!$channelInfo || empty($channelInfo['items'])) {
            Log::warning('No channel found for user', ['user_id' => $user->id]);
            return null;
        }
        
        $channelId = $channelInfo['items'][0]['id'];
        
        // Get the uploads playlist ID
        $uploadsPlaylistId = $channelInfo['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
        
        if (!$uploadsPlaylistId) {
            Log::warning('No uploads playlist found', ['user_id' => $user->id, 'channel_id' => $channelId]);
            return null;
        }
        
        // Get videos from the uploads playlist
        return $this->tokenService->makeYouTubeApiRequest($user, 'playlistItems', [
            'part' => 'snippet,contentDetails',
            'playlistId' => $uploadsPlaylistId,
            'maxResults' => $maxResults
        ]);
    }
    
    /**
     * Get video statistics
     */
    public function getVideoStats(User $user, string $videoId): ?array
    {
        return $this->tokenService->makeYouTubeApiRequest($user, 'videos', [
            'part' => 'statistics,snippet',
            'id' => $videoId
        ]);
    }
    
    /**
     * Get multiple video statistics
     */
    public function getMultipleVideoStats(User $user, array $videoIds): ?array
    {
        if (empty($videoIds)) {
            return null;
        }
        
        return $this->tokenService->makeYouTubeApiRequest($user, 'videos', [
            'part' => 'statistics,snippet',
            'id' => implode(',', $videoIds)
        ]);
    }

    /**
     * Check if the authenticated user is subscribed to a specific channel
     * 
     * @param User $user The authenticated user
     * @param string $channelId The channel ID to check subscription for
     * @return array|null Returns subscription data if subscribed, null if not subscribed or error
     */
    public function checkSubscription(User $user, string $channelId): ?array
    {
        try {
            Log::info('Checking subscription for channel', [
                'user_id' => $user->id,
                'channel_id' => $channelId
            ]);

            $response = $this->tokenService->makeYouTubeApiRequest($user, 'subscriptions', [
                'part' => 'snippet,subscriberSnippet',
                'forChannelId' => $channelId,
                'mine' => 'true'
            ]);

            Log::info('Subscription check API response', [
                'user_id' => $user->id,
                'channel_id' => $channelId,
                'response_received' => $response !== null,
                'has_items' => $response && isset($response['items']),
                'item_count' => $response && isset($response['items']) ? count($response['items']) : 0,
                'full_response' => $response
            ]);

            if ($response && isset($response['items']) && count($response['items']) > 0) {
                Log::info('User is subscribed to channel', [
                    'user_id' => $user->id,
                    'channel_id' => $channelId,
                    'subscription_data' => $response['items'][0]
                ]);
                return $response['items'][0];
            }

            Log::info('User is not subscribed to channel', [
                'user_id' => $user->id,
                'channel_id' => $channelId
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error checking subscription', [
                'user_id' => $user->id,
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Check if the authenticated user is subscribed to a specific channel (simple boolean)
     * 
     * @param User $user The authenticated user
     * @param string $channelId The channel ID to check subscription for
     * @return bool True if subscribed, false otherwise
     */
    public function isSubscribed(User $user, string $channelId): bool
    {
        return $this->checkSubscription($user, $channelId) !== null;
    }

    /**
     * Get channel information by channel ID or username
     * 
     * @param User $user The authenticated user
     * @param string $channelIdentifier Channel ID or username (starts with @)
     * @return array|null Channel information
     */
    public function getChannelByIdentifier(User $user, string $channelIdentifier): ?array
    {
        Log::info('Looking up channel by identifier', [
            'user_id' => $user->id,
            'channel_identifier' => $channelIdentifier,
            'is_username' => str_starts_with($channelIdentifier, '@')
        ]);

        // If it starts with @, it's a username
        if (str_starts_with($channelIdentifier, '@')) {
            $username = substr($channelIdentifier, 1); // Remove @ symbol
            
            Log::info('Searching for channel by username', [
                'user_id' => $user->id,
                'username' => $username
            ]);

            // Try method 1: forUsername (legacy, might not work for new handles)
            $response = $this->tokenService->makeYouTubeApiRequest($user, 'channels', [
                'part' => 'snippet,statistics',
                'forUsername' => $username
            ]);

            Log::info('Channel lookup by username response', [
                'user_id' => $user->id,
                'username' => $username,
                'response_received' => $response !== null,
                'has_items' => $response && isset($response['items']),
                'item_count' => $response && isset($response['items']) ? count($response['items']) : 0,
                'full_response' => $response
            ]);

            // If forUsername didn't work, try search API
            if (!$response || empty($response['items'])) {
                Log::info('forUsername failed, trying search API', [
                    'user_id' => $user->id,
                    'username' => $username
                ]);

                $searchResponse = $this->tokenService->makeYouTubeApiRequest($user, 'search', [
                    'part' => 'snippet',
                    'q' => '@' . $username,
                    'type' => 'channel',
                    'maxResults' => 5
                ]);

                Log::info('Channel search API response', [
                    'user_id' => $user->id,
                    'username' => $username,
                    'search_response_received' => $searchResponse !== null,
                    'search_has_items' => $searchResponse && isset($searchResponse['items']),
                    'search_item_count' => $searchResponse && isset($searchResponse['items']) ? count($searchResponse['items']) : 0,
                    'search_full_response' => $searchResponse
                ]);

                if ($searchResponse && isset($searchResponse['items']) && !empty($searchResponse['items'])) {
                    // Get the channel ID from search results and fetch full channel data
                    $channelId = $searchResponse['items'][0]['snippet']['channelId'];
                    
                    Log::info('Found channel ID from search, fetching full channel data', [
                        'user_id' => $user->id,
                        'username' => $username,
                        'found_channel_id' => $channelId
                    ]);

                    $response = $this->tokenService->makeYouTubeApiRequest($user, 'channels', [
                        'part' => 'snippet,statistics',
                        'id' => $channelId
                    ]);

                    Log::info('Channel data fetch by ID after search', [
                        'user_id' => $user->id,
                        'channel_id' => $channelId,
                        'response_received' => $response !== null,
                        'has_items' => $response && isset($response['items']),
                        'full_response' => $response
                    ]);
                }
            }

            return $response;
        } else {
            // Assume it's a channel ID
            Log::info('Searching for channel by ID', [
                'user_id' => $user->id,
                'channel_id' => $channelIdentifier
            ]);

            $response = $this->tokenService->makeYouTubeApiRequest($user, 'channels', [
                'part' => 'snippet,statistics',
                'id' => $channelIdentifier
            ]);

            Log::info('Channel lookup by ID response', [
                'user_id' => $user->id,
                'channel_id' => $channelIdentifier,
                'response_received' => $response !== null,
                'has_items' => $response && isset($response['items']),
                'item_count' => $response && isset($response['items']) ? count($response['items']) : 0,
                'full_response' => $response
            ]);

            return $response;
        }
    }

    /**
     * Get all channels the user is subscribed to
     * 
     * @param User $user The authenticated user
     * @param int $maxResults Maximum number of subscriptions to retrieve (default 50, max 50)
     * @return array|null List of subscriptions or null on error
     */
    public function getUserSubscriptions(User $user, int $maxResults = 50): ?array
    {
        try {
            $response = $this->tokenService->makeYouTubeApiRequest($user, 'subscriptions', [
                'part' => 'snippet,subscriberSnippet',
                'mine' => 'true',
                'maxResults' => min($maxResults, 50), // YouTube API limit is 50
                'order' => 'alphabetical'
            ]);

            if ($response && isset($response['items'])) {
                Log::info('Retrieved user subscriptions', [
                    'user_id' => $user->id,
                    'subscription_count' => count($response['items']),
                    'total_results' => $response['pageInfo']['totalResults'] ?? 0
                ]);
                return $response;
            }

            Log::warning('No subscriptions found for user', [
                'user_id' => $user->id
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error retrieving user subscriptions', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get a simplified list of subscription data
     * 
     * @param User $user The authenticated user
     * @param int $maxResults Maximum number of subscriptions to retrieve
     * @return array Simplified array of subscription data
     */
    public function getUserSubscriptionsList(User $user, int $maxResults = 50): array
    {
        $subscriptions = $this->getUserSubscriptions($user, $maxResults);
        
        if (!$subscriptions || empty($subscriptions['items'])) {
            return [];
        }

        $result = [];
        foreach ($subscriptions['items'] as $subscription) {
            $result[] = [
                'channel_id' => $subscription['snippet']['resourceId']['channelId'],
                'channel_title' => $subscription['snippet']['title'],
                'description' => $subscription['snippet']['description'] ?? '',
                'thumbnail' => $subscription['snippet']['thumbnails']['default']['url'] ?? null,
                'subscribed_at' => $subscription['snippet']['publishedAt'] ?? null,
            ];
        }

        return $result;
    }
} 