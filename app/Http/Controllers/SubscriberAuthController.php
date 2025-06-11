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
     * Redirect to Google OAuth for YouTube authentication
     */
    public function redirectToGoogle(Request $request, string $channelname, ?string $slug = null)
    {
        try {
            // Find tenant by channel name
            $tenant = $this->findTenantByChannelName($channelname);
            
            if (!$tenant) {
                Log::error('Tenant not found for channel name', ['channelname' => $channelname]);
                return redirect()->route('home')->with('error', 'Channel not found.');
            }

            if (!$tenant->hasSubscriberLmsEnabled()) {
                Log::error('Subscriber LMS not enabled for tenant', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelname
                ]);
                return redirect()->route('home')->with('error', 'This feature is not available.');
            }

            // Store tenant and intended destination in session
            Session::put('subscriber_auth.tenant_id', $tenant->id);
            Session::put('subscriber_auth.channelname', $channelname);
            Session::put('subscriber_auth.intended_slug', $slug);
            Session::put('subscriber_auth.remember_me', $request->boolean('remember_me', false));

            Log::info('Initiating Google OAuth for subscriber', [
                'tenant_id' => $tenant->id,
                'channelname' => $channelname,
                'intended_slug' => $slug,
                'remember_me' => $request->boolean('remember_me', false)
            ]);

            // Redirect to Google with YouTube readonly scope
            return Socialite::driver('google')
                ->scopes(['https://www.googleapis.com/auth/youtube.readonly'])
                ->with(['access_type' => 'offline', 'prompt' => 'consent'])
                ->redirect();

        } catch (\Exception $e) {
            Log::error('Error initiating Google OAuth for subscriber', [
                'channelname' => $channelname,
                'slug' => $slug,
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
            // Get stored session data
            $tenantId = Session::get('subscriber_auth.tenant_id');
            $channelname = Session::get('subscriber_auth.channelname');
            $intendedSlug = Session::get('subscriber_auth.intended_slug');
            $rememberMe = Session::get('subscriber_auth.remember_me', false);

            if (!$tenantId || !$channelname) {
                Log::error('Missing session data in OAuth callback');
                return redirect()->route('home')->with('error', 'Authentication session expired.');
            }

            // Find tenant
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                Log::error('Tenant not found in OAuth callback', ['tenant_id' => $tenantId]);
                return redirect()->route('home')->with('error', 'Invalid session.');
            }

            // Get user from Google
            $googleUser = Socialite::driver('google')->user();
            
            Log::info('Google OAuth callback received', [
                'tenant_id' => $tenant->id,
                'google_id' => $googleUser->id,
                'email' => $googleUser->email,
                'name' => $googleUser->name
            ]);

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
            'intendedSlug' => $intendedSlug,
            'youtubeChannelUrl' => $tenant->ytChannel?->channel_url
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
            $query->whereRaw('LOWER(custom_url) = ?', [strtolower($channelname)]);
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
    public function tryAgain(Request $request, string $channelname, ?string $slug = null)
    {
        try {
            $tenant = $this->findTenantByChannelName($channelname);
            $subscriber = $this->getAuthenticatedSubscriber();

            if (!$tenant || !$subscriber) {
                return redirect()->route('subscriber.login', [
                    'channelname' => $channelname,
                    'slug' => $slug
                ]);
            }

            Log::info('Subscriber trying again to verify subscription', [
                'subscriber_user_id' => $subscriber->id,
                'tenant_id' => $tenant->id
            ]);

            // Force re-verify subscription
            $isSubscribed = $this->subscriptionService->forceVerifySubscription($subscriber, $tenant);

            if ($isSubscribed) {
                // Redirect to intended destination
                if ($slug) {
                    return redirect()->route('subscriber.content', [
                        'channelname' => $channelname,
                        'slug' => $slug
                    ]);
                } else {
                    return redirect()->route('subscriber.dashboard', ['channelname' => $channelname]);
                }
            } else {
                return $this->showAccessDenied($tenant, $channelname, $slug);
            }

        } catch (\Exception $e) {
            Log::error('Error in try again verification', [
                'channelname' => $channelname,
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('home')->with('error', 'Verification failed.');
        }
    }
}
