<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'uuid',
        'is_name_auto_generated',
    ];

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->using(TenantUser::class)->withPivot('id')->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
