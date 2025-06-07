<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TenantCustomDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'domain',
        'is_verified',
        'is_primary',
        'verified_at',
        'verification_token',
        'ssl_status',
        'ssl_data',
        'is_active',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_primary' => 'boolean',
        'verified_at' => 'timestamp',
        'ssl_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->verification_token)) {
                $model->verification_token = Str::random(32);
            }
        });

        // Ensure only one primary domain per tenant
        static::saving(function ($model) {
            if ($model->is_primary && $model->isDirty('is_primary')) {
                static::where('tenant_id', $model->tenant_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_primary' => false]);
            }
        });
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only verified domains
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get only active domains
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get primary domain
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Check if domain is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Check if domain is primary
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Check if SSL is active
     */
    public function hasSsl(): bool
    {
        return $this->ssl_status === 'active';
    }

    /**
     * Mark domain as verified
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Set as primary domain (will unset other primary domains for this tenant)
     */
    public function setAsPrimary(): bool
    {
        return $this->update(['is_primary' => true]);
    }

    /**
     * Generate new verification token
     */
    public function regenerateVerificationToken(): bool
    {
        return $this->update(['verification_token' => Str::random(32)]);
    }

    /**
     * Get full domain URL
     */
    public function getFullUrlAttribute(): string
    {
        $protocol = $this->hasSsl() ? 'https' : 'http';
        return "{$protocol}://{$this->domain}";
    }
}
