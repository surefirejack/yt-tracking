<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\YtVideo;

class GetYouTubeVideoDetailsSupadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $ytVideo;
    protected $attempts = 0;
    protected $maxAttempts = 3;

    public function __construct(YtVideo $ytVideo)
    {
        $this->ytVideo = $ytVideo;
    }

    public function handle(): void
    {
        try {
            // Get the webhook URL from services config
            $webhookUrl = config('services.makedotcom.webhook_url.request_yt_video_details');

            if (!$webhookUrl) {
                throw new \Exception('Make.com webhook URL not configured');
            }

            // Log the webhook URL for debugging
            Log::info("Make.com webhook URL: {$webhookUrl}");

            // Prepare the payload
            $payload = [
                'id' => $this->ytVideo->id,
                'video_id' => $this->ytVideo->video_id,
                'env' => config('app.env'),
            ];

            // Log the exact payload being sent
            Log::info("Sending payload to Make.com", [
                'webhook_url' => $webhookUrl,
                'payload' => $payload,
                'video_id' => $this->ytVideo->id
            ]);

            // Send POST request to make.com
            $response = Http::post($webhookUrl, $payload);

            // Log detailed response information
            Log::info("Make.com response details", [
                'status_code' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
                'video_id' => $this->ytVideo->id
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to send request to make.com. Status: {$response->status()}, Body: {$response->body()}");
            }

            Log::info("Successfully sent video details request to make.com for video: {$this->ytVideo->id}");

        } catch (\Exception $e) {
            Log::error("Error in GetYouTubeVideoDetailsSupadata job: {$e->getMessage()}");
            $this->fail($e);
        }
    }
    
    protected function fail(\Throwable $exception): void
    {
        if ($this->attempts < $this->maxAttempts) { 
            $this->attempts++;
            $this->fail($exception);
        }
    }
}
