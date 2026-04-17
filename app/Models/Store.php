<?php

namespace App\Models;

use App\Support\PhoneFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Store extends Model
{
    use SoftDeletes;

    private static ?array $validTimezoneIdentifiers = null;

    private ?string $resolvedBusinessTimezoneCache = null;

    private ?array $normalizedWeeklyBusinessHoursCache = null;

    private ?array $normalizedWeeklyBreakHoursCache = null;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'phone',
        'address',
        'latitude',
        'longitude',
        'currency',
        'country_code',
        'timezone',
        'monthly_revenue_target',
        'contact_email',
        'notification_email',
        'is_active',
        'takeout_qr_enabled',
        'checkout_timing',
        'banner_image',
        'opening_time',
        'closing_time',
        'weekly_business_hours',
        'prep_time_minutes',
        'loyalty_enabled',
        'points_per_amount',
        'weekly_break_hours',
        'cancel_quick_reasons',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'takeout_qr_enabled' => 'boolean',
        'monthly_revenue_target' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'weekly_business_hours' => 'array',
        'prep_time_minutes' => 'integer',
        'loyalty_enabled' => 'boolean',
        'points_per_amount' => 'integer',
        'weekly_break_hours' => 'array',
        'cancel_quick_reasons' => 'array',
    ];

    public function getBannerImageUrlAttribute(): ?string
    {
        if ($this->banner_image) {
            return asset('storage/' . $this->banner_image);
        }

        return null;
    }

    public function getPhoneAttribute($value): ?string
    {
        return PhoneFormatter::format($value);
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = PhoneFormatter::digitsOnly(is_string($value) ? $value : null);
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
        if ($this->hasWeeklyBusinessHours()) {
            return true;
        }

        return filled($this->opening_time) && filled($this->closing_time);
    }

    public function isWithinBusinessHours(?Carbon $dateTime = null): bool
    {
        $timezone = $this->businessTimezone();
        $dateTime = $dateTime
            ? $dateTime->copy()->setTimezone($timezone)
            : now($timezone);

        if ($this->hasWeeklyBusinessHours()) {
            if (! $this->isWithinWeeklyBusinessHours($dateTime)) {
                return false;
            }

            return ! $this->isWithinWeeklyBreakHours($dateTime);
        }

        if (! $this->hasBusinessHours()) {
            return true;
        }

        $current = $dateTime->format('H:i:s');
        $openingTime = $this->normalizeTime($this->opening_time);
        $closingTime = $this->normalizeTime($this->closing_time);

        if ($openingTime === $closingTime) {
            return true;
        }

        if ($openingTime < $closingTime) {
            $isWithinOpeningRange = $current >= $openingTime && $current <= $closingTime;
        } else {
            $isWithinOpeningRange = $current >= $openingTime || $current <= $closingTime;
        }

        if (! $isWithinOpeningRange) {
            return false;
        }

        return ! $this->isWithinWeeklyBreakHours($dateTime);
    }

    public function businessTimezone(): string
    {
        if ($this->resolvedBusinessTimezoneCache !== null) {
            return $this->resolvedBusinessTimezoneCache;
        }

        $storeTimezone = (string) ($this->timezone ?? '');
        if ($storeTimezone !== '' && isset(self::validTimezoneIdentifierMap()[$storeTimezone])) {
            return $this->resolvedBusinessTimezoneCache = $storeTimezone;
        }

        return $this->resolvedBusinessTimezoneCache = match (strtolower((string) $this->country_code)) {
            'vn' => 'Asia/Ho_Chi_Minh',
            'cn' => 'Asia/Shanghai',
            'us' => 'America/New_York',
            'tw' => 'Asia/Taipei',
            default => config('app.timezone', 'Asia/Taipei'),
        };
    }

    public function isOrderingAvailable(?Carbon $dateTime = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->owner && $this->owner->isMerchant() && ! $this->owner->hasActiveSubscription()) {
            return false;
        }

        return $this->isWithinBusinessHours($dateTime);
    }

    public function businessHoursLabel(): string
    {
        if ($this->hasWeeklyBusinessHours()) {
            $timezone = $this->businessTimezone();
            $today = now($timezone);
            $slot = $this->businessHoursSlotForDate($today);

            if (! is_array($slot)) {
                return '--';
            }

            $start = $this->normalizeTime((string) ($slot['start'] ?? ''));
            $end = $this->normalizeTime((string) ($slot['end'] ?? ''));

            if ($start === null || $end === null) {
                return '--';
            }

            if ($start === $end) {
                return '00:00 - 24:00';
            }

            return sprintf('%s - %s', substr($start, 0, 5), substr($end, 0, 5));
        }

        if (! $this->hasBusinessHours()) {
            return '--';
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
            return __('home.status_closed');
        }

        if ($this->owner && $this->owner->isMerchant() && ! $this->owner->hasActiveSubscription()) {
            return __('home.status_closed');
        }

        if (! $this->isWithinBusinessHours($dateTime)) {
            return __('home.status_closed');
        }

        return __('home.status_open');
    }

    public function orderingClosedMessage(?Carbon $dateTime = null): string
    {
        if (! $this->is_active) {
            return __('customer.ordering_closed');
        }

        if ($this->owner && $this->owner->isMerchant() && ! $this->owner->hasActiveSubscription()) {
            return __('customer.ordering_closed');
        }

        if (! $this->isWithinBusinessHours($dateTime)) {
            if ($this->hasBusinessHours()) {
                return __('customer.ordering_closed') . ' (' . $this->businessHoursLabel() . ')';
            }

            return __('customer.ordering_closed');
        }

        return '';
    }

    public function isPrepayCheckout(): bool
    {
        return $this->checkout_timing === 'prepay';
    }

    public function checkoutTimingLabel(): string
    {
        return $this->isPrepayCheckout() ? __('admin.checkout_prepay') : __('admin.checkout_postpay');
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

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function calculateEarnedPoints(int $paidAmount): int
    {
        if (! $this->loyalty_enabled) {
            return 0;
        }

        $unitAmount = max((int) ($this->points_per_amount ?? 100), 1);
        $paidAmount = max($paidAmount, 0);

        return (int) floor($paidAmount / $unitAmount);
    }

    private function normalizeTime(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return strlen($time) === 5 ? $time . ':00' : substr($time, 0, 8);
    }

    private function hasWeeklyBusinessHours(): bool
    {
        return $this->normalizedWeeklyBusinessHours() !== [];
    }

    private function isWithinWeeklyBusinessHours(Carbon $dateTime): bool
    {
        $weeklyBusinessHours = $this->normalizedWeeklyBusinessHours();
        if ($weeklyBusinessHours === []) {
            return false;
        }

        $today = $dateTime->copy()->startOfDay();

        foreach ([0, -1] as $dayOffset) {
            $targetDate = $today->copy()->addDays($dayOffset);
            $slot = $this->businessHoursSlotForDate($targetDate, $weeklyBusinessHours);

            if (! is_array($slot)) {
                continue;
            }

            $start = $this->normalizeTime((string) ($slot['start'] ?? ''));
            $end = $this->normalizeTime((string) ($slot['end'] ?? ''));

            if ($start === null || $end === null) {
                continue;
            }

            $startAt = $targetDate->copy()->setTimeFromTimeString($start);
            $endAt = $targetDate->copy()->setTimeFromTimeString($end);

            if ($start === $end) {
                $endAt = $startAt->copy()->addDay();
            } elseif ($endAt->lessThan($startAt)) {
                $endAt->addDay();
            }

            if ($dateTime->between($startAt, $endAt, true)) {
                return true;
            }
        }

        return false;
    }

    private function isWithinWeeklyBreakHours(Carbon $dateTime): bool
    {
        $weeklyBreakHours = $this->normalizedWeeklyBreakHours();
        if ($weeklyBreakHours === []) {
            return false;
        }

        $today = $dateTime->copy()->startOfDay();

        foreach ([0, -1] as $dayOffset) {
            $targetDate = $today->copy()->addDays($dayOffset);
            $dayKey = $this->weekdayKeyFromCarbon($targetDate);
            $slot = $weeklyBreakHours[$dayKey] ?? null;

            if (! is_array($slot)) {
                continue;
            }

            $start = $this->normalizeTime((string) ($slot['start'] ?? ''));
            $end = $this->normalizeTime((string) ($slot['end'] ?? ''));

            if ($start === null || $end === null) {
                continue;
            }

            if ($start === $end) {
                return true;
            }

            $startAt = $targetDate->copy()->setTimeFromTimeString($start);
            $endAt = $targetDate->copy()->setTimeFromTimeString($end);

            if ($endAt->lessThan($startAt)) {
                $endAt->addDay();
            }

            if ($dateTime->between($startAt, $endAt, true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizedWeeklyBusinessHours(): array
    {
        if ($this->normalizedWeeklyBusinessHoursCache !== null) {
            return $this->normalizedWeeklyBusinessHoursCache;
        }

        if (! is_array($this->weekly_business_hours)) {
            return $this->normalizedWeeklyBusinessHoursCache = [];
        }

        $allowedWeekdays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $normalized = [];

        foreach ($allowedWeekdays as $weekday) {
            $slot = $this->weekly_business_hours[$weekday] ?? null;
            if (! is_array($slot)) {
                continue;
            }

            $start = $this->normalizeTime((string) ($slot['start'] ?? ''));
            $end = $this->normalizeTime((string) ($slot['end'] ?? ''));

            if ($start === null || $end === null) {
                continue;
            }

            $normalized[$weekday] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $this->normalizedWeeklyBusinessHoursCache = $normalized;
    }

    private function normalizedWeeklyBreakHours(): array
    {
        if ($this->normalizedWeeklyBreakHoursCache !== null) {
            return $this->normalizedWeeklyBreakHoursCache;
        }

        if (! is_array($this->weekly_break_hours)) {
            return $this->normalizedWeeklyBreakHoursCache = [];
        }

        $allowedWeekdays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $normalized = [];

        foreach ($allowedWeekdays as $weekday) {
            $slot = $this->weekly_break_hours[$weekday] ?? null;
            if (! is_array($slot)) {
                continue;
            }

            $start = $this->normalizeTime((string) ($slot['start'] ?? ''));
            $end = $this->normalizeTime((string) ($slot['end'] ?? ''));

            if ($start === null || $end === null) {
                continue;
            }

            $normalized[$weekday] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $this->normalizedWeeklyBreakHoursCache = $normalized;
    }

    private function businessHoursSlotForDate(Carbon $date, ?array $weeklyBusinessHours = null): ?array
    {
        $source = $weeklyBusinessHours ?? $this->normalizedWeeklyBusinessHours();
        if ($source === []) {
            return null;
        }

        $dayKey = $this->weekdayKeyFromCarbon($date);
        $slot = $source[$dayKey] ?? null;

        return is_array($slot) ? $slot : null;
    }

    private function weekdayKeyFromCarbon(Carbon $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            default => 'sun',
        };
    }

    private static function validTimezoneIdentifierMap(): array
    {
        if (self::$validTimezoneIdentifiers === null) {
            self::$validTimezoneIdentifiers = array_fill_keys(timezone_identifiers_list(), true);
        }

        return self::$validTimezoneIdentifiers;
    }
}
