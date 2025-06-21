<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailSubscriberContent extends Model
{
    protected $table = 'email_subscriber_contents';

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'content',
        'required_tag_id',
        'file_paths',
        'file_names',
        'youtube_video_url',
        'cta_youtube_video_url',
    ];

    protected $casts = [
        'file_paths' => 'array',
        'file_names' => 'array',
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

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(EmailVerificationRequest::class, 'content_id');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
} 