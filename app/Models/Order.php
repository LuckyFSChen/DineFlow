<?php

namespace App\Models;

use App\Jobs\IssueOrderInvoiceJob;
use App\Jobs\VoidStoreInvoiceJob;
use App\Mail\CustomerOrderCompletedMail;
use App\Mail\CustomerOrderCancelledMail;
use App\Mail\CustomerOrderCreatedMail;
use App\Support\PhoneFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class Order extends Model
{
    private const SUPPORTED_ORDER_LOCALES = ['zh_TW', 'zh_CN', 'en', 'vi'];

    protected $fillable = [
        'store_id',
        'member_id',
        'coupon_id',
        'dining_table_id',
        'order_type',
        'cart_token',
        'order_no',
        'status',
        'payment_status',
        'invoice_flow',
        'invoice_mobile_barcode',
        'invoice_member_carrier_code',
        'invoice_donation_code',
        'invoice_company_tax_id',
        'invoice_company_name',
        'invoice_requested_at',
        'customer_name',
        'customer_phone',
        'customer_email',
        'order_locale',
        'source_platform',
        'source_order_id',
        'source_store_id',
        'source_display_id',
        'platform_ordered_at',
        'source_payload',
        'note',
        'coupon_code',
        'coupon_discount',
        'points_used',
        'points_earned',
        'cancel_reason_options',
        'cancel_reason_other',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'cancel_reason_options' => 'array',
        'coupon_discount' => 'integer',
        'points_used' => 'integer',
        'points_earned' => 'integer',
        'invoice_requested_at' => 'datetime',
        'platform_ordered_at' => 'datetime',
        'source_payload' => 'array',
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function table() {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }

    public function member() {
        return $this->belongsTo(Member::class);
    }

    public function coupon() {
        return $this->belongsTo(Coupon::class);
    }

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function review()
    {
        return $this->hasOne(StoreReview::class);
    }

    public function invoice()
    {
        return $this->hasOne(StoreInvoice::class);
    }

    public function invoiceAllowances()
    {
        return $this->hasMany(StoreInvoiceAllowance::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function getCustomerPhoneAttribute($value): ?string
    {
        return PhoneFormatter::format($value);
    }

    public function setCustomerPhoneAttribute($value): void
    {
        $this->attributes['customer_phone'] = PhoneFormatter::digitsOnly(is_string($value) ? $value : null);
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }

            $order->order_locale = self::resolveOrderLocale($order->order_locale ?? app()->getLocale());
        });

        static::created(function (self $order) {
            if (blank($order->customer_email)) {
                return;
            }

            DB::afterCommit(function () use ($order): void {
                $freshOrder = self::query()->with(['store', 'table', 'items'])->find($order->id);

                if (! $freshOrder || blank($freshOrder->customer_email)) {
                    return;
                }

                try {
                    Mail::to($freshOrder->customer_email)
                        ->locale(self::resolveOrderLocale($freshOrder->order_locale))
                        ->queue(new CustomerOrderCreatedMail($freshOrder));
                } catch (Throwable $e) {
                    report($e);
                }
            });
        });

        static::updated(function (self $order) {
            $statusChanged = $order->wasChanged('status');
            $currentStatus = (string) $order->status;
            $previousStatus = (string) $order->getOriginal('status');

            if ($statusChanged && self::isCancelledStatus($currentStatus) && ! self::isCancelledStatus($previousStatus)) {
                if (blank($order->customer_email)) {
                    DB::afterCommit(function () use ($order): void {
                        $invoiceId = StoreInvoice::query()
                            ->where('order_id', $order->id)
                            ->value('id');

                        if ($invoiceId) {
                            VoidStoreInvoiceJob::dispatch((int) $invoiceId);
                        }
                    });

                    return;
                }

                DB::afterCommit(function () use ($order): void {
                    $freshOrder = self::query()->with(['store', 'table', 'items'])->find($order->id);

                    if (! $freshOrder || blank($freshOrder->customer_email)) {
                        return;
                    }

                    try {
                        Mail::to($freshOrder->customer_email)
                            ->locale(self::resolveOrderLocale($freshOrder->order_locale))
                            ->queue(new CustomerOrderCancelledMail($freshOrder));
                    } catch (Throwable $e) {
                        report($e);
                    }
                });

                DB::afterCommit(function () use ($order): void {
                    $invoiceId = StoreInvoice::query()
                        ->where('order_id', $order->id)
                        ->value('id');

                    if ($invoiceId) {
                        VoidStoreInvoiceJob::dispatch((int) $invoiceId);
                    }
                });

                return;
            }

            if ($statusChanged && self::isCompletedStatus($currentStatus) && ! self::isCompletedStatus($previousStatus)) {
                if (! blank($order->customer_email)) {
                    DB::afterCommit(function () use ($order): void {
                        $freshOrder = self::query()->with(['store', 'table', 'items'])->find($order->id);

                        if (! $freshOrder || blank($freshOrder->customer_email)) {
                            return;
                        }

                        try {
                            Mail::to($freshOrder->customer_email)
                                ->locale(self::resolveOrderLocale($freshOrder->order_locale))
                                ->queue(new CustomerOrderCompletedMail($freshOrder));
                        } catch (Throwable $e) {
                            report($e);
                        }
                    });
                }
            }

            $previousPaymentStatus = strtolower((string) $order->getOriginal('payment_status'));
            $currentPaymentStatus = strtolower((string) $order->payment_status);

            if ($order->wasChanged('payment_status') && $currentPaymentStatus === 'paid' && $previousPaymentStatus !== 'paid') {
                DB::afterCommit(function () use ($order): void {
                    IssueOrderInvoiceJob::dispatch($order->id);
                });
            }
        });
    }

    private static function isCompletedStatus(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['complete', 'completed', 'ready', 'ready_for_pickup'], true);
    }

    private static function isCancelledStatus(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['cancel', 'cancelled', 'canceled'], true);
    }

    public function resolvedCancelReasons(): array
    {
        $reasons = collect(is_array($this->cancel_reason_options) ? $this->cancel_reason_options : [])
            ->map(fn ($reason) => trim((string) $reason))
            ->filter(fn (string $reason) => $reason !== '');

        $otherReason = trim((string) ($this->cancel_reason_other ?? ''));
        if ($otherReason !== '') {
            $reasons->push($otherReason);
        }

        return $reasons->unique()->values()->all();
    }

    public function hasCancelReasons(): bool
    {
        return count($this->resolvedCancelReasons()) > 0;
    }

    public function getCustomerStatusLabelAttribute(): string
    {
        return self::customerStatusLabel($this->status, $this->payment_status);
    }

    public static function customerStatusLabel(?string $status, ?string $paymentStatus = null): string
    {
        return self::customerStatusLabelByLocale($status, $paymentStatus, app()->getLocale());
    }

    public static function customerStatusLabelByLocale(?string $status, ?string $paymentStatus = null, ?string $locale = null): string
    {
        $normalized = strtolower((string) $status);
        $normalizedPayment = strtolower((string) $paymentStatus);
        $resolvedLocale = self::resolveOrderLocale($locale);

        if (in_array($normalized, ['complete', 'completed', 'ready', 'ready_for_pickup'], true) && ($normalizedPayment === '' || $normalizedPayment === 'unpaid')) {
            return __('mail_orders.status.awaiting_payment', locale: $resolvedLocale);
        }

        return match ($normalized) {
            'pending' => __('mail_orders.status.pending', locale: $resolvedLocale),
            'accepted', 'confirmed', 'received' => __('mail_orders.status.accepted', locale: $resolvedLocale),
            'preparing', 'processing', 'cooking', 'in_progress' => __('mail_orders.status.preparing', locale: $resolvedLocale),
            'complete', 'completed', 'ready', 'ready_for_pickup' => __('mail_orders.status.completed', locale: $resolvedLocale),
            'picked_up', 'collected', 'served' => __('mail_orders.status.picked_up', locale: $resolvedLocale),
            'cancelled', 'canceled' => __('mail_orders.status.cancelled', locale: $resolvedLocale),
            default => __('mail_orders.status.updating', locale: $resolvedLocale),
        };
    }

    public static function resolveOrderLocale(?string $locale): string
    {
        $normalized = is_string($locale) ? trim($locale) : '';

        if (in_array($normalized, self::SUPPORTED_ORDER_LOCALES, true)) {
            return $normalized;
        }

        $fallback = (string) config('app.locale', 'zh_TW');

        return in_array($fallback, self::SUPPORTED_ORDER_LOCALES, true)
            ? $fallback
            : 'zh_TW';
    }

    public static function generateOrderNoForStore(int $storeId): string
    {
        $storeToken = strtoupper(str_pad(base_convert((string) max(0, $storeId), 10, 36), 2, '0', STR_PAD_LEFT));

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $randomToken = strtoupper(str_pad(base_convert((string) random_int(0, 1679615), 10, 36), 4, '0', STR_PAD_LEFT));
            $candidate = now()->format('ymd') . $storeToken . $randomToken;

            if (! self::query()->where('order_no', $candidate)->exists()) {
                return $candidate;
            }
        }

        return now()->format('ymdHis') . strtoupper(str_pad(base_convert((string) max(0, $storeId), 10, 36), 2, '0', STR_PAD_LEFT));
    }
}
