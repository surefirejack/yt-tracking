<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Filament\Notifications\Notification;

class YouTubeIntegrationController extends Controller
{
    public function redirect(Request $request)
    {
        \Log::info('YouTube Integration: redirect method called', [
            'url' => $request->fullUrl(),
            'query' => $request->query->all(),
            'session' => session()->all()
        ]);
        
        // Try to get tenant from query parameter first (if accessing directly)
        $tenantParam = $request->query('tenant');
        $tenant = null;
        
        if ($tenantParam) {
            // Try to find tenant by UUID first, then by ID for backwards compatibility
            $tenant = \App\Models\Tenant::where('uuid', $tenantParam)->first() 
                   ?? \App\Models\Tenant::find($tenantParam);
        }
        
        if (!$tenant) {
            // If no tenant in query, try to get from Filament context
            $tenant = Filament::getTenant();
        }
        
        \Log::info('YouTube Integration: tenant resolution', [
            'tenant_param' => $tenantParam,
            'tenant_id' => $tenant?->id,
            'tenant_uuid' => $tenant?->uuid,
            'has_filament_tenant' => Filament::getTenant() !== null
        ]);
        
        if (!$tenant) {
            \Log::error('YouTube Integration: No tenant found');
            abort(404, 'Tenant not found');
        }

        // Store the tenant ID in the session so we can retrieve it in the callback
        session(['integration_tenant_id' => $tenant->id]);
        
        \Log::info('YouTube Integration: Starting OAuth redirect', [
            'tenant_id' => $tenant->id
        ]);
        
        $socialiteDriver = Socialite::driver('youtube')
            ->scopes(['https://www.googleapis.com/auth/youtube.force-ssl']);
        
        // Override redirect URL for local testing
        if (env('APP_ENV') === 'local') {
            $testingUrl = config('services.local.base_url') . '/integrations/youtube/callback';
            $socialiteDriver->redirectUrl($testingUrl);
        }
        
        return $socialiteDriver->redirect();
    }

    public function callback(Request $request)
    {
        \Log::info('YouTube Integration: callback method called', [
            'url' => $request->fullUrl(),
            'query' => $request->query->all(),
            'has_code' => $request->has('code'),
            'has_state' => $request->has('state'),
            'session' => session()->all()
        ]);
        
        try {
            $tenantId = session('integration_tenant_id');
            \Log::info('YouTube Integration: tenant ID from session', ['tenant_id' => $tenantId]);
            
            if (!$tenantId) {
                \Log::error('YouTube Integration: No tenant context found in session');
                throw new \Exception('No tenant context found');
            }
            
            $tenant = \App\Models\Tenant::find($tenantId);
            \Log::info('YouTube Integration: tenant found', ['tenant' => $tenant ? $tenant->uuid : 'not found']);
            
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }
            
            $user = auth()->user();
            \Log::info('YouTube Integration: user authenticated', ['user_id' => $user?->id]);
            
            if (!$user) {
                throw new \Exception('User not authenticated');
            }
            
            \Log::info('YouTube Integration: about to call Socialite');
            
            // Log the callback URL details for debugging
            \Log::info('YouTube Integration: callback URL details', [
                'request_url' => $request->url(),
                'full_url' => $request->fullUrl(),
                'google_config_redirect' => config('services.google.redirect')
            ]);
            
            // Set the correct redirect URL for the YouTube integration callback
            $driver = Socialite::driver('youtube');
            
            // Override redirect URL for local testing
            if (env('APP_ENV') === 'local') {
                $callbackUrl = config('services.local.base_url') . '/integrations/youtube/callback';
            } else {
                $callbackUrl = url('/integrations/youtube/callback');
            }
            
            $driver->redirectUrl($callbackUrl);
            \Log::info('YouTube Integration: using callback URL', ['callback_url' => $callbackUrl]);
            
            try {
                $youtubeUser = $driver->user();
                \Log::info('YouTube Integration: Socialite user retrieved successfully', [
                    'email' => $youtubeUser->email ?? 'null',
                    'id' => $youtubeUser->id ?? 'null',
                    'nickname' => $youtubeUser->nickname ?? 'null',
                    'has_token' => !empty($youtubeUser->token),
                    'has_refresh_token' => !empty($youtubeUser->refreshToken)
                ]);
            } catch (\Exception $socialiteError) {
                \Log::error('YouTube Integration: Socialite error', [
                    'error' => $socialiteError->getMessage(),
                    'code' => $socialiteError->getCode(),
                    'class' => get_class($socialiteError)
                ]);
                throw $socialiteError;
            }
            
            // Handle case where user might not have a YouTube channel
            if (!$youtubeUser || !$youtubeUser->token) {
                \Log::warning('YouTube Integration: User has no valid YouTube token');
                throw new \Exception('YouTube integration failed. This could happen if you don\'t have a YouTube channel associated with your Google account, or if the required permissions were not granted.');
            }
            
            \Log::info('YouTube Integration: storing user parameters');
            // Store YouTube integration data
            $user->userParameters()->updateOrCreate(
                ['name' => 'youtube_connected'],
                ['value' => true]
            );
            
            if ($youtubeUser->token) {
                $user->userParameters()->updateOrCreate(
                    ['name' => 'youtube_token'],
                    ['value' => $youtubeUser->token]
                );
            }
            
            if ($youtubeUser->refreshToken) {
                $user->userParameters()->updateOrCreate(
                    ['name' => 'youtube_refresh_token'],
                    ['value' => $youtubeUser->refreshToken]
                );
            }
            
            if ($youtubeUser->email) {
                $user->userParameters()->updateOrCreate(
                    ['name' => 'youtube_email'],
                    ['value' => $youtubeUser->email]
                );
            }
            
            // Store additional user info if available
            if ($youtubeUser->id) {
                $user->userParameters()->updateOrCreate(
                    ['name' => 'youtube_user_id'],
                    ['value' => $youtubeUser->id]
                );
            }
            
            if ($youtubeUser->nickname) {
                $user->userParameters()->updateOrCreate(
                    ['name' => 'youtube_nickname'],
                    ['value' => $youtubeUser->nickname]
                );
            }
            
            \Log::info('YouTube Integration: user parameters stored successfully');
            
            // Clear the session
            session()->forget('integration_tenant_id');
            
            \Log::info('YouTube Integration: redirecting to settings');
            // Redirect back to settings with success message
            return redirect()->route('filament.dashboard.pages.settings', ['tenant' => $tenant->uuid])
                ->with('youtube_connected', true);
            
        } catch (\Exception $e) {
            \Log::error('YouTube Integration: callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // Clear the session
            session()->forget('integration_tenant_id');
            
            // Redirect back with error
            return redirect()->route('filament.dashboard.pages.settings', ['tenant' => $tenantId ? \App\Models\Tenant::find($tenantId)?->uuid ?? 'default' : 'default'])
                ->with('youtube_error', $e->getMessage());
        }
    }

    public function disconnect()
    {
        \Log::info('YouTube Integration: disconnect method called');
        
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            \Log::error('YouTube Integration: Tenant not found in disconnect');
            abort(404, 'Tenant not found');
        }
        
        $user = auth()->user();
        \Log::info('YouTube Integration: disconnecting for user', ['user_id' => $user->id]);
        
        // Check existing parameters before deletion
        $existingParams = $user->userParameters()->where('name', 'like', 'youtube_%')->get();
        \Log::info('YouTube Integration: existing parameters before deletion', [
            'count' => $existingParams->count(),
            'parameters' => $existingParams->pluck('name')->toArray()
        ]);
        
        // Remove YouTube integration data
        $deletedCount = $user->userParameters()->where('name', 'like', 'youtube_%')->delete();
        \Log::info('YouTube Integration: parameters deleted', ['deleted_count' => $deletedCount]);
        
        // Verify deletion
        $remainingParams = $user->userParameters()->where('name', 'like', 'youtube_%')->get();
        \Log::info('YouTube Integration: remaining parameters after deletion', [
            'count' => $remainingParams->count(),
            'parameters' => $remainingParams->pluck('name')->toArray()
        ]);
        
        \Log::info('YouTube Integration: redirecting to settings');
        return redirect()->route('filament.dashboard.pages.settings', ['tenant' => $tenant->uuid])
            ->with('youtube_disconnected', true);
    }
} 