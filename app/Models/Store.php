<?php

namespace App\Models;

use App\Support\PhoneFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Store extends Model
{
    use SoftDeletes;

    private const ETA_ACTIVE_PENDING_STATUSES = ['pending', 'accepted', 'confirmed', 'received'];

    private const ETA_ACTIVE_PREPARING_STATUSES = ['preparing', 'processing', 'cooking', 'in_progress'];

    private const ETA_COMPLETED_STATUSES = ['complete', 'completed', 'ready', 'ready_for_pickup', 'picked_up', 'collected', 'served'];

    private const ETA_CANCELLED_STATUSES = ['cancel', 'cancelled', 'canceled'];

    private const ETA_AVERAGE_SAMPLE_LIMIT = 30;

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
        'points_reward',
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
        'points_reward' => 'integer',
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

    public function reviews()
    {
        return $this->hasMany(StoreReview::class);
    }

    public function invoiceSetting()
    {
        return $this->hasOne(StoreInvoiceSetting::class);
    }

    public function invoices()
    {
        return $this->hasMany(StoreInvoice::class);
    }

    public function invoiceAllowances()
    {
        return $this->hasMany(StoreInvoiceAllowance::class);
    }

    public function calculateEarnedPoints(int $paidAmount): int
    {
        if (! $this->loyalty_enabled) {
            return 0;
        }

        $unitAmount = max((int) ($this->points_per_amount ?? 100), 1);
        $rewardPoints = max((int) ($this->points_reward ?? 1), 1);
        $paidAmount = max($paidAmount, 0);

        return (int) (floor($paidAmount / $unitAmount) * $rewardPoints);
    }

    public function estimatePrepTimeMinutesForProductIds(iterable $productIds): int
    {
        $normalizedProductIds = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $fallbackMinutes = $this->defaultPrepTimeMinutes();

        if ($normalizedProductIds->isEmpty()) {
            return $fallbackMinutes;
        }

        return $this->estimatePrepTimeMinutesForOrderItems(
            $normalizedProductIds->map(fn (int $productId) => [
                'product_id' => $productId,
                'qty' => 1,
            ])->all()
        );
    }

    public function estimatePrepTimeMinutesForOrderItems(iterable $items): int
    {
        $fallbackMinutes = $this->defaultPrepTimeMinutes();

        $normalizedItems = collect($items)
            ->map(function ($item) {
                if ($item instanceof OrderItem) {
                    return [
                        'product_id' => (int) $item->product_id,
                        'qty' => max(1, (int) $item->qty),
                    ];
                }

                if (is_array($item)) {
                    return [
                        'product_id' => (int) ($item['product_id'] ?? 0),
                        'qty' => max(1, (int) ($item['qty'] ?? 1)),
                    ];
                }

                if (is_object($item)) {
                    return [
                        'product_id' => (int) ($item->product_id ?? 0),
                        'qty' => max(1, (int) ($item->qty ?? 1)),
                    ];
                }

                return [
                    'product_id' => (int) $item,
                    'qty' => 1,
                ];
            })
            ->filter(fn (array $item) => $item['product_id'] > 0)
            ->groupBy('product_id')
            ->map(fn ($group) => [
                'product_id' => (int) $group->first()['product_id'],
                'qty' => (int) $group->sum('qty'),
            ])
            ->values();

        if ($normalizedItems->isEmpty()) {
            return $fallbackMinutes;
        }

        $products = Product::query()
            ->with(['category:id,store_id,prep_time_minutes'])
            ->where('store_id', $this->id)
            ->whereIn('id', $normalizedItems->pluck('product_id')->all())
            ->get(['id', 'store_id', 'category_id']);

        if ($products->isEmpty()) {
            return $fallbackMinutes;
        }

        $qtyByProductId = $normalizedItems->pluck('qty', 'product_id');
        $categoryLoads = [];
        $distinctProductCount = $normalizedItems->count();

        foreach ($products as $product) {
            $qty = max(1, (int) ($qtyByProductId[$product->id] ?? 1));
            $categoryPrepMinutes = max(1, (int) ($product->category?->prep_time_minutes ?? $fallbackMinutes));
            $categoryKey = $product->category_id !== null ? 'category:' . $product->category_id : 'fallback';

            if (! isset($categoryLoads[$categoryKey])) {
                $categoryLoads[$categoryKey] = [
                    'prep_minutes' => $categoryPrepMinutes,
                    'qty' => 0,
                ];
            }

            $categoryLoads[$categoryKey]['prep_minutes'] = max(
                (int) $categoryLoads[$categoryKey]['prep_minutes'],
                $categoryPrepMinutes
            );
            $categoryLoads[$categoryKey]['qty'] += $qty;
        }

        if ($categoryLoads === []) {
            return $fallbackMinutes;
        }

        $criticalPathMinutes = collect($categoryLoads)
            ->map(function (array $load) {
                $prepMinutes = max(1, (int) ($load['prep_minutes'] ?? 1));
                $qty = max(1, (int) ($load['qty'] ?? 1));
                $parallelPenaltyPerExtraItem = max(1, (int) ceil($prepMinutes * 0.25));

                return $prepMinutes + max(0, $qty - 1) * $parallelPenaltyPerExtraItem;
            })
            ->max();

        $stationCount = count($categoryLoads);
        $coordinationBufferMinutes = min(
            15,
            max(0, $stationCount - 1) * 2 + max(0, $distinctProductCount - $stationCount)
        );

        return max($fallbackMinutes, (int) $criticalPathMinutes) + $coordinationBufferMinutes;
    }

    public function estimateCustomerReadyTimeForOrderItems(iterable $items, ?int $excludingOrderId = null, float $queueWeight = 1.0): array
    {
        $baseMinutes = $this->estimatePrepTimeMinutesForOrderItems($items);
        $averagePrepMinutes = $this->averageCompletedPrepTimeMinutes();
        $referencePrepMinutes = $averagePrepMinutes ?? (float) $this->defaultPrepTimeMinutes();
        $speedFactor = $this->resolveCustomerReadyTimeSpeedFactor($referencePrepMinutes);
        $adjustedBaseMinutes = max(1, (int) round($baseMinutes * $speedFactor));
        $workloadSnapshot = $this->activeCustomerReadyWorkloadSnapshot($excludingOrderId);
        $queueDelayMinutes = $this->estimateCustomerReadyQueueDelayMinutes(
            $workloadSnapshot,
            $referencePrepMinutes,
            $queueWeight
        );

        return [
            'minutes' => max(1, $adjustedBaseMinutes + $queueDelayMinutes),
            'base_minutes' => $baseMinutes,
            'adjusted_base_minutes' => $adjustedBaseMinutes,
            'queue_delay_minutes' => $queueDelayMinutes,
            'average_prep_minutes' => $averagePrepMinutes === null ? null : round($averagePrepMinutes, 1),
            'speed_factor' => $speedFactor,
            'active_order_count' => (int) ($workloadSnapshot['active_order_count'] ?? 0),
            'active_item_quantity' => (int) ($workloadSnapshot['active_item_quantity'] ?? 0),
        ];
    }

    public function estimateCustomerReadyTimeForOrder(Order $order): array
    {
        $normalizedStatus = strtolower((string) $order->status);

        if (in_array($normalizedStatus, self::ETA_CANCELLED_STATUSES, true)) {
            return [
                'minutes' => 0,
                'base_minutes' => 0,
                'adjusted_base_minutes' => 0,
                'queue_delay_minutes' => 0,
                'average_prep_minutes' => $this->averageCompletedPrepTimeMinutes(),
                'speed_factor' => 1.0,
                'active_order_count' => 0,
                'active_item_quantity' => 0,
            ];
        }

        if (in_array($normalizedStatus, self::ETA_COMPLETED_STATUSES, true)) {
            return [
                'minutes' => 0,
                'base_minutes' => 0,
                'adjusted_base_minutes' => 0,
                'queue_delay_minutes' => 0,
                'average_prep_minutes' => $this->averageCompletedPrepTimeMinutes(),
                'speed_factor' => 1.0,
                'active_order_count' => 0,
                'active_item_quantity' => 0,
            ];
        }

        $remainingItems = $this->remainingOrderItemsForCustomerReadyEstimate($order);

        if ($remainingItems === []) {
            return [
                'minutes' => 1,
                'base_minutes' => 0,
                'adjusted_base_minutes' => 0,
                'queue_delay_minutes' => 0,
                'average_prep_minutes' => $this->averageCompletedPrepTimeMinutes(),
                'speed_factor' => 1.0,
                'active_order_count' => 0,
                'active_item_quantity' => 0,
            ];
        }

        $queueWeight = in_array($normalizedStatus, self::ETA_ACTIVE_PREPARING_STATUSES, true)
            ? 0.45
            : 1.0;

        return $this->estimateCustomerReadyTimeForOrderItems($remainingItems, (int) $order->id, $queueWeight);
    }

    public function averageCompletedPrepTimeMinutes(int $sampleLimit = self::ETA_AVERAGE_SAMPLE_LIMIT): ?float
    {
        $recentCompletedOrders = Order::query()
            ->select(['id', 'store_id', 'created_at', 'updated_at'])
            ->with(['items:id,order_id,completed_at'])
            ->where('store_id', $this->id)
            ->whereIn('status', self::ETA_COMPLETED_STATUSES)
            ->latest('updated_at')
            ->limit(max(1, $sampleLimit))
            ->get();

        $average = $recentCompletedOrders
            ->map(function (Order $order): ?float {
                $completedAt = $order->items
                    ->pluck('completed_at')
                    ->filter()
                    ->sortDesc()
                    ->first();

                $endAt = $completedAt ?? $order->updated_at;

                if (! $order->created_at || ! $endAt) {
                    return null;
                }

                $seconds = $order->created_at->diffInSeconds($endAt, false);

                if ($seconds < 0) {
                    return null;
                }

                return round($seconds / 60, 1);
            })
            ->filter(fn (?float $minutes) => $minutes !== null)
            ->avg();

        return $average === null ? null : (float) $average;
    }

    public function customerReadyTimeLabel(?int $minutes): string
    {
        return $minutes !== null && $minutes > 0
            ? __('customer.estimated_prep_time_only', ['minutes' => $minutes])
            : __('customer.estimated_ready_time_unknown');
    }

    private function defaultPrepTimeMinutes(): int
    {
        return max(
            1,
            (int) ($this->prep_time_minutes ?? config('dineflow.default_prep_time_minutes', 30))
        );
    }

    private function activeCustomerReadyWorkloadSnapshot(?int $excludingOrderId = null): array
    {
        $activeStatuses = array_merge(self::ETA_ACTIVE_PENDING_STATUSES, self::ETA_ACTIVE_PREPARING_STATUSES);

        $orders = Order::query()
            ->select(['id', 'store_id', 'status'])
            ->with(['items:id,order_id,qty,item_status,completed_at'])
            ->where('store_id', $this->id)
            ->whereIn('status', $activeStatuses)
            ->when($excludingOrderId !== null, function ($query) use ($excludingOrderId) {
                $query->where('id', '!=', $excludingOrderId);
            })
            ->get();

        $snapshot = [
            'active_order_count' => 0,
            'active_order_units' => 0.0,
            'active_item_quantity' => 0,
            'active_item_units' => 0.0,
        ];

        foreach ($orders as $order) {
            $queueWeight = $this->activeOrderQueueWeight($order->status);

            if ($queueWeight <= 0) {
                continue;
            }

            $remainingItemQuantity = $this->remainingItemQuantityForCustomerReadyEstimate($order);

            $snapshot['active_order_count']++;
            $snapshot['active_order_units'] += $queueWeight;
            $snapshot['active_item_quantity'] += $remainingItemQuantity;
            $snapshot['active_item_units'] += $remainingItemQuantity * $queueWeight;
        }

        return $snapshot;
    }

    private function estimateCustomerReadyQueueDelayMinutes(array $snapshot, float $averagePrepMinutes, float $queueWeight = 1.0): int
    {
        $activeOrderUnits = (float) ($snapshot['active_order_units'] ?? 0);
        $activeItemUnits = (float) ($snapshot['active_item_units'] ?? 0);

        if ($activeOrderUnits <= 0 && $activeItemUnits <= 0) {
            return 0;
        }

        $normalizedQueueWeight = max(0.0, min(1.0, $queueWeight));
        $orderBufferPerUnit = max(1.0, round($averagePrepMinutes * 0.12, 1));
        $rawQueueDelay = ($activeOrderUnits * $orderBufferPerUnit) + ($activeItemUnits * 0.45);

        return min(60, max(0, (int) ceil($rawQueueDelay * $normalizedQueueWeight)));
    }

    private function resolveCustomerReadyTimeSpeedFactor(float $averagePrepMinutes): float
    {
        $baselineMinutes = (float) max(1, $this->defaultPrepTimeMinutes());

        return max(0.75, min(1.6, round($averagePrepMinutes / $baselineMinutes, 2)));
    }

    private function activeOrderQueueWeight(?string $status): float
    {
        $normalizedStatus = strtolower((string) $status);

        return match (true) {
            in_array($normalizedStatus, self::ETA_ACTIVE_PENDING_STATUSES, true) => 1.0,
            in_array($normalizedStatus, self::ETA_ACTIVE_PREPARING_STATUSES, true) => 0.65,
            default => 0.0,
        };
    }

    private function remainingOrderItemsForCustomerReadyEstimate(Order $order): array
    {
        $order->loadMissing(['items:id,order_id,product_id,qty,item_status,completed_at']);

        return $order->items
            ->filter(fn (OrderItem $item) => ! $this->isCompletedCustomerReadyOrderItem($item))
            ->values()
            ->all();
    }

    private function remainingItemQuantityForCustomerReadyEstimate(Order $order): int
    {
        $order->loadMissing(['items:id,order_id,qty,item_status,completed_at']);

        $remainingQuantity = (int) $order->items
            ->filter(fn (OrderItem $item) => ! $this->isCompletedCustomerReadyOrderItem($item))
            ->sum(fn (OrderItem $item) => max(1, (int) $item->qty));

        return max(1, $remainingQuantity);
    }

    private function isCompletedCustomerReadyOrderItem(OrderItem $item): bool
    {
        return strtolower((string) ($item->item_status ?? '')) === 'completed'
            || $item->completed_at !== null;
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
