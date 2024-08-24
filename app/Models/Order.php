<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'currency_id',
        'total_amount',
        'total_amount_after_discount',
        'total_discount_amount',
        'payment_provider_order_id',
        'payment_provider_id',
        'tenant_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function discounts()
    {
        return $this->hasMany(OrderDiscount::class);
    }

    public function getRouteKeyName(): string
    {
        // used to find a model by its uuid instead of its id
        return 'uuid';
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentProvider()
    {
        return $this->belongsTo(PaymentProvider::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
