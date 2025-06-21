<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailVerificationRequest extends Model
{
    protected $fillable = [
        'email',
        'verification_token',
        'content_id',
        'tenant_id',
        'expires_at',
        'verified_at',
        'esp_error',
        'esp_error_at',
    ];

    protected $casts = [
        'email' => 'encrypted', // Automatically encrypt/decrypt email
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'esp_error_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($request) {
            if (empty($request->verification_token)) {
                $request->verification_token = Str::random(64);
            }
            
            if (empty($request->expires_at)) {
                $request->expires_at = Carbon::now()->addHours(2); // 2-hour expiration
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(EmailSubscriberContent::class, 'content_id');
    }

    public function isExpired(): bool
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function markAsVerified(): self
    {
        $this->update(['verified_at' => Carbon::now()]);
        return $this;
    }

    /**
     * Scope to find non-expired verification requests
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to find expired verification requests
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }
} 