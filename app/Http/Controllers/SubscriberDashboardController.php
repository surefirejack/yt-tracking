<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\SubscriberContent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class SubscriberDashboardController extends Controller
{
    /**
     * Display the subscriber dashboard with all available content.
     */
    public function index($channelname)
    {
        try {
            // Handle route model binding - channelname might be a Tenant object
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

            return view('subscriber.dashboard', [
                'tenant' => $tenant,
                'content' => $content,
                'channelname' => $channelnameStr
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading subscriber dashboard', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('home')->with('error', 'An error occurred.');
        }
    }

    /**
     * Handle logout for subscribers.
     */
    public function logout(Request $request, $channelname)
    {
        try {
            // Handle route model binding - channelname might be a Tenant object
            if ($channelname instanceof Tenant) {
                $tenant = $channelname;
                $channelnameStr = $tenant->getChannelName() ?? 'unknown';
            } else {
                $channelnameStr = $channelname;
                $tenant = Tenant::whereHas('ytChannel', function ($query) use ($channelnameStr) {
                    $query->where(\DB::raw('LOWER(REPLACE(handle, "@", ""))'), '=', strtolower($channelnameStr));
                })->first();
            }
            
            if ($tenant) {
                Log::info('Subscriber logout from dashboard', [
                    'tenant_id' => $tenant->id,
                    'channelname' => $channelnameStr,
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
            Log::error('Error during subscriber logout from dashboard', [
                'channelname' => is_object($channelname) ? get_class($channelname) : $channelname,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('home');
        }
    }
}
