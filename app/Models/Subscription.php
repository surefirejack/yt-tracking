<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;

class Subscription extends Model
{
    use HasFactory, VersionableTrait;
    protected string $versionClass = SubscriptionVersion::class;

    protected $fillable = [
        'user_id',
        'plan_id',
        'price',
        'currency_id',
        'ends_at',
        'status',
        'uuid',
        'cancelled_at',
        'payment_provider_subscription_id',
        'payment_provider_status',
        'payment_provider_id',
        'trial_ends_at',
        'interval_id',
        'interval_count',
        'is_canceled_at_end_of_cycle',
        'cancellation_reason',
        'cancellation_additional_info',
        'quantity',
        'tenant_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentProvider()
    {
        return $this->belongsTo(PaymentProvider::class);
    }

    public function interval()
    {
        return $this->belongsTo(Interval::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function discounts()
    {
        return $this->hasMany(SubscriptionDiscount::class);
    }

    public function getRouteKeyName(): string
    {
        // used to find a model by its uuid instead of its id
        return 'uuid';
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
