<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\EmailSubscriberContent;
use App\Models\EmailVerificationRequest;
use App\Models\SubscriberAccessRecord;
use App\Services\EmailServiceProvider\EmailServiceProviderManager;
use App\Jobs\ProcessEmailVerification;
use App\Jobs\CheckUserAccessJob;
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
                $tenant = $this->findTenantByChannelName($channelnameStr);

                if (!$tenant) {
                    Log::warning('Tenant not found for channel name', [
                        'channelname' => $channelnameStr,
                        'url' => $request->url()
                    ]);
                    return redirect()->route('home')->with('error', 'Channel not found.');
                }
            }

            Log::info('Tenant found for email-gated content', [
                'tenant_id' => $tenant->id,
                'channelname' => $channelnameStr,
                'has_ytchannel' => $tenant->ytChannel ? 'yes' : 'no',
                'channel_title' => $tenant->ytChannel->title ?? 'none',
                'channel_handle' => $tenant->ytChannel->handle ?? 'none'
            ]);

            if ($slug instanceof EmailSubscriberContent) {
                $content = $slug;
            } else {
                $content = EmailSubscriberContent::where('tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->first();

                if (!$content) {
                    Log::warning('Content not found', [
                        'tenant_id' => $tenant->id,
                        'slug' => $slug
                    ]);
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
                    'access_record_id' => $accessRecord->id,
                    'access_check_status' => $accessRecord->access_check_status ?? 'none'
                ]);

                return $this->showContent($tenant, $content, $channelnameStr, $accessRecord);
            }

            // Show the email access form
            return $this->showAccessForm($request, $tenant, $content, $channelnameStr);

        } catch (\Exception $e) {
            Log::error('Error loading email-gated content', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            Log::info('Email verification request created (not yet verified)', [
                'tenant_id' => $tenant->id,
                'content_id' => $content->id,
                'verification_request_id' => $verificationRequest->id,
                'email' => $email,
                'verified_at' => $verificationRequest->verified_at, // Should be null
                'esp_connected' => $this->checkESPConnectivity($tenant),
                'verification_token' => substr($verificationRequest->verification_token, 0, 8) . '...' // First 8 chars for debugging
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
    public function verifyEmail(Request $request, int $tenantId, string $token)
    {
        try {
            // Detect if this is a bot request
            $userAgent = $request->header('User-Agent', '');
            $isBot = $this->detectBot($userAgent);
            
            if ($isBot) {
                Log::info('Bot detected accessing verification link', [
                    'user_agent' => $userAgent,
                    'ip_address' => $request->ip(),
                    'token' => substr($token, 0, 8) . '...',
                    'tenant_id' => $tenantId
                ]);
                
                // Return a simple page for bots without verifying
                return response('Email verification link detected. This link is for human verification only.', 200)
                    ->header('Content-Type', 'text/plain');
            }

            // Get tenant first using the tenant ID from URL
            $tenant = Tenant::with('ytChannel')->find($tenantId);
            
            if (!$tenant) {
                Log::warning('Invalid tenant ID in verification URL', ['tenantId' => $tenantId, 'token' => $token]);
                
                return view('email-verification.expired', [
                    'message' => 'This verification link is invalid.',
                    'tenant' => null,
                    'channelname' => 'channel'
                ]);
            }
            
            $channelname = $tenant->getChannelName() ?? 'channel';
            
            // Find verification request (regardless of expiration to check if already verified)
            $verificationRequest = EmailVerificationRequest::where('verification_token', $token)
                ->where('tenant_id', $tenantId)
                ->with(['content'])
                ->first();

            if (!$verificationRequest) {
                Log::warning('Invalid verification token', ['tenantId' => $tenantId, 'token' => $token]);
                
                return view('email-verification.expired', [
                    'message' => 'This verification link is invalid.',
                    'tenant' => $tenant,
                    'channelname' => $channelname
                ]);
            }

            // Check if token has expired (but only if not already verified)
            if (!$verificationRequest->verified_at && $verificationRequest->expires_at <= now()) {
                Log::warning('Expired verification token', ['tenantId' => $tenantId, 'token' => $token]);
                
                return view('email-verification.expired', [
                    'message' => 'This verification link has expired.',
                    'tenant' => $tenant,
                    'channelname' => $channelname
                ]);
            }

            $content = $verificationRequest->content;

            // Check if we already have an access record for this user
            $accessRecord = SubscriberAccessRecord::where('email', $verificationRequest->email)
                ->where('tenant_id', $verificationRequest->tenant_id)
                ->first();

            // If already verified and access record exists, grant access
            if ($verificationRequest->verified_at && $accessRecord) {
                Log::info('Verification token already used, granting access via existing record', [
                    'token' => $token,
                    'verified_at' => $verificationRequest->verified_at,
                    'access_record_id' => $accessRecord->id
                ]);

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

            // Process verification (either first time or if access record doesn't exist)
            Log::info('Processing email verification', [
                'token' => $token,
                'email' => $verificationRequest->email,
                'is_first_time' => !$verificationRequest->verified_at,
                'has_existing_access_record' => $accessRecord ? 'yes' : 'no'
            ]);

            // Mark as verified if not already
            if (!$verificationRequest->verified_at) {
                $verificationRequest->update(['verified_at' => now()]);
                
                Log::info('Verification request marked as verified', [
                    'verification_request_id' => $verificationRequest->id,
                    'email' => $verificationRequest->email,
                    'token' => substr($verificationRequest->verification_token, 0, 8) . '...',
                    'verified_at' => $verificationRequest->fresh()->verified_at,
                    'user_agent' => $request->header('User-Agent'),
                    'ip_address' => $request->ip()
                ]);
            }

            // Create access record immediately if it doesn't exist (for cookie purposes)
            if (!$accessRecord) {
                $accessRecord = SubscriberAccessRecord::create([
                    'email' => $verificationRequest->email,
                    'tenant_id' => $tenant->id,
                    'tags_json' => [$content->required_tag_id], // Include required tag immediately
                    'access_check_status' => 'pending',
                    'access_check_started_at' => now(),
                    'last_verified_at' => now(),
                ]);

                Log::info('Created access record for verified email', [
                    'email' => $verificationRequest->email,
                    'access_record_id' => $accessRecord->id,
                    'verification_request_id' => $verificationRequest->id,
                    'tags_included' => [$content->required_tag_id]
                ]);
                
                // Dispatch async job to handle Kit API integration
                ProcessEmailVerification::dispatch($verificationRequest, null, true, $accessRecord->id);
                
                Log::info('Dispatched async Kit API job', [
                    'email' => $verificationRequest->email,
                    'verification_request_id' => $verificationRequest->id,
                    'access_record_id' => $accessRecord->id
                ]);
            }

            if ($accessRecord) {
                // Update existing record with verification time
                $accessRecord->update([
                    'last_verified_at' => now(),
                ]);

                Log::info('Updated existing access record for verified email', [
                    'email' => $verificationRequest->email,
                    'access_record_id' => $accessRecord->id,
                    'verification_request_id' => $verificationRequest->id
                ]);
            }

            // NOTE: Access record creation is handled by the ProcessEmailVerification job
            // dispatched from submitEmail(). If job hasn't finished, user gets temporary access.

            Log::info('Email verification completed', [
                'tenant_id' => $tenant->id,
                'content_id' => $content->id,
                'verification_request_id' => $verificationRequest->id,
                'access_record_id' => $accessRecord ? $accessRecord->id : 'none'
            ]);

            // Set access cookie and redirect to content
            $contentUrl = route('email-gated-content.show', [
                'channelname' => $channelname,
                'slug' => $content->slug
            ]);

            $view = view('email-verification.success', [
                'tenant' => $tenant,
                'content' => $content,
                'channelname' => $channelname,
                'contentUrl' => $contentUrl,
                'message' => 'Email verified successfully! You now have access to the content.'
            ]);

            // Set cookie pointing to the access record and return
            return $this->setAccessCookie(response($view), $accessRecord);

        } catch (\Exception $e) {
            Log::error('Error verifying email token', [
                'tenantId' => $tenantId,
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            // Try to get tenant from tenant ID if available
            $tenant = null;
            $channelname = 'channel';
            try {
                $tenant = Tenant::with('ytChannel')->find($tenantId);
                $channelname = $tenant?->getChannelName() ?? 'channel';
            } catch (\Exception $tenantException) {
                // Ignore tenant lookup errors
            }

            return view('email-verification.expired', [
                'message' => 'An error occurred during verification. Please try again.',
                'tenant' => $tenant,
                'channelname' => $channelname
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
        // Check for special query parameters
        $accessDenied = $request->query('access_denied');
        $timeout = $request->query('timeout');
        $error = $request->query('error');

        // Get video title and thumbnail if utm_content is provided
        $videoTitle = null;
        $videoThumbnail = null;
        $utmContent = $request->query('utm_content');
        if ($utmContent && $tenant->ytChannel) {
            $video = $tenant->ytChannel->ytVideos()->where('video_id', $utmContent)->first();
            if ($video) {
                $videoTitle = $video->title;
                $videoThumbnail = "https://img.youtube.com/vi/{$utmContent}/hqdefault.jpg";
            }
        }

        // Get tag name for display
        $tagName = null;
        if ($content->required_tag_id) {
            try {
                $provider = $this->espManager->getProviderForTenant($tenant);
                if ($provider) {
                    $tags = $provider->getTags();
                    $tag = collect($tags)->firstWhere('id', $content->required_tag_id);
                    $tagName = $tag['name'] ?? null;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get tag name for display', [
                    'tenant_id' => $tenant->id,
                    'tag_id' => $content->required_tag_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('email-gated-content.access-form', [
            'tenant' => $tenant,
            'content' => $content,
            'channelname' => $channelname,
            'videoTitle' => $videoTitle,
            'videoThumbnail' => $videoThumbnail,
            'utmContent' => $utmContent,
            'tagName' => $tagName,
            'accessDenied' => $accessDenied,
            'timeout' => $timeout,
            'error' => $error,
        ]);
    }

    /**
     * Show the content after verification
     */
    private function showContent(Tenant $tenant, EmailSubscriberContent $content, string $channelname, SubscriberAccessRecord $accessRecord): View
    {
        // If access check is in progress, show loading page
        if ($accessRecord->isCheckInProgress()) {
            return view('email-gated-content.checking-access', [
                'tenant' => $tenant,
                'content' => $content,
                'channelname' => $channelname,
                'accessRecord' => $accessRecord,
            ]);
        }

        // Check if user has required tag - if not, this shouldn't have gotten to showContent
        // This is a safety check that should not normally trigger
        if (!$accessRecord->hasTag($content->required_tag_id)) {
            Log::warning('showContent called for user without required tag', [
                'access_record_id' => $accessRecord->id,
                'required_tag_id' => $content->required_tag_id,
                'user_tags' => $accessRecord->tags_json,
                'access_check_status' => $accessRecord->access_check_status
            ]);
            
            // Return a view showing access denied instead of redirect
            return view('email-gated-content.access-denied', [
                'tenant' => $tenant,
                'content' => $content,
                'channelname' => $channelname,
                'message' => 'You do not have access to this content. Please verify your subscription.'
            ]);
        }

        // Show content (existing logic)
        $videoTitle = null;
        if ($content->youtube_video_url) {
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $content->youtube_video_url, $matches);
            $videoId = $matches[1] ?? null;
            
            if ($videoId && $tenant->ytChannel) {
                $video = $tenant->ytChannel->ytVideos()->where('video_id', $videoId)->first();
                if ($video) {
                    $videoTitle = $video->title;
                }
            }
        }

        // Get CTA video data if CTA video is set
        $ctaVideo = null;
        if ($content->cta_youtube_video_url) {
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $content->cta_youtube_video_url, $matches);
            $ctaVideoId = $matches[1] ?? null;
            
            if ($ctaVideoId && $tenant->ytChannel) {
                $video = $tenant->ytChannel->ytVideos()->where('video_id', $ctaVideoId)->first();
                if ($video) {
                    $ctaVideo = [
                        'id' => $ctaVideoId,
                        'title' => $video->title,
                        'url' => $content->cta_youtube_video_url,
                        'thumbnail_url' => "https://img.youtube.com/vi/{$ctaVideoId}/hqdefault.jpg"
                    ];
                }
            }
        }

        return view('email-gated-content.content', [
            'tenant' => $tenant,
            'content' => $content,
            'channelname' => $channelname,
            'accessRecord' => $accessRecord,
            'videoTitle' => $videoTitle,
            'ctaVideo' => $ctaVideo,
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
        
        Log::info('Checking existing access', [
            'tenant_id' => $tenant->id,
            'content_slug' => $content->slug,
            'required_tag_id' => $content->required_tag_id,
            'cookie_token_present' => $cookieToken ? 'yes' : 'no',
            'cookie_token_length' => $cookieToken ? strlen($cookieToken) : 0
        ]);
        
        if (!$cookieToken) {
            return null;
        }

        $accessRecord = SubscriberAccessRecord::where('cookie_token', $cookieToken)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$accessRecord) {
            Log::warning('Access record not found for cookie token', [
                'tenant_id' => $tenant->id,
                'cookie_token_length' => strlen($cookieToken)
            ]);
            return null;
        }

        Log::info('Found access record via cookie', [
            'access_record_id' => $accessRecord->id,
            'tenant_id' => $tenant->id,
            'user_tags' => $accessRecord->tags_json,
            'required_tag_id' => $content->required_tag_id,
            'last_verified_at' => $accessRecord->last_verified_at,
            'access_check_status' => $accessRecord->access_check_status ?? 'none'
        ]);

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
        $hasRequiredTag = in_array($content->required_tag_id, $userTags);
        
        Log::info('Tag check result', [
            'access_record_id' => $accessRecord->id,
            'required_tag_id' => $content->required_tag_id,
            'user_tags' => $userTags,
            'has_required_tag' => $hasRequiredTag ? 'yes' : 'no',
            'access_check_status' => $accessRecord->access_check_status
        ]);
        
        if (!$hasRequiredTag) {
            // If Kit API sync is still pending, check for timeout
            if ($accessRecord->access_check_status === 'pending') {
                // Check if the pending state has been going on too long (5 minutes)
                $pendingTimeout = 5; // minutes
                $pendingSince = $accessRecord->access_check_started_at ?? $accessRecord->last_verified_at;
                
                if ($pendingSince && $pendingSince->addMinutes($pendingTimeout)->isPast()) {
                    Log::warning('ESP job has been pending too long, clearing cookie and showing opt-in form', [
                        'access_record_id' => $accessRecord->id,
                        'pending_since' => $pendingSince,
                        'timeout_minutes' => $pendingTimeout,
                        'required_tag_id' => $content->required_tag_id
                    ]);
                    
                    // Clear the access record status to allow retry
                    $accessRecord->update([
                        'access_check_status' => 'timeout',
                        'access_check_error' => 'ESP sync timeout after ' . $pendingTimeout . ' minutes'
                    ]);
                    
                    return null; // This will show the opt-in form
                }
                
                Log::info('Kit API sync still pending, showing loading page', [
                    'access_record_id' => $accessRecord->id,
                    'required_tag_id' => $content->required_tag_id
                ]);
                return $accessRecord; // Will show loading page
            }
            
            // User doesn't have required tag and sync is complete - deny access
            Log::info('User does not have required tag, denying access', [
                'access_record_id' => $accessRecord->id,
                'required_tag_id' => $content->required_tag_id,
                'user_tags' => $userTags,
                'access_check_status' => $accessRecord->access_check_status
            ]);
            return null;
        }

        Log::info('Granting access - user has required tag', [
            'access_record_id' => $accessRecord->id,
            'required_tag_id' => $content->required_tag_id,
            'user_tags' => $userTags
        ]);

        return $accessRecord;
    }

    /**
     * Handle async tag checking
     */
    private function handleAsyncTagCheck(SubscriberAccessRecord $accessRecord, Tenant $tenant, EmailSubscriberContent $content): ?SubscriberAccessRecord
    {
        // Check if there's already an access check in progress for this content
        if ($accessRecord->required_tag_id === $content->required_tag_id && $accessRecord->isCheckInProgress()) {
            Log::info('Access check already in progress', [
                'access_record_id' => $accessRecord->id,
                'required_tag_id' => $content->required_tag_id,
                'status' => $accessRecord->access_check_status
            ]);
            return $accessRecord; // Return record to show loading page
        }

        // Check if we have a recent completed check for this content
        if ($accessRecord->required_tag_id === $content->required_tag_id && 
            $accessRecord->isCheckCompleted() && 
            $accessRecord->access_check_completed_at->isAfter(now()->subMinutes(5))) {
            
            // ALWAYS verify current tags match required tag - don't rely on cached has_required_access
            // because tags might have changed in the database without our knowledge
            $currentUserTags = $accessRecord->tags_json ?? [];
            $currentlyHasTag = in_array($content->required_tag_id, $currentUserTags);
            
            if ($currentlyHasTag) {
                Log::info('Using cached access check result - access granted (verified current tags)', [
                    'access_record_id' => $accessRecord->id,
                    'required_tag_id' => $content->required_tag_id,
                    'current_tags' => $currentUserTags
                ]);
                return $accessRecord;
            } else {
                Log::info('Using cached access check result - access denied (current tags do not match)', [
                    'access_record_id' => $accessRecord->id,
                    'required_tag_id' => $content->required_tag_id,
                    'current_tags' => $currentUserTags,
                    'has_required_tag' => false
                ]);
                return null;
            }
        }

        // Start new async check
        Log::info('Starting async access check', [
            'access_record_id' => $accessRecord->id,
            'required_tag_id' => $content->required_tag_id
        ]);

        // Reset status for new check
        $accessRecord->update([
            'access_check_status' => 'pending',
            'access_check_started_at' => null,
            'access_check_completed_at' => null,
            'access_check_error' => null
        ]);

        // Dispatch background job
        CheckUserAccessJob::dispatch($accessRecord, $content, $tenant);

        return $accessRecord; // Return record to show loading page
    }

    /**
     * Check if email has access via existing verification (async version)
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
            // Use async approach for tag checking
            return $this->handleAsyncTagCheck($accessRecord, $tenant, $content);
        }

        return $accessRecord;
    }

    /**
     * API endpoint to check access status
     */
    public function checkAccessStatus(Request $request, int $accessRecordId)
    {
        try {
            $accessRecord = SubscriberAccessRecord::find($accessRecordId);
            
            if (!$accessRecord) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access record not found'
                ], 404);
            }

            // For the checkAccessStatus endpoint, we need the content ID to be passed
            // or we need to find it another way. For now, let's get the first content
            // that the user is trying to access (this is a limitation of the current design)
            $contentId = $request->input('content_id');
            if ($contentId) {
                $content = EmailSubscriberContent::where('id', $contentId)
                    ->where('tenant_id', $accessRecord->tenant_id)
                    ->first();
            } else {
                // Fallback: get any content from this tenant (not ideal but works for single content scenarios)
                $content = EmailSubscriberContent::where('tenant_id', $accessRecord->tenant_id)->first();
            }

            if (!$content) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Content not found'
                ], 404);
            }

            $tenant = $accessRecord->tenant;
            $channelname = strtolower(str_replace('@', '', $tenant->ytChannel->handle ?? 'channel'));

            $response = [
                'status' => $accessRecord->access_check_status,
                'accessRecordId' => $accessRecord->id,
                'contentUrl' => route('email-gated-content.show', [$channelname, $content->slug]),
                'accessFormUrl' => route('email-gated-content.show', [$channelname, $content->slug]) . '?access_denied=1',
            ];

            if ($accessRecord->isCheckCompleted()) {
                // Check if user has required tag for this content
                $hasRequiredTag = $accessRecord->hasTag($content->required_tag_id);
                $response['hasAccess'] = $hasRequiredTag;
                $response['processingTime'] = $accessRecord->getProcessingTime();
                
                if ($accessRecord->access_check_error) {
                    $response['error'] = $accessRecord->access_check_error;
                }
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error checking access status', [
                'access_record_id' => $accessRecordId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while checking access'
            ], 500);
        }
    }

    /**
     * Set access cookie on response
     */
    private function setAccessCookie($response, SubscriberAccessRecord $accessRecord)
    {
        // Don't set cookie for temporary session access
        if ($accessRecord->cookie_token === 'temp_session' || $accessRecord->id === 'temp') {
            Log::info('Skipping cookie for temporary access record', [
                'access_record_id' => $accessRecord->id,
                'cookie_token' => $accessRecord->cookie_token
            ]);
            return $response;
        }

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
     * Find tenant by channel name
     */
    private function findTenantByChannelName(string $channelname): ?Tenant
    {
        Log::info('Looking for tenant by channel name', [
            'channelname' => $channelname,
            'normalized' => strtolower($channelname)
        ]);

        $tenant = Tenant::with('ytChannel')
            ->whereHas('ytChannel', function ($query) use ($channelname) {
                $query->where(\DB::raw('LOWER(REPLACE(handle, "@", ""))'), '=', strtolower($channelname));
            })
            ->first();

        if ($tenant) {
            Log::info('Tenant found by channel name', [
                'tenant_id' => $tenant->id,
                'channel_handle' => $tenant->ytChannel->handle ?? 'none',
                'channel_title' => $tenant->ytChannel->title ?? 'none'
            ]);
        } else {
            Log::warning('No tenant found for channel name', [
                'channelname' => $channelname,
                'available_channels' => Tenant::with('ytChannel')->get()->map(function($t) {
                    return [
                        'id' => $t->id,
                        'handle' => $t->ytChannel->handle ?? 'none',
                        'normalized' => strtolower(str_replace('@', '', $t->ytChannel->handle ?? ''))
                    ];
                })->toArray()
            ]);
        }

        return $tenant;
    }

    /**
     * Detect if the request is from a bot or automated crawler
     */
    private function detectBot(string $userAgent): bool
    {
        $userAgent = strtolower($userAgent);
        
        // Common bot patterns - focused on the most relevant ones
        $botPatterns = [
            'broken-link-checker',
            'linkchecker',
            'link-checker',
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
            'python',
            'node.js',
            'nodejs',
            'axios',
            'scanner',
            'validator',
            'monitor',
            'check',
            'test',
            'probe',
            'security',
            'mimecast',
            'proofpoint',
            'barracuda',
            'forcepoint',
            'symantec',
            'mcafee',
            'kaspersky',
            'microsoft defender',
            'windows defender',
            'google safe browsing',
            'phishtank',
            'urlhaus',
            'spamhaus',
            'virustotal',
            'urlvoid',
            'hybrid-analysis',
            'joesandbox',
            'cuckoo',
            'falcon',
            'crowdstrike',
            'carbonblack',
            'cylance',
            'sentinelone',
            'fireeye',
            'mandiant',
            'paloalto',
            'checkpoint',
            'fortinet',
            'cisco',
            'bluecoat',
            'websense',
            'sonicwall',
            'watchguard',
            'clearswift',
            'zscaler',
            'cloudflare',
            'akamai',
            'sucuri',
            'qualys',
            'nessus',
            'openvas',
            'burp',
            'owasp',
            'nikto',
            'nmap',
            'shodan',
            'censys',
            'binaryedge',
            'rapid7',
            'securitytrails',
        ];
        
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        
        // Additional checks for empty or suspicious user agents
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return true;
        }
        
        return false;
    }
} 