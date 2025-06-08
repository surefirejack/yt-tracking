<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Builder;

class YtVideo extends Model
{
    protected $fillable = [
        'yt_channel_id',
        'title',
        'url',
        'description',
        'views',
        'likes',
        'length',
        'auto_transcription',
        'custom_transcription',
        'custom_transcription_status',
        'summary',
        'summary_status',
        'video_id',
        'thumbnail_url',
        'published_at',
        'links_found',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views' => 'integer',
        'likes' => 'integer',
        'length' => 'integer',
        'links_found' => 'integer',
    ];

    /**
     * YouTube Channel relationship
     */
    public function ytChannel(): BelongsTo
    {
        return $this->belongsTo(YtChannel::class);
    }

    /**
     * Tenant relationship through channel
     */
    public function tenant(): HasOneThrough
    {
        return $this->hasOneThrough(Tenant::class, YtChannel::class, 'id', 'id', 'yt_channel_id', 'tenant_id');
    }

    /**
     * Links relationship - one video can have many links
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class, 'yt_video_id');
    }

    /**
     * Scope to filter by tenant through channel
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('ytChannel', function (Builder $query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        });
    }
}
