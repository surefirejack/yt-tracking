<?php

namespace App\Listeners;

use App\Events\YouTubeChannelAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetYouTubeChannelDetails implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // Supadata API will be used for YouTube data
    }

    /**
     * Handle the event.
     */
    public function handle(YouTubeChannelAdded $event): void
    {
        Log::info('GetYouTubeChannelDetails listener handling event for channel: ' . $event->ytChannel->handle);
        $ytChannel = $event->ytChannel;
        
        try {
            // The channel identifier can be handle, URL, or ID
            $channelIdentifier = $ytChannel->handle ?? $ytChannel->url ?? $ytChannel->channel_id;
            
            if (!$channelIdentifier) {
                Log::error("No valid identifier found for channel");
                return;
            }

            // Update the requested date before making the API call
            $ytChannel->update([
                'last_update_requested_date' => now(),
            ]);

            // Make request to Supadata API
            $response = Http::withHeaders([
                'x-api-key' => config('services.supadata.api_key')
            ])->get(
                config('services.supadata.base_url') . 'youtube/channel',
                [
                    'id' => $channelIdentifier
                ]
            );
            
            if (!$response->successful()) {
                Log::error("Failed to get channel details from Supadata: " . $response->body());
                return;
            }
            
            $data = $response->json();
            
            // Extract handle from URL if user provided a full URL
            $handle = $ytChannel->handle;
            $url = $ytChannel->url;
            
            // If we have a URL but no handle, try to extract handle from URL
            if (!$handle && $url) {
                // Extract handle from various YouTube URL formats
                // https://www.youtube.com/@channelhandle
                // https://youtube.com/@channelhandle  
                // https://www.youtube.com/c/channelname
                // https://www.youtube.com/channel/UCxxxxxxxxx
                if (preg_match('/@([^\/\?&]+)/', $url, $matches)) {
                    $handle = '@' . $matches[1];
                } elseif (preg_match('/\/c\/([^\/\?&]+)/', $url, $matches)) {
                    $handle = '@' . $matches[1]; // Convert /c/ format to @handle
                }
            }
            
            // If we have a handle but no URL, construct the URL
            if ($handle && !$url) {
                $url = 'https://www.youtube.com/' . $handle;
            }
            
            // Get the proper channel URL from API response or construct it
            $properChannelUrl = $url;
            if (isset($data['handle']) && $data['handle']) {
                $properChannelUrl = 'https://www.youtube.com/@' . ltrim($data['handle'], '@');
                // Also update the handle if we got it from API
                if (!$handle) {
                    $handle = '@' . ltrim($data['handle'], '@');
                }
            } elseif (isset($data['id']) && $data['id']) {
                // Fallback to channel ID URL if no handle available
                $properChannelUrl = 'https://www.youtube.com/channel/' . $data['id'];
            }
            
            // Update YtChannel with the retrieved data
            $ytChannel->update([
                'channel_id' => $data['id'] ?? null,
                'name' => $data['name'] ?? 'Unknown Channel',
                'description' => $data['description'] ?? null,
                'logo_image_url' => $data['thumbnail'] ?? null,
                'banner_image_url' => $data['banner'] ?? null,
                'subscribers_count' => $data['subscriberCount'] ?? 0,
                'videos_count' => $data['videoCount'] ?? 0,
                'handle' => $handle,
                'url' => $properChannelUrl,
                'last_update_received_date' => now(),
            ]);
            
            Log::info("Successfully updated YouTube channel details for: {$channelIdentifier}");
        } catch (\Exception $e) {
            Log::error("Failed to process YouTube channel {$ytChannel->handle}: " . $e->getMessage());
        }
    }
}
