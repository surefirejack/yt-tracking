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
        $tenantId = $request->query('tenant');
        
        if (!$tenantId) {
            // If no tenant in query, try to get from Filament context
            $tenant = Filament::getTenant();
            $tenantId = $tenant?->id;
        }
        
        \Log::info('YouTube Integration: tenant resolution', [
            'tenant_id' => $tenantId,
            'has_filament_tenant' => Filament::getTenant() !== null
        ]);
        
        if (!$tenantId) {
            \Log::error('YouTube Integration: No tenant found');
            abort(404, 'Tenant not found');
        }

        // Store the tenant ID in the session so we can retrieve it in the callback
        session(['integration_tenant_id' => $tenantId]);
        
        \Log::info('YouTube Integration: Starting OAuth redirect', [
            'tenant_id' => $tenantId
        ]);
        
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/youtube.readonly'])
            ->with(['state' => base64_encode(json_encode(['tenant_id' => $tenantId]))])
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $tenantId = session('integration_tenant_id');
            
            // If no tenant in session, try to get from OAuth state
            if (!$tenantId && $request->has('state')) {
                try {
                    $state = json_decode(base64_decode($request->get('state')), true);
                    $tenantId = $state['tenant_id'] ?? null;
                } catch (\Exception $e) {
                    // Ignore state parsing errors
                }
            }
            
            if (!$tenantId) {
                throw new \Exception('No tenant context found');
            }
            
            $tenant = \App\Models\Tenant::find($tenantId);
            
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }
            
            $user = auth()->user();
            $youtubeUser = Socialite::driver('google')->user();
            
            // Store YouTube integration data
            $user->userParameters()->updateOrCreate(
                ['name' => 'youtube_connected'],
                ['value' => true]
            );
            
            $user->userParameters()->updateOrCreate(
                ['name' => 'youtube_token'],
                ['value' => $youtubeUser->token]
            );
            
            if ($youtubeUser->refreshToken) {
                $user->userParameters()->updateOrCreate(
                    ['name' => 'youtube_refresh_token'],
                    ['value' => $youtubeUser->refreshToken]
                );
            }
            
            $user->userParameters()->updateOrCreate(
                ['name' => 'youtube_email'],
                ['value' => $youtubeUser->email]
            );
            
            // Clear the session
            session()->forget('integration_tenant_id');
            
            // Redirect back to settings with success message
            return redirect()->route('filament.dashboard.pages.settings', ['tenant' => $tenant->id])
                ->with('youtube_connected', true);
            
        } catch (\Exception $e) {
            // Clear the session
            session()->forget('integration_tenant_id');
            
            // Redirect back with error
            return redirect()->route('filament.dashboard.pages.settings', ['tenant' => $tenantId ?? 'default'])
                ->with('youtube_error', $e->getMessage());
        }
    }

    public function disconnect()
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        
        $user = auth()->user();
        
        // Remove YouTube integration data
        $user->userParameters()->where('name', 'like', 'youtube_%')->delete();
        
        return redirect()->route('filament.dashboard.pages.settings', ['tenant' => $tenant->id])
            ->with('youtube_disconnected', true);
    }
} 