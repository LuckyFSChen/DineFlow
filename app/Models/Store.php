<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'phone',
        'address',
        'currency',
        'contact_email',
        'notification_email',
        'is_active',
        'takeout_qr_enabled',
        'checkout_timing',
        'banner_image',
        'opening_time',
        'closing_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'takeout_qr_enabled' => 'boolean',
    ];

    public function getBannerImageUrlAttribute(): ?string
    {
        if ($this->banner_image) {
            return asset('storage/' . $this->banner_image);
        }

        return null;
    }

    protected static function booted(): void
    {
        static::creating(function ($store) {
            $store->slug = $store->slug ?: 'temp';
        });

        static::created(function ($store) {
            if ($store->slug === 'temp') {
                $store->slug = 'store-' . $store->id;
                $store->saveQuietly();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function hasBusinessHours(): bool
    {
        return filled($this->opening_time) && filled($this->closing_time);
    }

    public function isWithinBusinessHours(?Carbon $dateTime = null): bool
    {
        if (! $this->hasBusinessHours()) {
            return true;
        }

        $dateTime ??= now();

        $current = $dateTime->format('H:i:s');
        $openingTime = $this->normalizeTime($this->opening_time);
        $closingTime = $this->normalizeTime($this->closing_time);

        if ($openingTime === $closingTime) {
            return true;
        }

        if ($openingTime < $closingTime) {
            return $current >= $openingTime && $current <= $closingTime;
        }

        return $current >= $openingTime || $current <= $closingTime;
    }

    public function isOrderingAvailable(?Carbon $dateTime = null): bool
    {
        return $this->is_active && $this->isWithinBusinessHours($dateTime);
    }

    public function businessHoursLabel(): string
    {
        if (! $this->hasBusinessHours()) {
            return '未設定';
        }

        return sprintf(
            '%s - %s',
            substr($this->normalizeTime($this->opening_time), 0, 5),
            substr($this->normalizeTime($this->closing_time), 0, 5),
        );
    }

    public function orderingStatusLabel(?Carbon $dateTime = null): string
    {
        if (! $this->is_active) {
            return '停用';
        }

        if (! $this->isWithinBusinessHours($dateTime)) {
            return '非營業時間';
        }

        return '營業中';
    }

    public function orderingClosedMessage(?Carbon $dateTime = null): string
    {
        if (! $this->is_active) {
            return '此店家目前暫停接單。';
        }

        if (! $this->isWithinBusinessHours($dateTime)) {
            if ($this->hasBusinessHours()) {
                return '目前非營業時間，暫不開放點餐。營業時間為 ' . $this->businessHoursLabel() . '。';
            }

            return '此店家目前暫不開放點餐。';
        }

        return '';
    }

    public function isPrepayCheckout(): bool
    {
        return $this->checkout_timing === 'prepay';
    }

    public function checkoutTimingLabel(): string
    {
        return $this->isPrepayCheckout() ? '餐前結帳' : '餐後結帳';
    }

    public function tables()
    {
        return $this->hasMany(DiningTable::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function chefs()
    {
        return $this->hasMany(User::class, 'store_id')->where('role', 'chef');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    private function normalizeTime(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return strlen($time) === 5 ? $time . ':00' : substr($time, 0, 8);
    }
}
