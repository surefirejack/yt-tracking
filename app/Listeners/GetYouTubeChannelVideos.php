<?php

namespace App\Listeners;

use App\Events\YouTubeChannelAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\YtVideo;
use App\Jobs\GetYouTubeVideoDetails;

class GetYouTubeChannelVideos implements ShouldQueue
{

    public $limit = 5;


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
        Log::info('GetYouTubeChannelVideos listener handling event for channel: ' . $event->ytChannel->handle);
        $ytChannel = $event->ytChannel;
        
        try {
            // The channel identifier can be handle, URL, or ID
            $channelIdentifier = $ytChannel->handle ?? $ytChannel->url ?? $ytChannel->channel_id;
            
            if (!$channelIdentifier) {
                Log::error("No valid identifier found for channel");
                return;
            }

            Log::info('api key: ' . config('services.supadata.api_key'));
            
            // Make request to Supadata API
            $response = Http::withHeaders([
                'x-api-key' => config('services.supadata.api_key')
            ])->get(
                config('services.supadata.base_url') . 'youtube/channel/videos',
                [
                    'id' => $channelIdentifier,
                    'limit' => $this->limit
                ]
            );
            
            if (!$response->successful()) {
                Log::error("Failed to get videos from Supadata: " . $response->body());
                return;
            }
            
            $data = $response->json();
            
            // Process video IDs from the response
            foreach ($data['videoIds'] as $videoId) {
                // Create or update a YtVideo record
                $videoUrl = "https://www.youtube.com/watch?v=" . $videoId;
                
                // Check if video already exists to avoid duplicates
                $video = YtVideo::updateOrCreate(
                    [
                        'yt_channel_id' => $ytChannel->id,
                        'video_id' => $videoId,
                    ],
                    [
                        'url' => $videoUrl,
                    ]
                );
                
                // Only dispatch job for newly created videos to avoid duplicates
                if ($video->wasRecentlyCreated) {
                    Log::info('Sending video to GetYouTubeVideoDetails: ' . $video->video_id);
                    // Dispatch job to get more details about the video
                    GetYouTubeVideoDetails::dispatch($video);
                }
            }
            
            Log::info("Successfully processed videos for YouTube channel: {$channelIdentifier}");
        } catch (\Exception $e) {
            Log::error("Failed to process YouTube channel {$ytChannel->handle}: " . $e->getMessage());
        }
    }
}
