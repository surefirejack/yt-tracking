<?php

namespace App\Http\Controllers;

use App\Models\SubscriberUser;
use App\Models\Tenant;
use App\Services\YouTubeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;

class SubscriberAuthController extends Controller
{
    private YouTubeSubscriptionService $subscriptionService;

    public function __construct(YouTubeSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Show the login page for subscriber authentication
     */
    public function showLogin(Request $request, $channelname, $slug = null)
    {
        try {
            // Handle route model binding - channelname might be a Tenant object
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
                $channelnameStr = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelnameStr = $channelname;
                $tenant = $this->findTenantByChannelName($channelnameStr);
                
                if (!$tenant) {
                    Log::error('Tenant not found for channel name in showLogin', ['channelname' => $channelnameStr]);
                    return redirect()->route('home')->with('error', 'Channel not found.');
                }
            }

            if (!$tenant->hasSubscriberLmsEnabled()) {
                Log::error('Subscriber LMS not enabled for tenant in showLogin', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelnameStr
                ]);
                return redirect()->route('home')->with('error', 'This feature is not available.');
            }

            // Check if this is a preview request from a tenant member
            $isPreview = $request->has('preview') && $request->boolean('preview');
            if ($isPreview) {
                // Verify the user is a tenant member
                $user = auth()->user();
                if (!$user || !$tenant->users()->where('user_id', $user->id)->exists()) {
                    Log::warning('Unauthorized preview attempt', [
                        'user_id' => $user?->id,
                        'tenant_id' => $tenant->id,
                        'channelname' => $channelnameStr
                    ]);
                    return redirect()->route('home')->with('error', 'Unauthorized access.');
                }
                
                Log::info('Tenant member previewing login page', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelnameStr
                ]);
            }

            // Handle slug parameter - might be object or string
            $slugStr = null;
            $contentTitle = null;
            
            if ($slug instanceof \App\Models\SubscriberContent) {
                $slugStr = $slug->slug;
                $contentTitle = $slug->title;
            } elseif (is_string($slug)) {
                $slugStr = $slug;
                $content = $tenant->subscriberContent()->where('slug', $slug)->first();
                $contentTitle = $content?->title;
            }

            return view('subscriber.login', [
                'tenant' => $tenant,
                'channelname' => $channelnameStr,
                'slug' => $slugStr,
                'contentTitle' => $contentTitle,
                'loginText' => $tenant->member_login_text,
                'profileImage' => $tenant->member_profile_image,
                'channelBanner' => $tenant->ytChannel?->banner_image_url,
                'oauthUrl' => route('subscriber.auth.google', ['channelname' => $channelnameStr, 'slug' => $slugStr]),
                'isPreview' => $isPreview ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in showLogin', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('home')->with('error', 'An error occurred.');
        }
    }

    /**
     * Redirect to Google OAuth for subscriber authentication
     */
    public function redirectToGoogle(Request $request, $channelname, $slug = null)
    {
        try {
            // Handle route model binding - channelname might be a Tenant object
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
                $channelnameStr = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelnameStr = $channelname;
                // Find tenant by channel name
                $tenant = $this->findTenantByChannelName($channelnameStr);
                
                if (!$tenant) {
                    Log::error('Tenant not found for channel name', ['channelname' => $channelnameStr]);
                    return redirect()->route('home')->with('error', 'Channel not found.');
                }
            }

            if (!$tenant->hasSubscriberLmsEnabled()) {
                Log::error('Subscriber LMS not enabled for tenant', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelnameStr
                ]);
                return redirect()->route('home')->with('error', 'This feature is not available.');
            }

            // Handle slug parameter - might be object or string
            $slugStr = null;
            if ($slug instanceof \App\Models\SubscriberContent) {
                $slugStr = $slug->slug;
            } elseif (is_string($slug)) {
                $slugStr = $slug;
            }

            // Store tenant and intended destination in session
            Session::put('subscriber_auth.tenant_id', $tenant->id);
            Session::put('subscriber_auth.channelname', $channelnameStr);
            Session::put('subscriber_auth.intended_slug', $slugStr);
            Session::put('subscriber_auth.remember_me', $request->boolean('remember_me', false));

            Log::info('Initiating Google OAuth for subscriber', [
                'tenant_id' => $tenant->id,
                'channelname' => $channelnameStr,
                'intended_slug' => $slugStr,
                'remember_me' => $request->boolean('remember_me', false),
                'session_id' => session()->getId()
            ]);

            // Redirect to Google with YouTube readonly scope using YouTube credentials
            // Temporarily override Google OAuth config to use YouTube credentials
            config([
                'services.google.client_id' => config('services.youtube.client_id'),
                'services.google.client_secret' => config('services.youtube.client_secret'),
            ]);
            
            $driver = Socialite::driver('google');
            $redirectUrl = url('/subscriber/auth/callback');
            $driver->redirectUrl($redirectUrl);
            
            Log::info('=== OAUTH REDIRECT URL DEBUG ===', [
                'callback_url_being_sent_to_google' => $redirectUrl,
                'using_youtube_credentials' => true,
                'client_id' => config('services.youtube.client_id'),
                'session_data_stored' => [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelnameStr,
                    'slug' => $slugStr,
                    'session_id' => session()->getId()
                ]
            ]);
            
            return $driver
                ->scopes(['https://www.googleapis.com/auth/youtube.readonly'])
                ->with(['access_type' => 'offline', 'prompt' => 'consent'])
                ->redirect();

        } catch (\Exception $e) {
            Log::error('Error initiating Google OAuth for subscriber', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('home')->with('error', 'Authentication error occurred.');
        }
    }

    /**
     * Handle Google OAuth callback and verify subscription
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            Log::info('OAuth callback received', [
                'url' => $request->fullUrl(),
                'query_params' => $request->query->all(),
                'all_request_data' => $request->all(),
                'has_code' => $request->has('code'),
                'has_error' => $request->has('error'),
                'error' => $request->query('error'),
                'error_description' => $request->query('error_description'),
                'session_id' => session()->getId(),
                'all_session_data' => session()->all()
            ]);

            // Check for OAuth errors first
            if ($request->has('error')) {
                Log::error('OAuth error received', [
                    'error' => $request->query('error'),
                    'error_description' => $request->query('error_description'),
                    'error_uri' => $request->query('error_uri')
                ]);
                
                return redirect()->route('subscriber.login', ['channelname' => 'unknown'])
                    ->with('error', 'OAuth authentication failed: ' . $request->query('error_description', $request->query('error')));
            }

            // Check if we have authorization code
            if (!$request->has('code')) {
                Log::error('OAuth callback missing authorization code', [
                    'query_params' => $request->query->all(),
                    'full_url' => $request->fullUrl()
                ]);
                
                return redirect()->route('subscriber.login', ['channelname' => 'unknown'])
                    ->with('error', 'OAuth authentication failed: No authorization code received');
            }

            // Get stored session data
            $tenantId = Session::get('subscriber_auth.tenant_id');
            $channelname = Session::get('subscriber_auth.channelname');
            $intendedSlug = Session::get('subscriber_auth.intended_slug');
            $rememberMe = Session::get('subscriber_auth.remember_me', false);

            Log::info('Session data retrieval', [
                'tenant_id' => $tenantId,
                'channelname' => $channelname,
                'intended_slug' => $intendedSlug,
                'remember_me' => $rememberMe,
                'session_keys' => array_keys(session()->all())
            ]);

            if (!$tenantId || !$channelname) {
                Log::error('Missing session data in OAuth callback', [
                    'tenant_id' => $tenantId,
                    'channelname' => $channelname,
                    'session_id' => session()->getId(),
                    'all_session_keys' => array_keys(session()->all())
                ]);
                return redirect()->route('home')->with('error', 'Authentication session expired.');
            }

            // Find tenant
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                Log::error('Tenant not found in OAuth callback', ['tenant_id' => $tenantId]);
                return redirect()->route('home')->with('error', 'Invalid session.');
            }

            // Get user from Google using YouTube credentials
            // Temporarily override Google OAuth config to use YouTube credentials
            config([
                'services.google.client_id' => config('services.youtube.client_id'),
                'services.google.client_secret' => config('services.youtube.client_secret'),
            ]);
            
            Log::info('Attempting to get user from Google OAuth', [
                'client_id' => config('services.youtube.client_id'),
                'callback_url' => url('/subscriber/auth/callback'),
                'request_code' => $request->query('code') ? substr($request->query('code'), 0, 20) . '...' : 'none',
                'config_after_override' => [
                    'google_client_id' => config('services.google.client_id'),
                    'google_client_secret_first_10' => substr(config('services.google.client_secret'), 0, 10) . '...',
                ]
            ]);
            
            // Clear any cached instances and create a fresh Socialite driver instance
            app()->forgetInstance(\Laravel\Socialite\Contracts\Factory::class);
            $socialiteManager = app(\Laravel\Socialite\Contracts\Factory::class);
            $driver = $socialiteManager->driver('google');
            $driver->redirectUrl(url('/subscriber/auth/callback'));
            
            try {
                $googleUser = $driver->user();
                
                Log::info('Successfully received Google user data', [
                    'google_id' => $googleUser->id ?? 'unknown',
                    'email' => $googleUser->email ?? 'unknown'
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get Google user data', [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'previous_error' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
                    'config_check' => [
                        'google_client_id' => config('services.google.client_id'),
                        'google_client_secret_first_10' => substr(config('services.google.client_secret'), 0, 10) . '...',
                        'youtube_client_id' => config('services.youtube.client_id'),
                        'youtube_client_secret_first_10' => substr(config('services.youtube.client_secret'), 0, 10) . '...',
                    ],
                    'request_data' => [
                        'code_length' => strlen($request->query('code')),
                        'has_state' => $request->has('state'),
                        'scope' => $request->query('scope'),
                    ]
                ]);
                
                // If it's a client credentials error, let's try to see if it's using the right config
                if (strpos($e->getMessage(), 'client') !== false || strpos($e->getMessage(), 'invalid') !== false) {
                    Log::error('Possible client configuration issue detected', [
                        'current_google_config' => [
                            'client_id' => config('services.google.client_id'),
                            'client_secret' => substr(config('services.google.client_secret'), 0, 10) . '...',
                        ],
                        'youtube_config' => [
                            'client_id' => config('services.youtube.client_id'),
                            'client_secret' => substr(config('services.youtube.client_secret'), 0, 10) . '...',
                        ]
                    ]);
                }
                
                throw $e; // Re-throw the original exception
            }

            // Find or create subscriber user
            $subscriberUser = SubscriberUser::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'google_id' => $googleUser->id,
                ],
                [
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'profile_picture' => $googleUser->avatar,
                ]
            );

            // Update user data if changed
            $subscriberUser->update([
                'email' => $googleUser->email,
                'name' => $googleUser->name,
                'profile_picture' => $googleUser->avatar,
            ]);

            // Create temporary user for subscription verification
            $tempUser = new \App\Models\User();
            $tempUser->id = 'temp_' . $subscriberUser->id;
            $tempUser->email = $subscriberUser->email;
            $tempUser->name = $subscriberUser->name;

            // Set OAuth tokens for verification
            $this->subscriptionService->setTemporaryTokens($tempUser, [
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken,
                'expires_in' => $googleUser->expiresIn ?? 3600
            ]);

            // Verify subscription
            $isSubscribed = $this->subscriptionService->verifySubscription($subscriberUser, $tenant);

            if (!$isSubscribed) {
                Log::info('User is not subscribed, showing access denied', [
                    'subscriber_user_id' => $subscriberUser->id,
                    'tenant_id' => $tenant->id
                ]);

                return $this->showAccessDenied($tenant, $channelname, $intendedSlug);
            }

            // Create subscriber session
            $this->createSubscriberSession($subscriberUser, $tenant, $rememberMe);

            // Clean up OAuth session data
            Session::forget(['subscriber_auth.tenant_id', 'subscriber_auth.channelname', 
                           'subscriber_auth.intended_slug', 'subscriber_auth.remember_me']);

            Log::info('Subscriber authentication successful', [
                'subscriber_user_id' => $subscriberUser->id,
                'tenant_id' => $tenant->id,
                'intended_slug' => $intendedSlug
            ]);

            // Redirect to intended destination
            if ($intendedSlug) {
                return redirect()->route('subscriber.content', [
                    'channelname' => $channelname,
                    'slug' => $intendedSlug
                ]);
            } else {
                return redirect()->route('subscriber.dashboard', ['channelname' => $channelname]);
            }

        } catch (\Exception $e) {
            Log::error('Error in Google OAuth callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up session
            Session::forget(['subscriber_auth.tenant_id', 'subscriber_auth.channelname', 
                           'subscriber_auth.intended_slug', 'subscriber_auth.remember_me']);

            return redirect()->route('home')->with('error', 'Authentication failed.');
        }
    }

    /**
     * Logout subscriber and redirect
     */
    public function logout(Request $request, string $channelname)
    {
        try {
            $tenant = $this->findTenantByChannelName($channelname);
            
            if ($tenant) {
                Log::info('Subscriber logout', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelname,
                    'subscriber_user_id' => Session::get('subscriber_user_id')
                ]);

                // Clear subscriber session
                Session::forget(['subscriber_user_id', 'subscriber_tenant_id', 'subscriber_expires_at']);

                // Redirect to custom logout URL or default
                $redirectUrl = $tenant->logout_redirect_url ?: route('home');
                
                return redirect($redirectUrl)->with('success', 'You have been logged out.');
            }

            // Fallback if tenant not found
            Session::forget(['subscriber_user_id', 'subscriber_tenant_id', 'subscriber_expires_at']);
            return redirect()->route('home');

        } catch (\Exception $e) {
            Log::error('Error during subscriber logout', [
                'channelname' => $channelname,
                'error' => $e->getMessage()
            ]);

            // Clean up session anyway
            Session::forget(['subscriber_user_id', 'subscriber_tenant_id', 'subscriber_expires_at']);
            return redirect()->route('home');
        }
    }

    /**
     * Show access denied page
     */
    private function showAccessDenied(Tenant $tenant, string $channelname, ?string $intendedSlug = null): \Illuminate\View\View
    {
        return view('subscriber.access-denied', [
            'tenant' => $tenant,
            'channelname' => $channelname,
            'slug' => $intendedSlug,
            'youtubeChannelUrl' => $tenant->ytChannel?->channel_url,
            'tryAgainUrl' => route('subscriber.try-again', [
                'channelname' => $channelname,
                'slug' => $intendedSlug
            ])
        ]);
    }

    /**
     * Create subscriber session with "remember me" functionality
     */
    private function createSubscriberSession(SubscriberUser $subscriberUser, Tenant $tenant, bool $rememberMe = false): void
    {
        $expiresAt = $rememberMe 
            ? Carbon::now()->addDays(30) // 30 days for "remember me"
            : Carbon::now()->addHours(24); // 24 hours for regular session

        Session::put('subscriber_user_id', $subscriberUser->id);
        Session::put('subscriber_tenant_id', $tenant->id);
        Session::put('subscriber_expires_at', $expiresAt->toDateTimeString());

        // Set session lifetime if remember me is enabled
        if ($rememberMe) {
            config(['session.lifetime' => 43200]); // 30 days in minutes
        }

        Log::info('Subscriber session created', [
            'subscriber_user_id' => $subscriberUser->id,
            'tenant_id' => $tenant->id,
            'expires_at' => $expiresAt->toDateTimeString(),
            'remember_me' => $rememberMe
        ]);
    }

    /**
     * Find tenant by channel name (case-insensitive)
     */
    private function findTenantByChannelName(string $channelname): ?Tenant
    {
        return Tenant::whereHas('ytChannel', function ($query) use ($channelname) {
            $query->whereRaw('LOWER(REPLACE(handle, "@", "")) = ?', [strtolower($channelname)]);
        })->first();
    }

    /**
     * Get the currently authenticated subscriber user
     */
    public function getAuthenticatedSubscriber(): ?SubscriberUser
    {
        $subscriberUserId = Session::get('subscriber_user_id');
        $tenantId = Session::get('subscriber_tenant_id');
        $expiresAt = Session::get('subscriber_expires_at');

        if (!$subscriberUserId || !$tenantId || !$expiresAt) {
            return null;
        }

        // Check if session has expired
        if (Carbon::parse($expiresAt)->isPast()) {
            Log::info('Subscriber session expired', [
                'subscriber_user_id' => $subscriberUserId,
                'expired_at' => $expiresAt
            ]);
            
            // Clear expired session
            Session::forget(['subscriber_user_id', 'subscriber_tenant_id', 'subscriber_expires_at']);
            return null;
        }

        return SubscriberUser::find($subscriberUserId);
    }

    /**
     * Check if current subscriber is authenticated for specific tenant
     */
    public function isAuthenticatedForTenant(Tenant $tenant): bool
    {
        $subscriber = $this->getAuthenticatedSubscriber();
        $sessionTenantId = Session::get('subscriber_tenant_id');

        return $subscriber && $sessionTenantId == $tenant->id;
    }

    /**
     * Force re-verify subscription (for "try again" functionality)
     */
    public function tryAgain(Request $request, $channelname, $slug = null)
    {
        try {
            // Handle route model binding - channelname might be a Tenant object
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
                $channelnameStr = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelnameStr = $channelname;
                // Find tenant by channel name
                $tenant = $this->findTenantByChannelName($channelnameStr);
                
                if (!$tenant) {
                    Log::error('Tenant not found for channel name in tryAgain', ['channelname' => $channelnameStr]);
                    return redirect()->route('home')->with('error', 'Channel not found.');
                }
            }

            if (!$tenant->hasSubscriberLmsEnabled()) {
                Log::error('Subscriber LMS not enabled for tenant in tryAgain', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelnameStr
                ]);
                return redirect()->route('home')->with('error', 'This feature is not available.');
            }

            // Handle slug parameter - might be object or string
            $slugStr = null;
            if ($slug instanceof \App\Models\SubscriberContent) {
                $slugStr = $slug->slug;
            } elseif (is_string($slug)) {
                $slugStr = $slug;
            }

            Log::info('Try again attempt', [
                'tenant_id' => $tenant->id,
                'channelname' => $channelnameStr,
                'slug' => $slugStr
            ]);

            // Redirect to login with slug
            return redirect()->route('subscriber.auth.google', [
                'channelname' => $channelnameStr,
                'slug' => $slugStr
            ])->with(['try_again' => true]);

        } catch (\Exception $e) {
            Log::error('Error in try again', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'slug' => is_object($slug) ? get_class($slug) : $slug,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('home')->with('error', 'An error occurred.');
        }
    }
}
