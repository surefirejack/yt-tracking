<?php

namespace App\Services;

use App\Models\SubscriberUser;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class YouTubeSubscriptionService extends YouTubeApiService
{
    /**
     * Check if a subscriber user is subscribed to a tenant's YouTube channel
     * 
     * @param SubscriberUser $subscriberUser The subscriber to check
     * @param Tenant $tenant The tenant whose channel to check
     * @return bool True if subscribed, false otherwise
     */
    public function verifySubscription(SubscriberUser $subscriberUser, Tenant $tenant): bool
    {
        try {
            // Check if subscription verification is cached and still valid
            if ($subscriberUser->isSubscriptionCacheValid()) {
                Log::info('Using cached subscription verification', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'tenant_id' => $tenant->id,
                    'cached_until' => $subscriberUser->subscription_verified_at
                        ->addDays($tenant->subscription_cache_days)
                        ->toDateTimeString()
                ]);
                return $subscriberUser->isSubscriptionVerified();
            }

            // Get tenant's YouTube channel ID
            $tenantChannelId = $tenant->ytChannel?->channel_id;
            
            if (!$tenantChannelId) {
                Log::error('Tenant does not have a YouTube channel configured', [
                    'tenant_id' => $tenant->id,
                    'subscriber_user_id' => $subscriberUser->id
                ]);
                return false;
            }

            // Get valid OAuth token from subscriber
            $accessToken = $subscriberUser->getValidOAuthToken();
            
            if (!$accessToken) {
                Log::error('No valid OAuth token available for subscription verification', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'tenant_id' => $tenant->id,
                    'has_access_token' => !empty($subscriberUser->oauth_access_token),
                    'has_refresh_token' => !empty($subscriberUser->oauth_refresh_token),
                    'token_expired' => $subscriberUser->oauth_token_expires_at ? 
                        $subscriberUser->oauth_token_expires_at->isPast() : 'no_token_date'
                ]);
                return false;
            }

            // Check subscription directly using the stored token
            $isSubscribed = $this->checkSubscriptionDirectly($subscriberUser, $tenantChannelId, $accessToken);

            // Update subscription verification cache
            if ($isSubscribed) {
                $subscriberUser->markSubscriptionVerified();
                Log::info('Subscription verified and cached', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'tenant_id' => $tenant->id,
                    'tenant_channel_id' => $tenantChannelId
                ]);
            } else {
                $subscriberUser->clearSubscriptionVerification();
                Log::info('Subscription not found, cache cleared', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'tenant_id' => $tenant->id,
                    'tenant_channel_id' => $tenantChannelId
                ]);
            }

            return $isSubscribed;

        } catch (\Exception $e) {
            Log::error('Error verifying subscription', [
                'subscriber_user_id' => $subscriberUser->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // On error, don't change cache but return false for safety
            return false;
        }
    }

    /**
     * Force refresh subscription verification (bypass cache)
     * 
     * @param SubscriberUser $subscriberUser The subscriber to check
     * @param Tenant $tenant The tenant whose channel to check
     * @return bool True if subscribed, false otherwise
     */
    public function forceVerifySubscription(SubscriberUser $subscriberUser, Tenant $tenant): bool
    {
        // Clear existing cache first
        $subscriberUser->clearSubscriptionVerification();
        
        Log::info('Force verifying subscription (bypassing cache)', [
            'subscriber_user_id' => $subscriberUser->id,
            'tenant_id' => $tenant->id
        ]);
        
        return $this->verifySubscription($subscriberUser, $tenant);
    }

    /**
     * Create a temporary User object from SubscriberUser for API calls
     * This is needed because the existing YouTube API methods expect a User model
     * 
     * @param SubscriberUser $subscriberUser
     * @return \App\Models\User|null
     */
    private function createTemporaryUserFromSubscriber(SubscriberUser $subscriberUser): ?\App\Models\User
    {
        Log::info('Creating temporary user for subscription verification', [
            'subscriber_user_id' => $subscriberUser->id,
            'google_id' => $subscriberUser->google_id,
            'email' => $subscriberUser->email,
            'has_stored_token' => !empty($subscriberUser->oauth_access_token),
            'token_expires_at' => $subscriberUser->oauth_token_expires_at?->toDateTimeString()
        ]);

        // Get a valid OAuth token from the subscriber user
        $validToken = $subscriberUser->getValidOAuthToken();
        
        if (!$validToken) {
            Log::error('No valid OAuth token available for subscriber', [
                'subscriber_user_id' => $subscriberUser->id,
                'has_access_token' => !empty($subscriberUser->oauth_access_token),
                'has_refresh_token' => !empty($subscriberUser->oauth_refresh_token),
                'token_expired' => $subscriberUser->oauth_token_expires_at ? 
                    $subscriberUser->oauth_token_expires_at->isPast() : 'no_token_date'
            ]);
            return null;
        }

        // Create a temporary user instance (not saved to database)
        $tempUser = new \App\Models\User();
        $tempUser->id = 'temp_' . $subscriberUser->id; // Fix: Proper temporary ID
        $tempUser->email = $subscriberUser->email;
        $tempUser->name = $subscriberUser->name;
        
        // Store the valid token in the temporary user
        $tempUser->_temp_youtube_token = $validToken;
        $tempUser->_temp_youtube_refresh_token = $subscriberUser->oauth_refresh_token;
        $tempUser->_temp_youtube_token_expires_at = $subscriberUser->oauth_token_expires_at?->toDateTimeString();

        Log::info('Temporary user created with stored OAuth tokens', [
            'temp_user_id' => $tempUser->id,
            'has_valid_token' => !empty($validToken),
            'token_preview' => substr($validToken, 0, 20) . '...'
        ]);
        
        return $tempUser;
    }

    /**
     * Set temporary OAuth tokens for a user (used during subscriber auth flow)
     * 
     * @param \App\Models\User $tempUser
     * @param array $tokens OAuth tokens from Google
     */
    public function setTemporaryTokens(\App\Models\User $tempUser, array $tokens): void
    {
        // Store tokens temporarily for this request
        // We'll use a custom property since this is a temporary user
        $tempUser->_temp_youtube_token = $tokens['access_token'] ?? null;
        $tempUser->_temp_youtube_refresh_token = $tokens['refresh_token'] ?? null;
        $tempUser->_temp_youtube_token_expires_at = isset($tokens['expires_in']) 
            ? Carbon::now()->addSeconds($tokens['expires_in'])->toDateTimeString()
            : null;

        Log::info('Set temporary tokens for subscription verification', [
            'temp_user_id' => $tempUser->id,
            'has_access_token' => !empty($tokens['access_token']),
            'has_refresh_token' => !empty($tokens['refresh_token'])
        ]);
    }

    /**
     * Override token retrieval for temporary users during subscription verification
     */
    public function getValidAccessToken(\App\Models\User $user): ?string
    {
        // Check if this is a temporary user with custom token storage
        if (str_starts_with($user->id, 'temp_') && isset($user->_temp_youtube_token)) {
            Log::info('Using temporary token for subscription verification', [
                'temp_user_id' => $user->id
            ]);
            return $user->_temp_youtube_token;
        }

        // Fall back to parent implementation for regular users
        return parent::getValidAccessToken($user);
    }

    /**
     * Check subscription with enhanced error handling for rate limits and API failures
     * 
     * @param \App\Models\User $user
     * @param string $channelId
     * @return bool
     */
    private function isSubscribedWithErrorHandling(\App\Models\User $user, string $channelId): bool
    {
        try {
            $subscriptionData = $this->checkSubscription($user, $channelId);
            return $subscriptionData !== null;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $e->response;
            
            if ($response && $response->status() === 403) {
                $errorData = $response->json();
                $reason = $errorData['error']['errors'][0]['reason'] ?? 'unknown';
                
                if ($reason === 'quotaExceeded' || $reason === 'rateLimitExceeded') {
                    Log::warning('YouTube API quota/rate limit exceeded', [
                        'user_id' => $user->id,
                        'channel_id' => $channelId,
                        'reason' => $reason,
                        'response' => $errorData
                    ]);
                    
                    // Return null to indicate API unavailable (don't change cache)
                    throw new \Exception('YouTube API quota exceeded. Please try again later.');
                }
            }
            
            if ($response && $response->status() === 401) {
                Log::error('YouTube API authentication failed', [
                    'user_id' => $user->id,
                    'channel_id' => $channelId,
                    'status' => $response->status()
                ]);
                
                throw new \Exception('YouTube authentication failed. Please login again.');
            }
            
            Log::error('YouTube API request failed', [
                'user_id' => $user->id,
                'channel_id' => $channelId,
                'status' => $response ? $response->status() : 'unknown',
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('YouTube API temporarily unavailable. Please try again later.');
            
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), 'rate limit')) {
                throw $e; // Re-throw quota/rate limit errors
            }
            
            Log::error('Unexpected error during subscription check', [
                'user_id' => $user->id,
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Subscription verification failed. Please try again.');
        }
    }

    /**
     * Override makeYouTubeApiRequest to handle temporary users with stored tokens
     */
    public function makeYouTubeApiRequest(\App\Models\User $user, string $endpoint, array $params = []): ?array
    {
        // Check if this is a temporary user with custom token storage
        if (str_starts_with($user->id, 'temp_') && isset($user->_temp_youtube_token)) {
            Log::info('Making YouTube API request with temporary user tokens', [
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'params' => $params
            ]);

            $accessToken = $user->_temp_youtube_token;
            
            if (!$accessToken) {
                Log::error('No valid access token available for YouTube API request', [
                    'user_id' => $user->id,
                    'endpoint' => $endpoint
                ]);
                return null;
            }

            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ])->get("https://www.googleapis.com/youtube/v3/{$endpoint}", $params);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    Log::info('YouTube API request successful', [
                        'user_id' => $user->id,
                        'endpoint' => $endpoint,
                        'status' => $response->status(),
                        'has_items' => isset($data['items']),
                        'item_count' => isset($data['items']) ? count($data['items']) : 0
                    ]);
                    
                    return $data;
                } else {
                    Log::error('YouTube API request failed', [
                        'user_id' => $user->id,
                        'endpoint' => $endpoint,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    
                    return null;
                }
            } catch (\Exception $e) {
                Log::error('YouTube API request exception', [
                    'user_id' => $user->id,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);
                
                return null;
            }
        }

        // For regular users, delegate to parent class method
        // Create a new YouTubeTokenService instance since we can't access the private property
        $tokenService = new \App\Services\YouTubeTokenService();
        return $tokenService->makeYouTubeApiRequest($user, $endpoint, $params);
    }

    /**
     * Override checkSubscription to use our custom API request method
     */
    public function checkSubscription(\App\Models\User $user, string $channelId): ?array
    {
        try {
            Log::info('Checking subscription for channel', [
                'user_id' => $user->id,
                'channel_id' => $channelId
            ]);

            $response = $this->makeYouTubeApiRequest($user, 'subscriptions', [
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
     * Check subscription directly using the stored token
     * 
     * @param SubscriberUser $subscriberUser The subscriber to check
     * @param string $channelId The channel ID to check
     * @param string $accessToken The access token for the channel
     * @return bool True if subscribed, false otherwise
     */
    private function checkSubscriptionDirectly(SubscriberUser $subscriberUser, string $channelId, string $accessToken): bool
    {
        try {
            Log::info('Checking subscription directly using stored token', [
                'subscriber_user_id' => $subscriberUser->id,
                'channel_id' => $channelId,
                'email' => $subscriberUser->email
            ]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get("https://www.googleapis.com/youtube/v3/subscriptions", [
                'part' => 'snippet,subscriberSnippet',
                'forChannelId' => $channelId,
                'mine' => 'true'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Subscription check API response', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'channel_id' => $channelId,
                    'status' => $response->status(),
                    'has_items' => isset($data['items']),
                    'item_count' => isset($data['items']) ? count($data['items']) : 0,
                    'response_data' => $data
                ]);

                if (isset($data['items']) && count($data['items']) > 0) {
                    Log::info('User IS subscribed to channel', [
                        'subscriber_user_id' => $subscriberUser->id,
                        'channel_id' => $channelId,
                        'subscription_data' => $data['items'][0]
                    ]);
                    return true;
                }

                Log::info('User is NOT subscribed to channel', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'channel_id' => $channelId
                ]);
                return false;
            } else {
                Log::error('YouTube API subscription check failed', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'channel_id' => $channelId,
                    'status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Error checking subscription directly', [
                'subscriber_user_id' => $subscriberUser->id,
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
} 