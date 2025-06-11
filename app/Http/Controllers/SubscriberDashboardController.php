<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\SubscriberContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriberDashboardController extends Controller
{
    /**
     * Display the subscriber dashboard with all available content.
     */
    public function index(Request $request, Tenant $tenant): View
    {
        // Get all published content for this tenant
        $content = SubscriberContent::where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get the channel information for the header
        $channel = $tenant->ytChannel;

        return view('subscriber.dashboard', [
            'tenant' => $tenant,
            'content' => $content,
            'channel' => $channel,
            'channelname' => $tenant->getChannelName() ?? 'channel',
        ]);
    }

    /**
     * Handle logout for subscribers.
     */
    public function logout(Request $request, Tenant $tenant)
    {
        // Clear the subscriber session
        $request->session()->forget("subscriber_user_{$tenant->id}");
        $request->session()->forget("subscriber_authenticated_{$tenant->id}");
        
        // Log the logout action
        \Log::info('Subscriber logged out', [
            'tenant_id' => $tenant->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Redirect to custom logout URL if set, otherwise to the login page
        $redirectUrl = $tenant->logout_redirect_url;
        
        if (!empty($redirectUrl)) {
            return redirect()->to($redirectUrl);
        }

        // Default: redirect back to the member area (will show login page)
        $channelname = $tenant->getChannelName() ?? 'channel';
        return redirect()->route('subscriber.dashboard', ['channelname' => $channelname])
            ->with('message', 'You have been logged out successfully.');
    }
}
