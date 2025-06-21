<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubscriberAccessRecord extends Model
{
    protected $fillable = [
        'email',
        'subscriber_id',
        'tenant_id',
        'tags_json',
        'cookie_token',
        'last_verified_at',
        'access_check_status',
        'access_check_started_at',
        'access_check_completed_at',
        'access_check_error',
    ];

    protected $casts = [
        'email' => 'encrypted', // Automatically encrypt/decrypt email
        'tags_json' => 'array', // Automatically handle JSON array conversion
        'last_verified_at' => 'datetime',
        'access_check_started_at' => 'datetime',
        'access_check_completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($record) {
            if (empty($record->cookie_token)) {
                $record->cookie_token = Str::random(64);
            }
            
            if (empty($record->last_verified_at)) {
                $record->last_verified_at = Carbon::now();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if the subscriber has a specific tag
     */
    public function hasTag(string $tagId): bool
    {
        return in_array($tagId, $this->tags_json ?? []);
    }

    /**
     * Add a tag to the subscriber's tag list
     */
    public function addTag(string $tagId): self
    {
        $tags = $this->tags_json ?? [];
        if (!in_array($tagId, $tags)) {
            $tags[] = $tagId;
            $this->update(['tags_json' => $tags]);
        }
        return $this;
    }

    /**
     * Remove a tag from the subscriber's tag list
     */
    public function removeTag(string $tagId): self
    {
        $tags = $this->tags_json ?? [];
        $tags = array_filter($tags, fn($tag) => $tag !== $tagId);
        $this->update(['tags_json' => array_values($tags)]);
        return $this;
    }

    /**
     * Update the last verified timestamp
     */
    public function updateLastVerified(): bool
    {
        $this->last_verified_at = Carbon::now();
        return $this->save();
    }

    /**
     * Check if access record is still valid based on tenant settings
     */
    public function isValid(): bool
    {
        $cacheValidUntil = $this->last_verified_at->addDays($this->tenant->subscription_cache_days ?? 7);
        return Carbon::now()->lessThan($cacheValidUntil);
    }

    /**
     * Check if access check is in progress
     */
    public function isCheckInProgress(): bool
    {
        return in_array($this->access_check_status, ['pending', 'processing']);
    }

    /**
     * Check if access check is completed
     */
    public function isCheckCompleted(): bool
    {
        return in_array($this->access_check_status, ['completed', 'failed']);
    }

    /**
     * Check if access check failed
     */
    public function isCheckFailed(): bool
    {
        return $this->access_check_status === 'failed';
    }

    /**
     * Get the processing time for the access check
     */
    public function getProcessingTime(): ?int
    {
        if ($this->access_check_started_at && $this->access_check_completed_at) {
            return $this->access_check_completed_at->diffInSeconds($this->access_check_started_at);
        }
        return null;
    }

    /**
     * Scope to find records by cookie token
     */
    public function scopeByCookieToken($query, string $token)
    {
        return $query->where('cookie_token', $token);
    }

    /**
     * Scope to find records by email for a specific tenant
     */
    public function scopeByEmailAndTenant($query, string $email, int $tenantId)
    {
        return $query->where('email', $email)->where('tenant_id', $tenantId);
    }

    /**
     * Scope to find records that need access checking
     */
    public function scopeNeedsAccessCheck($query)
    {
        return $query->where('access_check_status', 'pending');
    }

    /**
     * Scope to find records with completed access checks
     */
    public function scopeAccessCheckCompleted($query)
    {
        return $query->whereIn('access_check_status', ['completed', 'failed']);
    }
} 