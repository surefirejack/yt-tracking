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

            // Create a temporary user object for API calls using subscriber's Google data
            $tempUser = $this->createTemporaryUserFromSubscriber($subscriberUser);
            
            if (!$tempUser) {
                Log::error('Failed to create temporary user for subscription check', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'tenant_id' => $tenant->id
                ]);
                return false;
            }

            // Check subscription using parent class method with error handling
            $isSubscribed = $this->isSubscribedWithErrorHandling($tempUser, $tenantChannelId);

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
        // We need to get the subscriber's OAuth tokens stored during login
        // These should be stored in session or a temporary storage system
        
        // For now, we'll need to retrieve these from the session data
        // This will be implemented in the SubscriberAuthController
        
        Log::info('Creating temporary user for subscription verification', [
            'subscriber_user_id' => $subscriberUser->id,
            'google_id' => $subscriberUser->google_id,
            'email' => $subscriberUser->email
        ]);

        // Create a temporary user instance (not saved to database)
        $tempUser = new \App\Models\User();
        $tempUser->id = 'temp_' . $subscriberUser->id; // Temporary ID
        $tempUser->email = $subscriberUser->email;
        $tempUser->name = $subscriberUser->name;
        
        // The OAuth tokens will need to be set by the auth controller
        // using a method like setTemporaryTokens()
        
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
} 