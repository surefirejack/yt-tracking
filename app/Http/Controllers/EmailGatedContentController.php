<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\EmailSubscriberContent;
use App\Models\EmailVerificationRequest;
use App\Models\SubscriberAccessRecord;
use App\Services\EmailServiceProvider\EmailServiceProviderManager;
use App\Jobs\ProcessEmailVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailGatedContentController extends Controller
{
    private EmailServiceProviderManager $espManager;

    public function __construct(EmailServiceProviderManager $espManager)
    {
        $this->espManager = $espManager;
    }

    /**
     * Display the email access form or the content if already verified
     */
    public function show(Request $request, $channelname, $slug)
    {
        try {
            // Handle route model binding - both parameters might be objects
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
                $channelnameStr = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelnameStr = $channelname;
                $tenant = Tenant::whereHas('ytChannel', function ($query) use ($channelnameStr) {
                    $query->where(\DB::raw('LOWER(REPLACE(handle, "@", ""))'), '=', strtolower($channelnameStr));
                })->first();

                if (!$tenant) {
                    return redirect()->route('home')->with('error', 'Channel not found.');
                }
            }

            if ($slug instanceof EmailSubscriberContent) {
                $content = $slug;
            } else {
                $content = EmailSubscriberContent::where('tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->first();

                if (!$content) {
                    return redirect()->route('home')->with('error', 'Content not found.');
                }
            }

            // Verify content belongs to tenant
            if ($content->tenant_id !== $tenant->id) {
                return redirect()->route('home')->with('error', 'Invalid request.');
            }

            // Check if user already has access via cookie
            $accessRecord = $this->checkExistingAccess($request, $tenant, $content);
            
            if ($accessRecord) {
                Log::info('User has existing access via cookie', [
                    'tenant_id' => $tenant->id,
                    'content_id' => $content->id,
                    'access_record_id' => $accessRecord->id
                ]);

                return $this->showContent($tenant, $content, $channelnameStr, $accessRecord);
            }

            // Show the email access form
            return $this->showAccessForm($request, $tenant, $content, $channelnameStr);

        } catch (\Exception $e) {
            Log::error('Error loading email-gated content', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('home')->with('error', 'An error occurred.');
        }
    }

    /**
     * Handle email submission for content access
     */
    public function submitEmail(Request $request, $channelname, $slug): JsonResponse
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'subscribe_agreed' => 'required|accepted',
                'utm_content' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find tenant and content
            $tenant = $this->findTenantByChannelName($channelname);
            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Channel not found.'
                ], 404);
            }

            $content = EmailSubscriberContent::where('tenant_id', $tenant->id)
                ->where('slug', $slug)
                ->first();

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found.'
                ], 404);
            }

            $email = strtolower(trim($request->email));

            // Check for existing access
            $existingAccess = $this->checkEmailAccess($email, $tenant, $content);
            if ($existingAccess) {
                // User already has access, set cookie and redirect
                $response = response()->json([
                    'success' => true,
                    'message' => 'You already have access to this content!',
                    'action' => 'immediate_access'
                ]);

                return $this->setAccessCookie($response, $existingAccess);
            }

            // Check for recent verification request to prevent spam
            $recentRequest = EmailVerificationRequest::where('email', $email)
                ->where('content_id', $content->id)
                ->where('tenant_id', $tenant->id)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($recentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting another verification email.'
                ], 429);
            }

            // Create verification request
            $verificationRequest = EmailVerificationRequest::create([
                'email' => $email,
                'verification_token' => Str::random(64),
                'content_id' => $content->id,
                'tenant_id' => $tenant->id,
                'expires_at' => now()->addHours(2),
            ]);

            // Dispatch job to handle ESP interactions and send email
            ProcessEmailVerification::dispatch(
                $verificationRequest,
                $request->utm_content
            );

            Log::info('Email verification request created', [
                'tenant_id' => $tenant->id,
                'content_id' => $content->id,
                'verification_request_id' => $verificationRequest->id,
                'email' => $email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check your email for a verification link!',
                'action' => 'email_sent'
            ]);

        } catch (\Exception $e) {
            Log::error('Error submitting email for verification', [
                'channelname' => $channelname,
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle email verification from link clicks
     */
    public function verifyEmail(Request $request, string $token): View
    {
        try {
            $verificationRequest = EmailVerificationRequest::where('verification_token', $token)
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->first();

            if (!$verificationRequest) {
                Log::warning('Invalid or expired verification token', ['token' => $token]);
                
                return view('diamonds.email-verification.expired', [
                    'message' => 'This verification link is invalid or has expired.'
                ]);
            }

            $tenant = $verificationRequest->tenant;
            $content = $verificationRequest->content;

            // Mark as verified
            $verificationRequest->update(['verified_at' => now()]);

            // Handle ESP integration (add subscriber and tag)
            $this->handlePostVerificationESP($verificationRequest);

            // Create or update access record (will be updated by ESP job)
            $accessRecord = SubscriberAccessRecord::firstOrCreate(
                [
                    'email' => $verificationRequest->email,
                    'tenant_id' => $tenant->id,
                ],
                [
                    'tags_json' => [$content->required_tag_id],
                    'cookie_token' => Str::random(64),
                    'last_verified_at' => now(),
                ]
            );

            Log::info('Email verification completed', [
                'tenant_id' => $tenant->id,
                'content_id' => $content->id,
                'verification_request_id' => $verificationRequest->id,
                'access_record_id' => $accessRecord->id
            ]);

            // Set access cookie and redirect to content
            $channelname = $tenant->getChannelName() ?? 'channel';
            $contentUrl = route('email-gated-content.show', [
                'channelname' => $channelname,
                'slug' => $content->slug
            ]);

            $response = response()->view('diamonds.email-verification.success', [
                'tenant' => $tenant,
                'content' => $content,
                'channelname' => $channelname,
                'contentUrl' => $contentUrl,
                'message' => 'Email verified successfully! You now have access to the content.'
            ]);

            return $this->setAccessCookie($response, $accessRecord);

        } catch (\Exception $e) {
            Log::error('Error verifying email token', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return view('diamonds.email-verification.expired', [
                'message' => 'An error occurred during verification. Please try again.'
            ]);
        }
    }

    /**
     * Show the email access form
     */
    private function showAccessForm(Request $request, Tenant $tenant, EmailSubscriberContent $content, string $channelname): View
    {
        // Get ESP provider for tag name
        $tagName = $content->required_tag_id;
        $provider = $this->espManager->getProviderForTenant($tenant);
        
        if ($provider && $content->required_tag_id) {
            try {
                $tags = $provider->getTags();
                $tag = collect($tags)->firstWhere('id', $content->required_tag_id);
                $tagName = $tag['name'] ?? $content->required_tag_id;
            } catch (\Exception $e) {
                Log::warning('Failed to fetch tag name for content', [
                    'content_id' => $content->id,
                    'tag_id' => $content->required_tag_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Get video info if utm_content parameter matches
        $videoThumbnail = null;
        $videoTitle = null;
        $utmContent = $request->get('utm_content');
        
        if ($utmContent && $tenant->ytChannel) {
            $video = $tenant->ytChannel->ytVideos()
                ->where('video_id', $utmContent)
                ->first();
            
            if ($video) {
                $videoThumbnail = $video->thumbnail_url;
                $videoTitle = $video->title;
            }
        }

        return view('diamonds.email-gated-content.access-form', [
            'tenant' => $tenant,
            'content' => $content,
            'channelname' => $channelname,
            'tagName' => $tagName,
            'videoThumbnail' => $videoThumbnail,
            'videoTitle' => $videoTitle,
            'utmContent' => $utmContent,
        ]);
    }

    /**
     * Show the content after verification
     */
    private function showContent(Tenant $tenant, EmailSubscriberContent $content, string $channelname, SubscriberAccessRecord $accessRecord): View
    {
        return view('diamonds.email-gated-content.content', [
            'tenant' => $tenant,
            'content' => $content,
            'channelname' => $channelname,
            'accessRecord' => $accessRecord,
        ]);
    }

    /**
     * Check if user has existing access via cookie
     */
    private function checkExistingAccess(Request $request, Tenant $tenant, EmailSubscriberContent $content): ?SubscriberAccessRecord
    {
        $cookieToken = $request->cookie('email_access_' . $tenant->id);
        
        if (!$cookieToken) {
            return null;
        }

        $accessRecord = SubscriberAccessRecord::where('cookie_token', $cookieToken)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$accessRecord) {
            return null;
        }

        // Check if cookie has expired
        $cookieDuration = $tenant->email_verification_cookie_duration_days ?? 30;
        if ($accessRecord->last_verified_at->addDays($cookieDuration)->isPast()) {
            Log::info('Access cookie expired', [
                'access_record_id' => $accessRecord->id,
                'last_verified' => $accessRecord->last_verified_at,
                'cookie_duration_days' => $cookieDuration
            ]);
            return null;
        }

        // Check if user has required tag
        $userTags = $accessRecord->tags_json ?? [];
        if (!in_array($content->required_tag_id, $userTags)) {
            // Try to sync tags from ESP
            return $this->syncUserTags($accessRecord, $tenant, $content);
        }

        return $accessRecord;
    }

    /**
     * Check if email has access via existing verification
     */
    private function checkEmailAccess(string $email, Tenant $tenant, EmailSubscriberContent $content): ?SubscriberAccessRecord
    {
        $accessRecord = SubscriberAccessRecord::where('email', $email)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$accessRecord) {
            return null;
        }

        // Check if user has required tag
        $userTags = $accessRecord->tags_json ?? [];
        if (!in_array($content->required_tag_id, $userTags)) {
            // Try to sync tags from ESP
            return $this->syncUserTags($accessRecord, $tenant, $content);
        }

        return $accessRecord;
    }

    /**
     * Sync user tags from ESP
     */
    private function syncUserTags(SubscriberAccessRecord $accessRecord, Tenant $tenant, EmailSubscriberContent $content): ?SubscriberAccessRecord
    {
        try {
            $provider = $this->espManager->getProviderForTenant($tenant);
            if (!$provider) {
                return null;
            }

            $subscriber = $provider->getSubscriber($accessRecord->email);
            if (!$subscriber) {
                return null;
            }

            $subscriberTags = $provider->getSubscriberTags($subscriber['id']);
            $accessRecord->update([
                'tags_json' => $subscriberTags,
                'last_verified_at' => now(),
            ]);

            // Check if user now has required tag
            if (in_array($content->required_tag_id, $subscriberTags)) {
                Log::info('User tags synced from ESP, access granted', [
                    'access_record_id' => $accessRecord->id,
                    'required_tag' => $content->required_tag_id,
                    'user_tags' => $subscriberTags
                ]);
                return $accessRecord;
            }

        } catch (\Exception $e) {
            Log::error('Failed to sync user tags from ESP', [
                'access_record_id' => $accessRecord->id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Set access cookie on response
     */
    private function setAccessCookie($response, SubscriberAccessRecord $accessRecord)
    {
        $cookieDuration = $accessRecord->tenant->email_verification_cookie_duration_days ?? 30;
        $cookieName = 'email_access_' . $accessRecord->tenant_id;
        
        return $response->withCookie(cookie(
            $cookieName,
            $accessRecord->cookie_token,
            $cookieDuration * 24 * 60, // Convert days to minutes
            '/',
            null,
            true, // secure
            true  // httpOnly
        ));
    }

    /**
     * Handle ESP integration after email verification
     */
    private function handlePostVerificationESP(EmailVerificationRequest $verificationRequest): void
    {
        try {
            $processVerificationJob = new ProcessEmailVerification($verificationRequest);
            $processVerificationJob->handlePostVerificationESP();
        } catch (\Exception $e) {
            Log::error('Failed to handle post-verification ESP integration', [
                'verification_request_id' => $verificationRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Find tenant by channel name
     */
    private function findTenantByChannelName(string $channelname): ?Tenant
    {
        return Tenant::whereHas('ytChannel', function ($query) use ($channelname) {
            $query->where(\DB::raw('LOWER(REPLACE(handle, "@", ""))'), '=', strtolower($channelname));
        })->first();
    }
} 