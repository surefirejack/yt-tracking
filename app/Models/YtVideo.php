<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YtVideo extends Model
{
    protected $fillable = [
        'tenant_id',
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
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views' => 'integer',
        'likes' => 'integer',
        'length' => 'integer',
    ];

    /**
     * Tenant relationship for multi-tenancy
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
