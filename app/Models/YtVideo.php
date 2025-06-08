<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Links relationship - one video can have many links
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class, 'yt_video_id');
    }
}
