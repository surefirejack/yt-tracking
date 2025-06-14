<?php

namespace App\Jobs;

use App\Models\YtVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class GetYouTubeVideoDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $ytVideo;
    protected $attempts = 0;
    protected $maxAttempts = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(YtVideo $ytVideo)
    {
        try {
            Log::info('GetYouTubeVideoDetails job constructed', [
                'video_id' => $ytVideo->video_id,
                'yt_channel_id' => $ytVideo->yt_channel_id
            ]);
            $this->ytVideo = $ytVideo;
        } catch (\Throwable $e) {
            error_log("Error in constructor: " . $e->getMessage());
            Log::error('Error in GetYouTubeVideoDetails constructor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('GetYouTubeVideoDetails job starting to handle', [
                'video_id' => $this->ytVideo->video_id,
                'yt_channel_id' => $this->ytVideo->yt_channel_id
            ]);

            try {
                // Extract video ID from the stored URL or use the video_id field
                $videoId = $this->ytVideo->video_id;
                
                Log::info('Processing video details', [
                    'video_id' => $videoId,
                    'url' => $this->ytVideo->url
                ]);
                
                if (empty($videoId)) {
                    // Try to extract from URL if needed
                    $url = $this->ytVideo->url;
                    parse_str(parse_url($url, PHP_URL_QUERY), $params);
                    $videoId = $params['v'] ?? null;
                    
                    if (empty($videoId)) {
                        Log::error("Failed to extract video ID from URL: {$url}");
                        return;
                    }
                }
                
                // Make request to Supadata API
                $response = Http::withHeaders([
                    'x-api-key' => config('services.supadata.api_key')
                ])->get(
                    config('services.supadata.base_url') . 'youtube/video',
                    [
                        'id' => $videoId
                    ]
                );
                
                if (!$response->successful()) {
                    Log::error("Failed to get video details from Supadata: " . $response->body());
                    
                    // Retry logic for transient errors
                    $this->attempts++;
                    if ($this->attempts < $this->maxAttempts) {
                        // Exponential backoff (1 min, 5 min, 15 min)
                        $backoff = pow(5, $this->attempts);
                        $this->release(now()->addMinutes($backoff));
                        
                        Log::info("Retrying video {$this->ytVideo->video_id} in {$backoff} minutes (attempt {$this->attempts} of {$this->maxAttempts})");
                    }
                    return;
                }
                
                $data = $response->json();
                
                Log::info('Video data received from Supadata', [
                    'video_id' => $videoId,
                    'title' => $data['title'] ?? 'No title',
                    'duration' => $data['duration'] ?? 0,
                    'views' => $data['viewCount'] ?? 0
                ]);

                Log::info('Description: ' . ($data['description'] ?? ''));
                
                // Count URLs in the description
                $description = $data['description'] ?? '';
                $urlCount = $this->countUrlsInText($description);
                
                // Convert uploadDate to proper format
                $publishedAt = null;
                if (!empty($data['uploadDate'])) {
                    $publishedAt = date('Y-m-d H:i:s', strtotime($data['uploadDate']));
                }
                
                // Update the video record with detailed information
                $this->ytVideo->update([
                    'title' => $data['title'] ?? null,
                    'description' => $description,
                    'published_at' => $publishedAt,
                    'thumbnail_url' => $data['thumbnail'] ?? null,
                    'length' => $data['duration'] ?? 0,
                    'views' => $data['viewCount'] ?? 0,
                    'likes' => $data['likeCount'] ?? 0,
                    'links_found' => $urlCount,
                ]);
                
                Log::info("Successfully processed video details for ID: {$videoId}");
                
            } catch (\Exception $e) {
                Log::error("Supadata API Error for video ID {$this->ytVideo->video_id}: " . $e->getMessage());
                
                // Retry logic for transient errors
                $this->attempts++;
                if ($this->attempts < $this->maxAttempts) {
                    // Exponential backoff (1 min, 5 min, 15 min)
                    $backoff = pow(5, $this->attempts);
                    $this->release(now()->addMinutes($backoff));
                    
                    Log::info("Retrying video {$this->ytVideo->video_id} in {$backoff} minutes (attempt {$this->attempts} of {$this->maxAttempts})");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in GetYouTubeVideoDetails handle method: " . $e->getMessage());
        }
    }
    
    /**
     * Count URLs in the given text
     * 
     * @param string $text The text to search for URLs
     * @return int Number of URLs found
     */
    protected function countUrlsInText($text)
    {
        if (empty($text)) {
            return 0;
        }
        
        // Regular expression to match URLs
        // This matches http://, https://, and www. patterns
        $urlPattern = '/\b(?:https?:\/\/|www\.)[^\s<>"{}|\\^`\[\]]+/i';
        
        // Find all matches
        preg_match_all($urlPattern, $text, $matches);
        
        // Count unique URLs (in case same URL appears multiple times)
        $uniqueUrls = array_unique($matches[0]);
        
        Log::info('URLs found in description', [
            'video_id' => $this->ytVideo->video_id,
            'url_count' => count($uniqueUrls),
            'urls' => $uniqueUrls
        ]);
        
        return count($uniqueUrls);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GetYouTubeVideoDetails job failed', [
            'video_id' => $this->ytVideo->video_id ?? 'unknown',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Prepare the job for serialization.
     */
    public function __sleep()
    {
        Log::info('GetYouTubeVideoDetails job being serialized', [
            'video_id' => $this->ytVideo->video_id ?? 'unknown'
        ]);
        return ['ytVideo', 'attempts', 'maxAttempts'];
    }

    /**
     * Handle job deserialization.
     */
    public function __wakeup()
    {
        Log::info('GetYouTubeVideoDetails job being deserialized', [
            'video_id' => $this->ytVideo->video_id ?? 'unknown'
        ]);
    }
}