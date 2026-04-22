<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    private const DISCOUNT_TYPE_FIXED = 'fixed';
    private const DISCOUNT_TYPE_PERCENT = 'percent';
    private const DISCOUNT_TYPE_POINTS_REWARD = 'points_reward';

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'points_cost',
        'reward_per_amount',
        'reward_points',
        'usage_limit',
        'used_count',
        'starts_at',
        'ends_at',
        'allow_dine_in',
        'allow_takeout',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_order_amount' => 'integer',
        'points_cost' => 'integer',
        'reward_per_amount' => 'integer',
        'reward_points' => 'integer',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'allow_dine_in' => 'boolean',
        'allow_takeout' => 'boolean',
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
        $discountType = $this->normalizedDiscountType();

        if ($discountType === self::DISCOUNT_TYPE_POINTS_REWARD) {
            return 0;
        }

        if ($discountType === self::DISCOUNT_TYPE_PERCENT) {
            $percent = max(0, min((int) $this->discount_value, 100));
            return (int) floor($subtotal * $percent / 100);
        }

        return min($subtotal, max((int) $this->discount_value, 0));
    }

    public function calculateBonusPoints(int $subtotal): int
    {
        if (! $this->hasBonusPointsReward()) {
            return 0;
        }

        $subtotal = max($subtotal, 0);
        $unitAmount = max((int) $this->reward_per_amount, 1);
        $rewardPoints = max((int) $this->reward_points, 0);

        if ($rewardPoints === 0) {
            return 0;
        }

        return (int) (floor($subtotal / $unitAmount) * $rewardPoints);
    }

    public function hasDiscount(): bool
    {
        return max((int) $this->discount_value, 0) > 0
            && $this->normalizedDiscountType() !== self::DISCOUNT_TYPE_POINTS_REWARD;
    }

    public function hasBonusPointsReward(): bool
    {
        return max((int) $this->reward_per_amount, 0) > 0
            && max((int) $this->reward_points, 0) > 0;
    }

    public function normalizedDiscountType(): string
    {
        $raw = strtolower(trim((string) $this->discount_type));

        return match ($raw) {
            self::DISCOUNT_TYPE_PERCENT, 'percentage', 'percent_discount' => self::DISCOUNT_TYPE_PERCENT,
            self::DISCOUNT_TYPE_POINTS_REWARD, 'points', 'reward_points', 'point_reward' => self::DISCOUNT_TYPE_POINTS_REWARD,
            self::DISCOUNT_TYPE_FIXED, 'fixed_amount', 'amount', '' => self::DISCOUNT_TYPE_FIXED,
            default => self::DISCOUNT_TYPE_FIXED,
        };
    }

    public function isPointsRewardType(): bool
    {
        return $this->normalizedDiscountType() === self::DISCOUNT_TYPE_POINTS_REWARD;
    }

    public function isPercentType(): bool
    {
        return $this->normalizedDiscountType() === self::DISCOUNT_TYPE_PERCENT;
    }

    public function allowsDineIn(): bool
    {
        if (! array_key_exists('allow_dine_in', $this->attributes) || $this->attributes['allow_dine_in'] === null) {
            return true;
        }

        return (bool) $this->allow_dine_in;
    }

    public function allowsTakeout(): bool
    {
        if (! array_key_exists('allow_takeout', $this->attributes) || $this->attributes['allow_takeout'] === null) {
            return true;
        }

        return (bool) $this->allow_takeout;
    }

    public function isAvailableForOrderType(?string $orderType): bool
    {
        $normalized = strtolower(trim((string) $orderType));

        return match ($normalized) {
            'takeout', 'take_out' => $this->allowsTakeout(),
            'dine_in', 'dinein', '' => $this->allowsDineIn(),
            default => $this->allowsDineIn(),
        };
    }
}
