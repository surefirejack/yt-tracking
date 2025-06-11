<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class YouTubeTokenService
{
    /**
     * Get a valid YouTube access token for the user, refreshing if necessary
     */
    public function getValidAccessToken(User $user): ?string
    {
        // Get current tokens
        $accessToken = $user->userParameters()
            ->where('name', 'youtube_token')
            ->first()?->value;
            
        $refreshToken = $user->userParameters()
            ->where('name', 'youtube_refresh_token')
            ->first()?->value;
            
        // Get token expiry time if we stored it
        $tokenExpiry = $user->userParameters()
            ->where('name', 'youtube_token_expires_at')
            ->first()?->value;
            
        if (!$accessToken || !$refreshToken) {
            Log::warning('YouTube tokens not found for user', ['user_id' => $user->id]);
            return null;
        }
        
        // Check if token is expired or will expire soon (5 minutes buffer)
        $now = Carbon::now();
        $expiresAt = $tokenExpiry ? Carbon::parse($tokenExpiry) : $now->copy()->subMinute(); // Assume expired if no expiry time
        
        if ($expiresAt->lessThan($now->addMinutes(5))) {
            Log::info('YouTube token expired or expiring soon, refreshing', [
                'user_id' => $user->id,
                'expires_at' => $expiresAt->toDateTimeString()
            ]);
            
            $newTokens = $this->refreshAccessToken($refreshToken);
            
            if ($newTokens) {
                $this->storeTokens($user, $newTokens);
                return $newTokens['access_token'];
            } else {
                Log::error('Failed to refresh YouTube token', ['user_id' => $user->id]);
                return null;
            }
        }
        
        return $accessToken;
    }
    
    /**
     * Refresh the access token using the refresh token
     */
    private function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('YouTube token refreshed successfully');
                
                return [
                    'access_token' => $data['access_token'],
                    'expires_in' => $data['expires_in'] ?? 3600, // Default 1 hour
                    // Note: refresh_token is usually not returned on refresh, keep the existing one
                ];
            } else {
                Log::error('YouTube token refresh failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('YouTube token refresh exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Store the new tokens in user parameters
     */
    private function storeTokens(User $user, array $tokens): void
    {
        // Update access token
        $user->userParameters()->updateOrCreate(
            ['name' => 'youtube_token'],
            ['value' => $tokens['access_token']]
        );
        
        // Store expiry time
        $expiresAt = Carbon::now()->addSeconds($tokens['expires_in']);
        $user->userParameters()->updateOrCreate(
            ['name' => 'youtube_token_expires_at'],
            ['value' => $expiresAt->toDateTimeString()]
        );
        
        Log::info('YouTube tokens updated', [
            'user_id' => $user->id,
            'expires_at' => $expiresAt->toDateTimeString()
        ]);
    }
    
    /**
     * Make an authenticated request to YouTube API with automatic token refresh
     */
    public function makeYouTubeApiRequest(User $user, string $endpoint, array $params = []): ?array
    {
        Log::info('Making YouTube API request', [
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'params' => $params
        ]);

        $accessToken = $this->getValidAccessToken($user);
        
        if (!$accessToken) {
            Log::error('No valid access token available for YouTube API request', [
                'user_id' => $user->id,
                'endpoint' => $endpoint
            ]);
            return null;
        }
        
        try {
            $url = 'https://www.googleapis.com/youtube/v3/' . $endpoint;
            
            Log::info('Sending HTTP request to YouTube API', [
                'user_id' => $user->id,
                'url' => $url,
                'params' => $params,
                'has_token' => !empty($accessToken)
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get($url, $params);
            
            Log::info('YouTube API response received', [
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_size' => strlen($response->body()),
                'response_body' => $response->body()
            ]);
            
            if ($response->successful()) {
                $jsonData = $response->json();
                Log::info('YouTube API response parsed successfully', [
                    'user_id' => $user->id,
                    'endpoint' => $endpoint,
                    'has_items' => isset($jsonData['items']),
                    'item_count' => isset($jsonData['items']) ? count($jsonData['items']) : 0,
                    'parsed_data' => $jsonData
                ]);
                return $jsonData;
            } else {
                Log::error('YouTube API request failed', [
                    'user_id' => $user->id,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('YouTube API request exception', [
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Check if user has valid YouTube connection
     */
    public function hasValidConnection(User $user): bool
    {
        $accessToken = $this->getValidAccessToken($user);
        return $accessToken !== null;
    }
} 