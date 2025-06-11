<?php

namespace App\Http\Middleware;

use App\Http\Controllers\SubscriberAuthController;
use App\Models\Tenant;
use App\Services\YouTubeSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifySubscription
{
    private SubscriberAuthController $authController;
    private YouTubeSubscriptionService $subscriptionService;

    public function __construct(
        SubscriberAuthController $authController,
        YouTubeSubscriptionService $subscriptionService
    ) {
        $this->authController = $authController;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get channelname from route parameters
            $channelnameParam = $request->route('channelname');
            $slug = $request->route('slug');

            // Handle route model binding - channelname might already be a Tenant object
            if ($channelnameParam instanceof Tenant) {
                $tenant = $channelnameParam;
                $channelname = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelname = $channelnameParam;
                if (!$channelname) {
                    Log::error('No channelname in route for subscription verification');
                    return redirect()->route('home')->with('error', 'Invalid request.');
                }

                // Find tenant by channel name
                $tenant = $this->findTenantByChannelName($channelname);

                if (!$tenant) {
                    Log::error('Tenant not found for channelname', ['channelname' => $channelname]);
                    return redirect()->route('home')->with('error', 'Channel not found.');
                }
            }

            // Check if subscriber LMS is enabled for this tenant
            if (!$tenant->hasSubscriberLmsEnabled()) {
                Log::error('Subscriber LMS not enabled for tenant', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelname
                ]);
                return redirect()->route('home')->with('error', 'This feature is not available.');
            }

            // Check if user is authenticated as a subscriber for this tenant
            if (!$this->authController->isAuthenticatedForTenant($tenant)) {
                Log::info('User not authenticated, redirecting to login', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelname,
                    'slug' => $slug
                ]);

                // Redirect to login page for this specific content
                $slugStr = null;
                if ($slug instanceof \App\Models\SubscriberContent) {
                    $slugStr = $slug->slug;
                } elseif (is_string($slug)) {
                    $slugStr = $slug;
                }

                return redirect()->route('subscriber.login', [
                    'channelname' => $channelname,
                    'slug' => $slugStr
                ]);
            }

            // Get authenticated subscriber
            $subscriber = $this->authController->getAuthenticatedSubscriber();

            if (!$subscriber) {
                Log::error('Authenticated subscriber not found');
                return $this->showLoginPage($tenant, $channelname, $slug);
            }

            // Check subscription status (uses cache if valid)
            $isSubscribed = $this->subscriptionService->verifySubscription($subscriber, $tenant);

            if (!$isSubscribed) {
                Log::info('Subscriber is not subscribed or subscription expired', [
                    'subscriber_user_id' => $subscriber->id,
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelname
                ]);

                // Show access denied page
                return $this->showAccessDenied($tenant, $channelname, $slug);
            }

            // Add tenant and subscriber to request for use in controllers
            $request->attributes->set('tenant', $tenant);
            $request->attributes->set('subscriber', $subscriber);

            Log::info('Subscription verification passed', [
                'subscriber_user_id' => $subscriber->id,
                'tenant_id' => $tenant->id,
                'channelname' => $channelname,
                'slug' => $slug
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Error in subscription verification middleware', [
                'channelname' => $channelname ?? 'unknown',
                'slug' => $slug ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Show login page for specific content
     */
    private function showLoginPage(Tenant $tenant, string $channelname, $slug): \Illuminate\View\View
    {
        // Handle slug parameter - might be object or string
        $slugStr = null;
        $contentTitle = null;
        
        if ($slug instanceof \App\Models\SubscriberContent) {
            $slugStr = $slug->slug;
            $contentTitle = $slug->title;
        } elseif (is_string($slug)) {
            $slugStr = $slug;
            $contentTitle = $this->getContentTitle($tenant, $slug);
        }

        return response()->view('diamonds.subscriber.login', [
            'tenant' => $tenant,
            'channelname' => $channelname,
            'slug' => $slugStr,
            'contentTitle' => $contentTitle,
            'loginText' => $tenant->member_login_text,
            'profileImage' => $tenant->member_profile_image,
            'channelBanner' => $tenant->ytChannel?->banner_image_url,
            'oauthUrl' => route('subscriber.auth.google', ['channelname' => $channelname, 'slug' => $slugStr])
        ]);
    }

    /**
     * Show access denied page
     */
    private function showAccessDenied(Tenant $tenant, string $channelname, $slug): \Illuminate\View\View
    {
        // Handle slug parameter - might be object or string
        $slugStr = null;
        
        if ($slug instanceof \App\Models\SubscriberContent) {
            $slugStr = $slug->slug;
        } elseif (is_string($slug)) {
            $slugStr = $slug;
        }

        return response()->view('diamonds.subscriber.access-denied', [
            'tenant' => $tenant,
            'channelname' => $channelname,
            'slug' => $slugStr,
            'youtubeChannelUrl' => $tenant->ytChannel?->channel_url,
            'channelBanner' => $tenant->ytChannel?->banner_image_url,
            'tryAgainUrl' => route('subscriber.try-again', ['channelname' => $channelname, 'slug' => $slugStr])
        ]);
    }

    /**
     * Get content title for display on login page
     */
    private function getContentTitle(Tenant $tenant, string $slug): ?string
    {
        $content = $tenant->subscriberContent()->where('slug', $slug)->first();
        return $content?->title;
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
}
