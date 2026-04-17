<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'store_id',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'points_cost',
        'usage_limit',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_order_amount' => 'integer',
        'points_cost' => 'integer',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscountAmount(int $subtotal): int
    {
        $subtotal = max($subtotal, 0);

        if ($this->discount_type === 'percent') {
            $percent = max(0, min((int) $this->discount_value, 100));
            return (int) floor($subtotal * $percent / 100);
        }

        return min($subtotal, max((int) $this->discount_value, 0));
    }
}

