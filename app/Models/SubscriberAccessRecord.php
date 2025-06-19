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
        'tenant_id',
        'tags_json',
        'cookie_token',
        'last_verified_at',
    ];

    protected $casts = [
        'email' => 'encrypted', // Automatically encrypt/decrypt email
        'tags_json' => 'array', // Automatically handle JSON array conversion
        'last_verified_at' => 'datetime',
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
} 