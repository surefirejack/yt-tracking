<?php

namespace App\Jobs;

use App\Models\EmailVerificationRequest;
use App\Models\SubscriberAccessRecord;
use App\Services\EmailServiceProvider\EmailServiceProviderManager;
use App\Mail\EmailVerificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProcessEmailVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public EmailVerificationRequest $verificationRequest;
    public ?string $utmContent;
    public bool $espOnly;
    public ?int $accessRecordId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(EmailVerificationRequest $verificationRequest, ?string $utmContent = null, bool $espOnly = false, ?int $accessRecordId = null)
    {
        $this->verificationRequest = $verificationRequest;
        $this->utmContent = $utmContent;
        $this->espOnly = $espOnly;
        $this->accessRecordId = $accessRecordId;
    }

    public function handle(EmailServiceProviderManager $espManager): void
    {
        try {
            Log::info('Processing email verification', [
                'verification_request_id' => $this->verificationRequest->id,
                'tenant_id' => $this->verificationRequest->tenant_id,
                'content_id' => $this->verificationRequest->content_id,
                'esp_only' => $this->espOnly,
            ]);

            // If this is ESP-only processing (post-verification), handle ESP integration only
            if ($this->espOnly) {
                $this->handlePostVerificationESP();
                return;
            }

            $tenant = $this->verificationRequest->tenant;
            $content = $this->verificationRequest->content;
            $email = $this->verificationRequest->email;

            // Get ESP provider
            $provider = $espManager->getProviderForTenant($tenant);
            
            if (!$provider) {
                Log::warning('No ESP provider configured for tenant', [
                    'tenant_id' => $tenant->id,
                    'verification_request_id' => $this->verificationRequest->id
                ]);
                
                // Still send verification email even without ESP
                $this->sendVerificationEmail();
                return;
            }

            // Check if subscriber already exists in ESP
            $existingSubscriber = null;
            try {
                $existingSubscriber = $provider->checkSubscriber($email);
                Log::info('ESP subscriber check completed', [
                    'email' => $email,
                    'exists' => !empty($existingSubscriber),
                    'verification_request_id' => $this->verificationRequest->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to check subscriber in ESP', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'verification_request_id' => $this->verificationRequest->id
                ]);
            }

            // If subscriber exists, log their status but always require email verification
            if ($existingSubscriber) {
                try {
                    $subscriberTags = $existingSubscriber['tags'] ?? [];
                    
                    if (in_array($content->required_tag_id, $subscriberTags)) {
                        Log::info('Existing subscriber has required tag, will grant access after email verification', [
                            'email' => $email,
                            'required_tag' => $content->required_tag_id,
                            'subscriber_tags' => $subscriberTags,
                            'verification_request_id' => $this->verificationRequest->id
                        ]);
                    } else {
                        Log::info('Existing subscriber missing required tag, will add tag after verification', [
                            'email' => $email,
                            'required_tag' => $content->required_tag_id,
                            'subscriber_tags' => $subscriberTags,
                            'verification_request_id' => $this->verificationRequest->id
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to check subscriber tags in ESP', [
                        'email' => $email,
                        'subscriber_id' => $existingSubscriber['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'verification_request_id' => $this->verificationRequest->id
                    ]);
                }
            }

            // Send verification email for new subscribers or existing ones without the tag
            $this->sendVerificationEmail();

            Log::info('Email verification processing completed', [
                'verification_request_id' => $this->verificationRequest->id,
                'email_sent' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing email verification', [
                'verification_request_id' => $this->verificationRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Record ESP error in verification request
            $this->verificationRequest->update([
                'esp_error' => $e->getMessage(),
                'esp_error_at' => now()
            ]);

            // Still try to send verification email as fallback
            try {
                $this->sendVerificationEmail();
            } catch (\Exception $mailError) {
                Log::error('Failed to send fallback verification email', [
                    'verification_request_id' => $this->verificationRequest->id,
                    'error' => $mailError->getMessage()
                ]);
                throw $mailError;
            }
        }
    }

    /**
     * Handle ESP integration after email verification
     */
    public function handlePostVerificationESP(): void
    {
        try {
            $tenant = $this->verificationRequest->tenant;
            $content = $this->verificationRequest->content;
            $email = $this->verificationRequest->email;

            $espManager = app(EmailServiceProviderManager::class);
            $provider = $espManager->getProviderForTenant($tenant);

            if (!$provider) {
                Log::warning('No ESP provider configured for post-verification processing', [
                    'tenant_id' => $tenant->id,
                    'verification_request_id' => $this->verificationRequest->id
                ]);
                return;
            }

            // Track the tags we know this subscriber should have
            $knownTags = [];

            // Check if subscriber exists
            $subscriberCheck = $provider->checkSubscriber($email);
            
            if (!$subscriberCheck['is_subscribed']) {
                // Add new subscriber with the required tag
                Log::info('Adding new subscriber to ESP with required tag', [
                    'email' => $email,
                    'tag_id' => $content->required_tag_id,
                    'verification_request_id' => $this->verificationRequest->id
                ]);

                $addResult = $provider->addSubscriber($email, [$content->required_tag_id]);
                
                if (!$addResult['success']) {
                    Log::error('Failed to add subscriber to ESP', [
                        'email' => $email,
                        'tag_id' => $content->required_tag_id,
                        'error' => $addResult['error'] ?? 'Unknown error',
                        'verification_request_id' => $this->verificationRequest->id
                    ]);
                    return;
                }

                Log::info('Successfully added new subscriber with tag', [
                    'email' => $email,
                    'tag_id' => $content->required_tag_id,
                    'verification_request_id' => $this->verificationRequest->id,
                    'subscriber_id' => $addResult['subscriber_id'] ?? 'unknown'
                ]);

                // Mark access record as completed since ESP call succeeded
                $this->markAccessRecordCompleted($addResult['subscriber_id'] ?? null);
                return; // Exit early since we've successfully processed
            } else {
                // Use existing subscriber and their current tags
                $subscriber = [
                    'id' => $subscriberCheck['subscriber_id'],
                    'email' => $email,
                ];
                // Get existing tags for this subscriber
                $knownTags = array_column($subscriberCheck['tags'] ?? [], 'id');
            }

            // Add required tag to subscriber (if they don't already have it)
            if ($content->required_tag_id) {
                $hasRequiredTag = in_array($content->required_tag_id, $knownTags);
                
                if ($hasRequiredTag) {
                    Log::info('Subscriber already has required tag', [
                        'email' => $email,
                        'subscriber_id' => $subscriber['id'],
                        'tag_id' => $content->required_tag_id,
                        'verification_request_id' => $this->verificationRequest->id
                    ]);
                } else {
                    Log::info('Adding required tag to subscriber', [
                        'email' => $email,
                        'subscriber_id' => $subscriber['id'],
                        'tag_id' => $content->required_tag_id,
                        'verification_request_id' => $this->verificationRequest->id
                    ]);

                    $tagResult = $provider->addTagToSubscriber($email, $content->required_tag_id);
                    
                    if (!$tagResult) {
                        Log::error('Failed to add tag to subscriber', [
                            'email' => $email,
                            'subscriber_id' => $subscriber['id'],
                            'tag_id' => $content->required_tag_id,
                            'verification_request_id' => $this->verificationRequest->id
                        ]);
                    } else {
                        Log::info('Successfully added tag to subscriber', [
                            'email' => $email,
                            'subscriber_id' => $subscriber['id'],
                            'tag_id' => $content->required_tag_id,
                            'verification_request_id' => $this->verificationRequest->id
                        ]);
                        
                        // Mark access record as completed since ESP call succeeded
                        $this->markAccessRecordCompleted($subscriber['id']);
                        return; // Exit early since we've successfully processed
                    }
                }
            }

            // No need for fallback update since tags are set at creation
            // and we only update status when ESP succeeds

        } catch (\Exception $e) {
            Log::error('Error in post-verification ESP processing', [
                'verification_request_id' => $this->verificationRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(): void
    {
        try {
            $verificationUrl = route('email-verification.verify', [
                'tenantId' => $this->verificationRequest->tenant_id,
                'token' => $this->verificationRequest->verification_token
            ]);

            Mail::to($this->verificationRequest->email)
                ->send(new EmailVerificationMail(
                    $this->verificationRequest,
                    $verificationUrl,
                    $this->utmContent
                ));

            Log::info('Verification email sent', [
                'email' => $this->verificationRequest->email,
                'verification_request_id' => $this->verificationRequest->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'email' => $this->verificationRequest->email,
                'verification_request_id' => $this->verificationRequest->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mark access record as completed after successful ESP operation
     */
    private function markAccessRecordCompleted(?string $subscriberId = null): void
    {
        try {
            // Find access record - use direct ID if available, otherwise search by email
            $accessRecord = null;
            if ($this->accessRecordId) {
                $accessRecord = SubscriberAccessRecord::find($this->accessRecordId);
            } else {
                // Fallback to finding by email and tenant
                $accessRecord = SubscriberAccessRecord::where('email', $this->verificationRequest->email)
                    ->where('tenant_id', $this->verificationRequest->tenant_id)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if ($accessRecord) {
                // Mark as completed since ESP operation succeeded
                $updateData = [
                    'access_check_status' => 'completed',
                    'access_check_completed_at' => now(),
                    'last_verified_at' => now(),
                ];
                
                // Store subscriber ID if provided
                if ($subscriberId) {
                    $updateData['subscriber_id'] = $subscriberId;
                }
                
                $accessRecord->update($updateData);

                Log::info('Access record marked as completed after ESP success', [
                    'email' => $this->verificationRequest->email,
                    'access_record_id' => $accessRecord->id,
                    'verification_request_id' => $this->verificationRequest->id,
                    'subscriber_id' => $subscriberId,
                    'found_via' => $this->accessRecordId ? 'direct_id' : 'email_search'
                ]);
            } else {
                Log::error('Access record not found for ESP completion update', [
                    'email' => $this->verificationRequest->email,
                    'tenant_id' => $this->verificationRequest->tenant_id,
                    'verification_request_id' => $this->verificationRequest->id,
                    'access_record_id' => $this->accessRecordId,
                    'search_method' => $this->accessRecordId ? 'direct_id' : 'email_search'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to mark access record as completed', [
                'email' => $this->verificationRequest->email,
                'verification_request_id' => $this->verificationRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessEmailVerification job failed', [
            'verification_request_id' => $this->verificationRequest->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // You could implement notification to admins here
        // or mark the verification request as failed
    }
} 