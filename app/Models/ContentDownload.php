<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentDownload extends Model
{
    protected $fillable = [
        'subscriber_user_id',
        'subscriber_content_id',
        'file_name',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function subscriberUser(): BelongsTo
    {
        return $this->belongsTo(SubscriberUser::class);
    }

    public function subscriberContent(): BelongsTo
    {
        return $this->belongsTo(SubscriberContent::class);
    }
}
