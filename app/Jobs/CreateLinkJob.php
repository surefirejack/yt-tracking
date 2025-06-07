<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CreateLinkJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry after 10, 30, then 60 seconds

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Link $link
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Mark link as processing
            $this->link->update(['status' => 'processing']);

            // Get Dub API configuration
            $apiKey = config('services.dub.api_key');
            $createLinkUrl = config('services.dub.create_link_url');

            if (!$apiKey || !$createLinkUrl) {
                throw new Exception('Dub API configuration is missing. Please check DUB_API_KEY and DUB_CREATE_LINK_URL in your environment.');
            }

            // Prepare the payload
            $payload = [
                'url' => $this->link->original_url,
                'tenantId' => (string) $this->link->tenant_id, // Convert to string as Dub API expects string
                'externalId' => (string) $this->link->id, // Convert to string as Dub might expect string
            ];

            // Add optional fields if they exist
            if ($this->link->title) {
                $payload['title'] = $this->link->title;
            }
            
            if ($this->link->description) {
                $payload['description'] = $this->link->description;
            }

            // Add tags if they exist in the relationship
            if ($this->link->tagModels()->exists()) {
                $tagNames = $this->link->tagModels()->pluck('name')->toArray();
                if (!empty($tagNames)) {
                    $payload['tagNames'] = $tagNames;
                }
            }

            // Add UTM parameters if they exist
            if ($this->link->utm_source) $payload['utm_source'] = $this->link->utm_source;
            if ($this->link->utm_medium) $payload['utm_medium'] = $this->link->utm_medium;
            if ($this->link->utm_campaign) $payload['utm_campaign'] = $this->link->utm_campaign;
            if ($this->link->utm_term) $payload['utm_term'] = $this->link->utm_term;
            if ($this->link->utm_content) $payload['utm_content'] = $this->link->utm_content;

            // Add boolean fields
            $payload['trackConversion'] = (bool) $this->link->track_conversion;
            $payload['archived'] = (bool) $this->link->archived;
            $payload['publicStats'] = (bool) $this->link->public_stats;
            $payload['proxy'] = (bool) $this->link->proxy;
            $payload['rewrite'] = (bool) $this->link->rewrite;
            $payload['doIndex'] = (bool) $this->link->do_index;

            // Add other optional fields if they exist
            if ($this->link->password) $payload['password'] = $this->link->password;
            if ($this->link->expires_at) $payload['expiresAt'] = $this->link->expires_at->toISOString();
            if ($this->link->expired_url) $payload['expiredUrl'] = $this->link->expired_url;
            if ($this->link->image) $payload['image'] = $this->link->image;
            if ($this->link->video) $payload['video'] = $this->link->video;
            if ($this->link->ios) $payload['ios'] = $this->link->ios;
            if ($this->link->android) $payload['android'] = $this->link->android;
            if ($this->link->geo) $payload['geo'] = $this->link->geo;
            if ($this->link->webhook_ids) $payload['webhookIds'] = $this->link->webhook_ids;

            // Make the API call to Dub
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($createLinkUrl, $payload);

            Log::info('Creating link via Dub API', [
                'link_id' => $this->link->id,
                'tenant_id' => $this->link->tenant_id,
                'external_id' => $this->link->id,
                'payload_tenant_id' => $payload['tenantId'] ?? null,
                'payload_external_id' => $payload['externalId'] ?? null,
                'original_url' => $this->link->original_url,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update the link with response data
                $this->link->update([
                    'dub_id' => $data['id'] ?? null,
                    'domain' => $data['domain'] ?? null,
                    'key' => $data['key'] ?? null,
                    'url' => $data['url'] ?? null,
                    'short_link' => $data['shortLink'] ?? null,
                    'archived' => $data['archived'] ?? false,
                    'expires_at' => isset($data['expiresAt']) ? \Carbon\Carbon::parse($data['expiresAt']) : null,
                    'expired_url' => $data['expiredUrl'] ?? null,
                    'password' => $data['password'] ?? null,
                    'track_conversion' => $data['trackConversion'] ?? false,
                    'proxy' => $data['proxy'] ?? false,
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image' => $data['image'] ?? null,
                    'video' => $data['video'] ?? null,
                    'utm_source' => $data['utm_source'] ?? null,
                    'utm_medium' => $data['utm_medium'] ?? null,
                    'utm_campaign' => $data['utm_campaign'] ?? null,
                    'utm_term' => $data['utm_term'] ?? null,
                    'utm_content' => $data['utm_content'] ?? null,
                    'rewrite' => $data['rewrite'] ?? false,
                    'do_index' => $data['doIndex'] ?? false,
                    'ios' => $data['ios'] ?? null,
                    'android' => $data['android'] ?? null,
                    'geo' => $data['geo'] ?? null,
                    'test_variants' => $data['testVariants'] ?? null,
                    'test_started_at' => isset($data['testStartedAt']) ? \Carbon\Carbon::parse($data['testStartedAt']) : null,
                    'test_completed_at' => isset($data['testCompletedAt']) ? \Carbon\Carbon::parse($data['testCompletedAt']) : null,
                    'user_id_dub' => $data['userId'] ?? null,
                    'project_id' => $data['projectId'] ?? null,
                    'program_id' => $data['programId'] ?? null,
                    'folder_id' => $data['folderId'] ?? null,
                    'external_id' => $data['externalId'] ?? null,
                    'tenant_id_dub' => $data['tenantId'] ?? null,
                    'public_stats' => $data['publicStats'] ?? false,
                    'clicks' => $data['clicks'] ?? 0,
                    'last_clicked' => isset($data['lastClicked']) ? \Carbon\Carbon::parse($data['lastClicked']) : null,
                    'leads' => $data['leads'] ?? 0,
                    'sales' => $data['sales'] ?? 0,
                    'sale_amount' => $data['saleAmount'] ?? 0,
                    'comments' => $data['comments'] ?? null,
                    'partner_id' => $data['partnerId'] ?? null,
                    'tags' => $data['tags'] ?? null,
                    'identifier' => $data['identifier'] ?? null,
                    'tag_id' => $data['tagId'] ?? null,
                    'webhook_ids' => $data['webhookIds'] ?? null,
                    'qr_code' => $data['qrCode'] ?? null,
                    'workspace_id' => $data['workspaceId'] ?? null,
                    'status' => 'completed',
                    'error_message' => null,
                ]);

                Log::info('Link created successfully', [
                    'link_id' => $this->link->id,
                    'tenant_id' => $this->link->tenant_id,
                    'dub_id' => $data['id'] ?? null,
                    'short_link' => $data['shortLink'] ?? null,
                ]);
            } else {
                throw new Exception('Dub API request failed: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Failed to create link', [
                'link_id' => $this->link->id,
                'tenant_id' => $this->link->tenant_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Update link with error status
            $this->link->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CreateLinkJob permanently failed', [
            'link_id' => $this->link->id,
            'tenant_id' => $this->link->tenant_id,
            'error' => $exception->getMessage(),
        ]);

        $this->link->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
