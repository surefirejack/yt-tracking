<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SubscriberUser extends Model
{
    protected $fillable = [
        'tenant_id',
        'google_id',
        'email',
        'name',
        'profile_picture',
        'subscription_verified_at',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_token_expires_at',
    ];

    protected $casts = [
        'subscription_verified_at' => 'datetime',
        'oauth_token_expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ContentDownload::class);
    }

    public function isSubscriptionVerified(): bool
    {
        return !is_null($this->subscription_verified_at);
    }

    public function isSubscriptionCacheValid(): bool
    {
        if (!$this->isSubscriptionVerified()) {
            return false;
        }

        $cacheValidUntil = $this->subscription_verified_at->addDays($this->tenant->subscription_cache_days);
        return Carbon::now()->lessThan($cacheValidUntil);
    }

    public function markSubscriptionVerified(): void
    {
        $this->update(['subscription_verified_at' => Carbon::now()]);
    }

    public function clearSubscriptionVerification(): void
    {
        $this->update(['subscription_verified_at' => null]);
    }

    /**
     * Store OAuth tokens from Google
     */
    public function storeOAuthTokens(string $accessToken, ?string $refreshToken = null, ?int $expiresIn = null): void
    {
        $expiresAt = $expiresIn ? Carbon::now()->addSeconds($expiresIn) : Carbon::now()->addHour();
        
        $this->update([
            'oauth_access_token' => $accessToken,
            'oauth_refresh_token' => $refreshToken ?? $this->oauth_refresh_token, // Keep existing refresh token if none provided
            'oauth_token_expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check if the stored OAuth token is still valid (with 5 minute buffer)
     */
    public function hasValidOAuthToken(): bool
    {
        if (!$this->oauth_access_token || !$this->oauth_token_expires_at) {
            return false;
        }

        return Carbon::now()->addMinutes(5)->lessThan($this->oauth_token_expires_at);
    }

    /**
     * Alias for hasValidOAuthToken (plural form)
     */
    public function hasValidOAuthTokens(): bool
    {
        return $this->hasValidOAuthToken();
    }

    /**
     * Get a valid OAuth access token, refreshing if necessary
     */
    public function getValidOAuthToken(): ?string
    {
        if ($this->hasValidOAuthToken()) {
            return $this->oauth_access_token;
        }

        // Try to refresh the token if we have a refresh token
        if ($this->oauth_refresh_token) {
            return $this->refreshOAuthToken();
        }

        return null;
    }

    /**
     * Refresh the OAuth access token using the refresh token
     */
    private function refreshOAuthToken(): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
                'refresh_token' => $this->oauth_refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->storeOAuthTokens(
                    $data['access_token'],
                    null, // Keep existing refresh token
                    $data['expires_in'] ?? 3600
                );

                \Illuminate\Support\Facades\Log::info('OAuth token refreshed for subscriber', [
                    'subscriber_user_id' => $this->id,
                    'expires_at' => $this->oauth_token_expires_at->toDateTimeString()
                ]);

                return $data['access_token'];
            }

            \Illuminate\Support\Facades\Log::error('Failed to refresh OAuth token for subscriber', [
                'subscriber_user_id' => $this->id,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception refreshing OAuth token for subscriber', [
                'subscriber_user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Clear stored OAuth tokens
     */
    public function clearOAuthTokens(): void
    {
        $this->update([
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_token_expires_at' => null,
        ]);
    }
}
