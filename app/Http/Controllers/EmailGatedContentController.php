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
use Illuminate\Support\Facades\Storage;
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

            // Handle route model binding - both parameters might be objects
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
            } else {
                $tenant = $this->findTenantByChannelName($channelname);
                if (!$tenant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Channel not found.'
                    ], 404);
                }
            }

            if ($slug instanceof EmailSubscriberContent) {
                $content = $slug;
            } else {
                $content = EmailSubscriberContent::where('tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->first();
            }

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

            // Check ESP connectivity before dispatching job
            $espConnected = $this->checkESPConnectivity($tenant);
            
            // Dispatch job to handle ESP interactions and send email
            ProcessEmailVerification::dispatch(
                $verificationRequest,
                $request->utm_content
            );

            Log::info('Email verification request created', [
                'tenant_id' => $tenant->id,
                'content_id' => $content->id,
                'verification_request_id' => $verificationRequest->id,
                'email' => $email,
                'esp_connected' => $espConnected
            ]);

            $message = $espConnected 
                ? 'Check your email for a verification link!'
                : 'Check your email for a verification link! Note: Email list signup may be delayed due to temporary issues.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'action' => 'email_sent',
                'esp_status' => $espConnected ? 'connected' : 'degraded'
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
    public function verifyEmail(Request $request, string $token)
    {
        try {
            // First, find the verification request regardless of verified status
            $verificationRequest = EmailVerificationRequest::where('verification_token', $token)
                ->where('expires_at', '>', now())
                ->first();

            if (!$verificationRequest) {
                Log::warning('Invalid or expired verification token', ['token' => $token]);
                
                return view('email-verification.expired', [
                    'message' => 'This verification link is invalid or has expired.',
                    'tenant' => null // No tenant context available
                ]);
            }

            // If already verified, check if we can grant access via existing record
            if ($verificationRequest->verified_at) {
                Log::info('Verification token already used, checking for existing access', [
                    'token' => $token,
                    'verified_at' => $verificationRequest->verified_at
                ]);

                // Find existing access record
                $accessRecord = SubscriberAccessRecord::where('email', $verificationRequest->email)
                    ->where('tenant_id', $verificationRequest->tenant_id)
                    ->first();

                if ($accessRecord) {
                    // Grant access using existing record
                    $tenant = $verificationRequest->tenant;
                    $content = $verificationRequest->content;
                    $channelname = $tenant->getChannelName() ?? 'channel';
                    $contentUrl = route('email-gated-content.show', [
                        'channelname' => $channelname,
                        'slug' => $content->slug
                    ]);

                    $view = view('email-verification.success', [
                        'tenant' => $tenant,
                        'content' => $content,
                        'channelname' => $channelname,
                        'contentUrl' => $contentUrl,
                        'message' => 'You already have access to this content!'
                    ]);

                    return $this->setAccessCookie(response($view), $accessRecord);
                }

                // If no access record found, show expired (this shouldn't normally happen)
                return view('email-verification.expired', [
                    'message' => 'This verification link has already been used.',
                    'tenant' => $verificationRequest->tenant
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

            // Create view response like other methods in this controller
            $view = view('email-verification.success', [
                'tenant' => $tenant,
                'content' => $content,
                'channelname' => $channelname,
                'contentUrl' => $contentUrl,
                'message' => 'Email verified successfully! You now have access to the content.'
            ]);

            // Convert to response and set cookie
            return $this->setAccessCookie(response($view), $accessRecord);

        } catch (\Exception $e) {
            Log::error('Error verifying email token', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            // Try to get tenant from verification request if available
            $tenant = null;
            try {
                $verificationRequest = EmailVerificationRequest::where('verification_token', $token)->first();
                $tenant = $verificationRequest?->tenant;
            } catch (\Exception $tenantException) {
                // Ignore tenant lookup errors
            }

            return view('email-verification.expired', [
                'message' => 'An error occurred during verification. Please try again.',
                'tenant' => $tenant
            ]);
        }
    }

    /**
     * Handle secure file downloads for email-verified users.
     */
    public function download($channelname, $slug, string $filename)
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
                    return response()->json(['error' => 'Channel not found'], 404);
                }
            }

            if ($slug instanceof EmailSubscriberContent) {
                $content = $slug;
            } else {
                $content = EmailSubscriberContent::where('tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->first();

                if (!$content) {
                    return response()->json(['error' => 'Content not found'], 404);
                }
            }

            // Verify content belongs to tenant
            if ($content->tenant_id !== $tenant->id) {
                return response()->json(['error' => 'Invalid request'], 403);
            }

            // Check if user has access via cookie
            $accessRecord = $this->checkExistingAccess(request(), $tenant, $content);
            
            if (!$accessRecord) {
                return response()->json(['error' => 'Access denied. Please verify your email first.'], 403);
            }

            // Find the file path that matches the filename
            $matchedFilePath = null;
            $displayName = $filename;
            
            if ($content->file_paths && is_array($content->file_paths)) {
                foreach ($content->file_paths as $index => $path) {
                    if (basename($path) === $filename) {
                        $matchedFilePath = $path;
                        
                        // Get human-readable name if available
                        if ($content->file_names && isset($content->file_names[$index])) {
                            $displayName = $content->file_names[$index];
                        } else {
                            // Remove timestamp prefix if present
                            $displayName = preg_replace('/^\d{14}_/', '', $filename);
                        }
                        break;
                    }
                }
            }

            // Check if file path was found and file exists
            if (!$matchedFilePath || !Storage::disk('local')->exists($matchedFilePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            Log::info('Email-gated content file download', [
                'tenant_id' => $tenant->id,
                'content_id' => $content->id,
                'access_record_id' => $accessRecord->id,
                'filename' => $displayName,
                'actual_file' => $filename
            ]);

            // Get file path and MIME type
            $fullFilePath = Storage::disk('local')->path($matchedFilePath);
            $mimeType = Storage::disk('local')->mimeType($matchedFilePath);

            // Return file download response with human-readable name
            return response()->download($fullFilePath, $displayName, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $displayName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error downloading email-gated content file', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Download failed'], 500);
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

        return view('email-gated-content.access-form', [
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
        // Get video title if YouTube video is set
        $videoTitle = null;
        if ($content->youtube_video_url) {
            // Extract YouTube video ID from URL
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $content->youtube_video_url, $matches);
            $videoId = $matches[1] ?? null;
            
            if ($videoId && $tenant->ytChannel) {
                // Find the video in the yt_videos table
                $video = $tenant->ytChannel->ytVideos()->where('video_id', $videoId)->first();
                $videoTitle = $video?->title;
            }
        }

        return view('email-gated-content.content', [
            'tenant' => $tenant,
            'content' => $content,
            'channelname' => $channelname,
            'accessRecord' => $accessRecord,
            'videoTitle' => $videoTitle,
        ]);
    }

    /**
     * Check ESP connectivity to provide user feedback
     */
    private function checkESPConnectivity(Tenant $tenant): bool
    {
        try {
            $espCredentials = $tenant->esp_api_credentials ?? [];
            
            if (empty($tenant->email_service_provider) || empty($espCredentials['api_key'])) {
                return false;
            }

            $provider = $this->espManager->getProviderForTenant($tenant);
            
            if (!$provider) {
                return false;
            }
            
            // Try a simple API call to check connectivity
            $provider->getTags();
            return true;
        } catch (\Exception $e) {
            Log::warning('ESP connectivity check failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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