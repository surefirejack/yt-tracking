<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateLinkJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry after 10, 30, then 60 seconds

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Link $link,
        public array $updateData
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
            $updateLinkUrl = config('services.dub.update_link_url');

            if (!$apiKey || !$updateLinkUrl) {
                throw new Exception('Dub API configuration is missing. Please check DUB_API_KEY and DUB_UPDATE_LINK_URL in your environment.');
            }

            // Make the API call to Dub to update the link
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->patch($updateLinkUrl . '/' . $this->link->dub_id, $this->updateData);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update the link with response data
                $this->link->update([
                    'dub_id' => $data['id'] ?? $this->link->dub_id,
                    'domain' => $data['domain'] ?? $this->link->domain,
                    'key' => $data['key'] ?? $this->link->key,
                    'url' => $data['url'] ?? $this->link->url,
                    'short_link' => $data['shortLink'] ?? $this->link->short_link,
                    'archived' => $data['archived'] ?? $this->link->archived,
                    'expires_at' => isset($data['expiresAt']) ? \Carbon\Carbon::parse($data['expiresAt']) : $this->link->expires_at,
                    'expired_url' => $data['expiredUrl'] ?? $this->link->expired_url,
                    'password' => $data['password'] ?? $this->link->password,
                    'track_conversion' => $data['trackConversion'] ?? $this->link->track_conversion,
                    'proxy' => $data['proxy'] ?? $this->link->proxy,
                    'title' => $data['title'] ?? $this->link->title,
                    'description' => $data['description'] ?? $this->link->description,
                    'image' => $data['image'] ?? $this->link->image,
                    'video' => $data['video'] ?? $this->link->video,
                    'utm_source' => $data['utm_source'] ?? $this->link->utm_source,
                    'utm_medium' => $data['utm_medium'] ?? $this->link->utm_medium,
                    'utm_campaign' => $data['utm_campaign'] ?? $this->link->utm_campaign,
                    'utm_term' => $data['utm_term'] ?? $this->link->utm_term,
                    'utm_content' => $data['utm_content'] ?? $this->link->utm_content,
                    'rewrite' => $data['rewrite'] ?? $this->link->rewrite,
                    'do_index' => $data['doIndex'] ?? $this->link->do_index,
                    'ios' => $data['ios'] ?? $this->link->ios,
                    'android' => $data['android'] ?? $this->link->android,
                    'geo' => $data['geo'] ?? $this->link->geo,
                    'test_variants' => $data['testVariants'] ?? $this->link->test_variants,
                    'test_started_at' => isset($data['testStartedAt']) ? \Carbon\Carbon::parse($data['testStartedAt']) : $this->link->test_started_at,
                    'test_completed_at' => isset($data['testCompletedAt']) ? \Carbon\Carbon::parse($data['testCompletedAt']) : $this->link->test_completed_at,
                    'user_id_dub' => $data['userId'] ?? $this->link->user_id_dub,
                    'project_id' => $data['projectId'] ?? $this->link->project_id,
                    'program_id' => $data['programId'] ?? $this->link->program_id,
                    'folder_id' => $data['folderId'] ?? $this->link->folder_id,
                    'external_id' => $data['externalId'] ?? $this->link->external_id,
                    'tenant_id_dub' => $data['tenantId'] ?? $this->link->tenant_id_dub,
                    'public_stats' => $data['publicStats'] ?? $this->link->public_stats,
                    'clicks' => $data['clicks'] ?? $this->link->clicks,
                    'last_clicked' => isset($data['lastClicked']) ? \Carbon\Carbon::parse($data['lastClicked']) : $this->link->last_clicked,
                    'leads' => $data['leads'] ?? $this->link->leads,
                    'sales' => $data['sales'] ?? $this->link->sales,
                    'sale_amount' => $data['saleAmount'] ?? $this->link->sale_amount,
                    'comments' => $data['comments'] ?? $this->link->comments,
                    'partner_id' => $data['partnerId'] ?? $this->link->partner_id,
                    'tags' => $data['tags'] ?? $this->link->tags,
                    'identifier' => $data['identifier'] ?? $this->link->identifier,
                    'tag_id' => $data['tagId'] ?? $this->link->tag_id,
                    'webhook_ids' => $data['webhookIds'] ?? $this->link->webhook_ids,
                    'qr_code' => $data['qrCode'] ?? $this->link->qr_code,
                    'workspace_id' => $data['workspaceId'] ?? $this->link->workspace_id,
                    'status' => 'completed',
                    'error_message' => null,
                ]);

                Log::info('Link updated successfully', [
                    'link_id' => $this->link->id,
                    'tenant_id' => $this->link->tenant_id,
                    'dub_id' => $this->link->dub_id,
                    'short_link' => $this->link->short_link,
                ]);
            } else {
                throw new Exception('Dub API request failed: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Failed to update link', [
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
        Log::error('UpdateLinkJob permanently failed', [
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
