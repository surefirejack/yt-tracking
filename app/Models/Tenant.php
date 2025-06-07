<?php

namespace App\Models;

use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'uuid',
        'is_name_auto_generated',
        'created_by',
        'domain',
    ];

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(TenantUser::class)->withPivot('id')->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function stripeData(): HasOne
    {
        return $this->hasOne(UserStripeData::class);
    }

    public function customDomains(): HasMany
    {
        return $this->hasMany(TenantCustomDomain::class);
    }

    public function ytVideos(): HasManyThrough
    {
        return $this->hasManyThrough(YtVideo::class, YtChannel::class);
    }

    public function ytChannel(): HasOne
    {
        return $this->hasOne(YtChannel::class);
    }

    public function subscriptionProductMetadata()
    {
        /** @var SubscriptionService $subscriptionService */
        $subscriptionService = app(SubscriptionService::class);

        return $subscriptionService->getTenantSubscriptionProductMetadata($this);
    }

    /**
     * Get all active custom domains for this tenant
     */
    public function getActiveCustomDomains()
    {
        return $this->customDomains()->active()->get();
    }

    /**
     * Get all verified custom domains for this tenant
     */
    public function getVerifiedCustomDomains()
    {
        return $this->customDomains()->verified()->active()->get();
    }

    /**
     * Get the primary custom domain for this tenant
     */
    public function getPrimaryCustomDomain(): ?TenantCustomDomain
    {
        return $this->customDomains()->primary()->active()->first();
    }

    /**
     * Check if tenant has any verified custom domains
     */
    public function hasVerifiedCustomDomains(): bool
    {
        return $this->customDomains()->verified()->active()->exists();
    }

    /**
     * Add a new custom domain to this tenant
     */
    public function addCustomDomain(string $domain, bool $isPrimary = false): TenantCustomDomain
    {
        return $this->customDomains()->create([
            'domain' => $domain,
            'is_primary' => $isPrimary,
        ]);
    }
}
