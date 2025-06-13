<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class YtVideo extends Model
{
    protected $fillable = [
        'yt_channel_id',
        'title',
        'url',
        'description',
        'description_new',
        'converted_links',
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
        'converted_links' => 'integer',
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
     * Links relationship - many-to-many
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class, 'link_yt_video');
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

    /**
     * Extract URLs from the video description (uses description_new if not null)
     */
    public function getDescriptionUrls(): array
    {
        $desc = $this->description_new ?? $this->description;
        if (empty($desc)) {
            return [];
        }
        // Regular expression to match URLs
        $urlPattern = '/\\b(?:https?:\/\/|www\.)[^\s<>"{}|\\^`\[\]]+/i';
        
        // Find all matches
        preg_match_all($urlPattern, $desc, $matches);
        
        // Get unique URLs and sort alphabetically
        $uniqueUrls = array_unique($matches[0]);
        sort($uniqueUrls);
        
        return $uniqueUrls;
    }

    /**
     * Categorize URLs into allowed and excluded based on domains
     */
    public function getCategorizedUrls(): array
    {
        $urls = $this->getDescriptionUrls();
        
        $excludedDomains = [
            'youtu.be',
            'youtube.com',
            'facebook.com',
            'instagram.com',
            'twitter.com',
            'linkedin.com'
        ];
        
        $allowed = [];
        $excluded = [];
        
        foreach ($urls as $url) {
            $isExcluded = false;
            
            foreach ($excludedDomains as $domain) {
                if (stripos($url, $domain) !== false) {
                    $excluded[] = $url;
                    $isExcluded = true;
                    break;
                }
            }
            
            if (!$isExcluded) {
                $allowed[] = $url;
            }
        }
        
        return [
            'allowed' => $allowed,
            'excluded' => $excluded
        ];
    }

    /**
     * Get existing links for this video that match description URLs
     */
    public function getExistingLinkUrls(): array
    {
        return $this->links()
            ->pluck('original_url')
            ->toArray();
    }
}
