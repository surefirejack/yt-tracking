<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Link extends Model
{
    protected $fillable = [
        'tenant_id',
        'original_url',
        'dub_id',
        'domain',
        'key',
        'url',
        'short_link',
        'archived',
        'expires_at',
        'expired_url',
        'password',
        'track_conversion',
        'proxy',
        'title',
        'description',
        'image',
        'video',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'rewrite',
        'do_index',
        'ios',
        'android',
        'geo',
        'test_variants',
        'test_started_at',
        'test_completed_at',
        'user_id_dub',
        'project_id',
        'program_id',
        'folder_id',
        'external_id',
        'tenant_id_dub',
        'public_stats',
        'clicks',
        'last_clicked',
        'leads',
        'sales',
        'sale_amount',
        'comments',
        'partner_id',
        'tags',
        'identifier',
        'tag_id',
        'webhook_ids',
        'qr_code',
        'workspace_id',
        'status',
        'error_message',
    ];

    protected $casts = [
        'archived' => 'boolean',
        'expires_at' => 'datetime',
        'track_conversion' => 'boolean',
        'proxy' => 'boolean',
        'rewrite' => 'boolean',
        'do_index' => 'boolean',
        'geo' => 'array',
        'test_variants' => 'array',
        'test_started_at' => 'datetime',
        'test_completed_at' => 'datetime',
        'public_stats' => 'boolean',
        'clicks' => 'integer',
        'last_clicked' => 'datetime',
        'leads' => 'integer',
        'sales' => 'integer',
        'sale_amount' => 'decimal:2',
        'tags' => 'array',
        'webhook_ids' => 'array',
    ];

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Check if link is successfully created
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if link creation failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if link is being processed
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if link is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
