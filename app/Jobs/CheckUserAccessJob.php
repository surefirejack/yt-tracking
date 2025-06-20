<?php

namespace App\Jobs;

use App\Models\EmailSubscriberContent;
use App\Models\SubscriberAccessRecord;
use App\Models\Tenant;
use App\Services\EmailServiceProvider\EmailServiceProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckUserAccessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 3; // Retry up to 3 times

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SubscriberAccessRecord $accessRecord,
        public EmailSubscriberContent $content,
        public Tenant $tenant
    ) {
        // Set the queue name for better organization
        $this->onQueue('esp-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(EmailServiceProviderManager $espManager): void
    {
        try {
            Log::info('Starting async access check', [
                'access_record_id' => $this->accessRecord->id,
                'content_id' => $this->content->id,
                'tenant_id' => $this->tenant->id,
                'required_tag_id' => $this->content->required_tag_id
            ]);

            // Update status to processing
            $this->accessRecord->update([
                'access_check_status' => 'processing',
                'access_check_started_at' => now(),
                'access_check_error' => null
            ]);

            // Get ESP provider
            $provider = $espManager->getProviderForTenant($this->tenant);
            if (!$provider) {
                throw new \Exception('ESP provider not configured for tenant');
            }

            // Get subscriber from ESP
            $subscriber = $provider->getSubscriber($this->accessRecord->email);
            if (!$subscriber) {
                Log::warning('Subscriber not found in ESP', [
                    'email' => $this->accessRecord->email,
                    'tenant_id' => $this->tenant->id
                ]);
                
                $this->accessRecord->update([
                    'access_check_status' => 'completed',
                    'access_check_completed_at' => now(),
                    'access_check_error' => 'Subscriber not found in ESP'
                ]);
                return;
            }

            // Get fresh tags from ESP
            $subscriberTags = $provider->getSubscriberTags($subscriber['id']);
            
            // Extract just the tag IDs for storage
            $tagIds = collect($subscriberTags)->pluck('id')->toArray();
            
            // Check if user has required tag
            $hasRequiredTag = in_array($this->content->required_tag_id, $tagIds);

            // Update access record with results
            $this->accessRecord->update([
                'tags_json' => $tagIds,
                'access_check_status' => 'completed',
                'last_verified_at' => now(),
                'access_check_completed_at' => now()
            ]);

            Log::info('Async access check completed', [
                'access_record_id' => $this->accessRecord->id,
                'has_required_tag' => $hasRequiredTag,
                'required_tag_id' => $this->content->required_tag_id,
                'user_tags' => $tagIds,
                'processing_time' => $this->accessRecord->access_check_completed_at->diffInSeconds($this->accessRecord->access_check_started_at)
            ]);

        } catch (\Exception $e) {
            Log::error('Async access check failed', [
                'access_record_id' => $this->accessRecord->id,
                'content_id' => $this->content->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update record with error status
            $this->accessRecord->update([
                'access_check_status' => 'failed',
                'access_check_completed_at' => now(),
                'access_check_error' => $e->getMessage()
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
        Log::error('CheckUserAccessJob failed permanently', [
            'access_record_id' => $this->accessRecord->id,
            'content_id' => $this->content->id,
            'error' => $exception->getMessage()
        ]);

        // Mark as permanently failed
        $this->accessRecord->update([
            'access_check_status' => 'failed',
            'access_check_completed_at' => now(),
            'access_check_error' => 'Permanent failure: ' . $exception->getMessage()
        ]);
    }
}
