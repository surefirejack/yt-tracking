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

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    public function __construct(EmailVerificationRequest $verificationRequest, ?string $utmContent = null)
    {
        $this->verificationRequest = $verificationRequest;
        $this->utmContent = $utmContent;
    }

    public function handle(EmailServiceProviderManager $espManager): void
    {
        try {
            Log::info('Processing email verification', [
                'verification_request_id' => $this->verificationRequest->id,
                'tenant_id' => $this->verificationRequest->tenant_id,
                'content_id' => $this->verificationRequest->content_id,
            ]);

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

            // If subscriber exists, check if they have the required tag
            if ($existingSubscriber) {
                try {
                    $subscriberTags = $existingSubscriber['tags'] ?? [];
                    
                    if (in_array($content->required_tag_id, $subscriberTags)) {
                        Log::info('Existing subscriber has required tag, granting immediate access', [
                            'email' => $email,
                            'required_tag' => $content->required_tag_id,
                            'subscriber_tags' => $subscriberTags,
                            'verification_request_id' => $this->verificationRequest->id
                        ]);

                        // Create access record for immediate access
                        $this->createAccessRecord($subscriberTags);
                        
                        // Mark verification as completed
                        $this->verificationRequest->update(['verified_at' => now()]);
                        
                        // Send confirmation email instead of verification
                        $this->sendImmediateAccessEmail();
                        return;
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
                        'subscriber_id' => $existingSubscriber['id'],
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

            // Check if subscriber exists
            $subscriber = $provider->checkSubscriber($email);
            
            if (!$subscriber) {
                // Add new subscriber
                Log::info('Adding new subscriber to ESP', [
                    'email' => $email,
                    'verification_request_id' => $this->verificationRequest->id
                ]);

                $addResult = $provider->addSubscriber($email);
                
                if (!$addResult['success']) {
                    Log::error('Failed to add subscriber to ESP', [
                        'email' => $email,
                        'error' => $addResult['error'] ?? 'Unknown error',
                        'verification_request_id' => $this->verificationRequest->id
                    ]);
                    return;
                }

                $subscriber = $addResult['subscriber'];
            }

            // Add required tag to subscriber
            if ($content->required_tag_id) {
                Log::info('Adding required tag to subscriber', [
                    'email' => $email,
                    'subscriber_id' => $subscriber['id'],
                    'tag_id' => $content->required_tag_id,
                    'verification_request_id' => $this->verificationRequest->id
                ]);

                $tagResult = $provider->addTagToSubscriber($subscriber['id'], $content->required_tag_id);
                
                if (!$tagResult['success']) {
                    Log::error('Failed to add tag to subscriber', [
                        'email' => $email,
                        'subscriber_id' => $subscriber['id'],
                        'tag_id' => $content->required_tag_id,
                        'error' => $tagResult['error'] ?? 'Unknown error',
                        'verification_request_id' => $this->verificationRequest->id
                    ]);
                } else {
                    Log::info('Successfully added tag to subscriber', [
                        'email' => $email,
                        'subscriber_id' => $subscriber['id'],
                        'tag_id' => $content->required_tag_id,
                        'verification_request_id' => $this->verificationRequest->id
                    ]);
                }
            }

            // Get updated subscriber tags for access record
            $updatedSubscriber = $provider->checkSubscriber($email);
            $subscriberTags = $updatedSubscriber['tags'] ?? [];
            $this->createAccessRecord($subscriberTags);

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
     * Send immediate access email for existing subscribers
     */
    private function sendImmediateAccessEmail(): void
    {
        try {
            $tenant = $this->verificationRequest->tenant;
            $content = $this->verificationRequest->content;
            $channelname = $tenant->getChannelName() ?? 'channel';
            
            $contentUrl = route('email-gated-content.show', [
                'channelname' => $channelname,
                'slug' => $content->slug
            ]);

            // You could create a separate mailable for immediate access
            // For now, we'll use a simple notification approach
            Mail::raw(
                "Great news! You already have access to '{$content->title}'.\n\n" .
                "Click here to view your content: {$contentUrl}\n\n" .
                "Thanks for being a subscriber!",
                function ($message) {
                    $message->to($this->verificationRequest->email)
                        ->subject('Your content is ready!');
                }
            );

            Log::info('Immediate access email sent', [
                'email' => $this->verificationRequest->email,
                'verification_request_id' => $this->verificationRequest->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send immediate access email', [
                'email' => $this->verificationRequest->email,
                'verification_request_id' => $this->verificationRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create access record for the subscriber
     */
    private function createAccessRecord(array $subscriberTags): void
    {
        try {
            SubscriberAccessRecord::updateOrCreate(
                [
                    'email' => $this->verificationRequest->email,
                    'tenant_id' => $this->verificationRequest->tenant_id,
                ],
                [
                    'tags_json' => $subscriberTags,
                    'cookie_token' => Str::random(64),
                    'last_verified_at' => now(),
                ]
            );

            Log::info('Access record created/updated', [
                'email' => $this->verificationRequest->email,
                'tenant_id' => $this->verificationRequest->tenant_id,
                'tags' => $subscriberTags,
                'verification_request_id' => $this->verificationRequest->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create access record', [
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