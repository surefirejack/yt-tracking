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
} 