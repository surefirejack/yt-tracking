<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriberContent extends Model
{
    protected $table = 'subscriber_content';

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'content',
        'youtube_video_url',
        'file_paths',
        'file_names',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'file_paths' => 'array',
        'file_names' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($content) {
            if (empty($content->slug)) {
                $content->slug = Str::slug($content->title);
            }
        });
        
        static::updating(function ($content) {
            if ($content->isDirty('title')) {
                $content->slug = Str::slug($content->title);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ContentDownload::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
