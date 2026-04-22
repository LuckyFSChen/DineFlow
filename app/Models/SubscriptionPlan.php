<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'max_stores' => 'integer',
        'discount_twd' => 'integer',
    ];
}
