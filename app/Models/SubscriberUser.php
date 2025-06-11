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
    ];

    protected $casts = [
        'subscription_verified_at' => 'datetime',
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
}
