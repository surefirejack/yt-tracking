<?php

namespace App\Listeners;

use App\Events\YouTubeChannelAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use App\Models\YtVideo;
use App\Jobs\GetYouTubeVideoDetailsSupadata;

class GetYouTubeChannelVideos implements ShouldQueue
{

    public $limit = 20;


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
            
            // Collect jobs for batch processing
            $jobs = [];
            
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
                        'title' => 'Loading...', // Temporary title, will be updated by GetYouTubeVideoDetails job
                    ]
                );
                
                // Only add job for newly created videos to avoid duplicates
                if ($video->wasRecentlyCreated) {
                    Log::info('Adding video to batch processing: ' . $video->video_id);
                    $jobs[] = new GetYouTubeVideoDetailsSupadata($video);
                }
            }
            
            // Dispatch jobs as a batch if we have any
            if (!empty($jobs)) {
                $batch = Bus::batch($jobs)
                    ->name("YouTube Video Details - Channel: {$ytChannel->name}")
                    ->allowFailures()
                    ->dispatch();
                
                Log::info("Successfully dispatched batch of " . count($jobs) . " video detail jobs for channel: {$channelIdentifier}", [
                    'batch_id' => $batch->id,
                    'job_count' => count($jobs)
                ]);
            } else {
                Log::info("No new videos to process for channel: {$channelIdentifier}");
            }
            
            Log::info("Successfully processed videos for YouTube channel: {$channelIdentifier}");
        } catch (\Exception $e) {
            Log::error("Failed to process YouTube channel {$ytChannel->handle}: " . $e->getMessage());
        }
    }
}
