<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YtChannel extends Model
{
    protected $fillable = [
        'name',
        'type',
        'handle',
        'url',
        'tenant_id',
        'channel_id',
        'description',
        'additional_url',
        'logo_image_url',
        'subscribers_count',
        'videos_count',
        'last_update_requested_date',
        'last_update_received_date',
    ];

    protected $casts = [
        'subscribers_count' => 'integer',
        'videos_count' => 'integer',
        'last_update_requested_date' => 'datetime',
        'last_update_received_date' => 'datetime',
    ];

    /**
     * Tenant relationship for multi-tenancy (one-to-one)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Videos relationship - one channel has many videos
     */
    public function ytVideos(): HasMany
    {
        return $this->hasMany(YtVideo::class);
    }
}
