<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'price_twd',
        'discount_twd',
        'duration_days',
        'max_stores',
        'features',
        'description',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price_twd' => 'integer',
        'duration_days' => 'integer',
        'max_stores' => 'integer',
        'discount_twd' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function merchantUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'merchant');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function oldChangeLogs(): HasMany
    {
        return $this->hasMany(SubscriptionChangeLog::class, 'old_plan_id');
    }

    public function newChangeLogs(): HasMany
    {
        return $this->hasMany(SubscriptionChangeLog::class, 'new_plan_id');
    }
}
