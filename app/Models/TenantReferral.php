<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantReferral extends Model
{
    protected $fillable = [
        'tenant_id',
        'clicks',
        'conversions',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    public function incrementConversions(): void
    {
        $this->increment('conversions');
    }
}
