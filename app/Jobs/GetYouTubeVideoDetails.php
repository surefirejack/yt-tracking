<?php

namespace App\Jobs;

use App\Models\YtVideo;
use Google_Client;
use Google_Service_YouTube;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\RequestYtTranscriptSupadataViaMakedotcom;

class GetYouTubeVideoDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ytVideo;
    protected $attempts = 0;
    protected $maxAttempts = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(YtVideo $ytVideo)
    {
        try {
            file_put_contents(storage_path('logs/direct-test.log'), "Direct file write test\n", FILE_APPEND);
            
            Log::info('GetYouTubeVideoDetails job constructed', [
                'video_id' => $ytVideo->video_id,
                'channel_id' => $ytVideo->yt_channel_id
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
                'channel_id' => $this->ytVideo->yt_channel_id
            ]);

            // Initialize the Google client
            $client = new Google_Client();
            $client->setDeveloperKey(config('services.youtube.api_key'));

            // Create the YouTube service
            $youtube = new Google_Service_YouTube($client);

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
                
                // Request the most useful parts
                $parts = [
                    'snippet',
                    'contentDetails',
                    'statistics',
                    'status'
                ];
                
                $params = [
                    'id' => $videoId
                ];

                // Execute the videos.list request
                $response = $youtube->videos->listVideos(implode(',', $parts), $params);
                
                if (empty($response->getItems())) {
                    Log::error("Video not found for ID: {$videoId}");
                    return;
                }
                
                // Get the video data
                $videoData = $response->getItems()[0];
                
                // Format the duration (convert ISO 8601 to seconds)
                $duration = $this->convertDuration($videoData->getContentDetails()->getDuration());
                
                // Get or create thumbnail URLs
                $thumbnails = $videoData->getSnippet()->getThumbnails();
                $thumbnailUrl = null;
                
                // Try to get the highest quality thumbnail available
                if ($thumbnails->getMaxres()) {
                    $thumbnailUrl = $thumbnails->getMaxres()->getUrl();
                } elseif ($thumbnails->getStandard()) {
                    $thumbnailUrl = $thumbnails->getStandard()->getUrl();
                } elseif ($thumbnails->getHigh()) {
                    $thumbnailUrl = $thumbnails->getHigh()->getUrl();
                } elseif ($thumbnails->getMedium()) {
                    $thumbnailUrl = $thumbnails->getMedium()->getUrl();
                } elseif ($thumbnails->getDefault()) {
                    $thumbnailUrl = $thumbnails->getDefault()->getUrl();
                }

                Log::info('Description: ' . $videoData->getSnippet()->getDescription());
                
                // Count URLs in the description
                $description = $videoData->getSnippet()->getDescription();
                $urlCount = $this->countUrlsInText($description);
                
                // Update the video record with detailed information
                $this->ytVideo->update([
                    'title' => $videoData->getSnippet()->getTitle(),
                    'description' => $description,
                    'published_at' => date('Y-m-d H:i:s', strtotime($videoData->getSnippet()->getPublishedAt())),
                    'thumbnail_url' => $thumbnailUrl,
                    'length' => $duration,
                    'views' => $videoData->getStatistics()->getViewCount() ?? 0,
                    'likes' => $videoData->getStatistics()->getLikeCount() ?? 0,
                    'links_found' => $urlCount,
                    // 'comments' => $videoData->getStatistics()->getCommentCount() ?? 0,
                    // 'channel_title' => $videoData->getSnippet()->getChannelTitle(),
                    // 'tags' => json_encode($videoData->getSnippet()->getTags() ?? []),
                    // 'category_id' => $videoData->getSnippet()->getCategoryId(),
                    // 'definition' => $videoData->getContentDetails()->getDefinition(),
                    // 'caption' => $videoData->getContentDetails()->getCaption() == 'true',
                    // 'licensed_content' => $videoData->getContentDetails()->getLicensedContent(),
                    // 'dimension' => $videoData->getContentDetails()->getDimension(),
                    // 'projection' => $videoData->getContentDetails()->getProjection(),
                    // 'privacy_status' => $videoData->getStatus()->getPrivacyStatus(),
                    // 'license' => $videoData->getStatus()->getLicense(),
                    // 'embeddable' => $videoData->getStatus()->getEmbeddable(),
                    // 'made_for_kids' => $videoData->getStatus()->getMadeForKids() ?? false,
                    // 'processed_at' => now(),
                ]);
                
                Log::info("Successfully processed video details for ID: {$videoId}");

      
                
            } catch (\Exception $e) {
                Log::error("YouTube API Error for video ID {$this->ytVideo->video_id}: " . $e->getMessage());
                
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
     * Convert ISO 8601 duration to seconds
     * 
     * @param string $duration ISO 8601 duration format (e.g. PT1H2M3S)
     * @return int Duration in seconds
     */
    protected function convertDuration($duration)
    {
        $matches = [];
        // Match hours, minutes, seconds
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
        
        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
        $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
        
        return $hours * 3600 + $minutes * 60 + $seconds;
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