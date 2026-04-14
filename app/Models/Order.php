<?php

namespace App\Models;

use App\Mail\CustomerOrderCompletedMail;
use App\Mail\CustomerOrderCreatedMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class Order extends Model
{
    protected $fillable = [
        'store_id',
        'dining_table_id',
        'order_type',
        'cart_token',
        'order_no',
        'status',
        'payment_status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'note',
        'subtotal',
        'total',
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function table() {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
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
                    Mail::to($freshOrder->customer_email)->send(new CustomerOrderCreatedMail($freshOrder));
                } catch (Throwable $e) {
                    report($e);
                }
            });
        });

        static::updated(function (self $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $currentStatus = (string) $order->status;
            $previousStatus = (string) $order->getOriginal('status');

            if (! self::isCompletedStatus($currentStatus) || self::isCompletedStatus($previousStatus)) {
                return;
            }

            if (blank($order->customer_email)) {
                return;
            }

            DB::afterCommit(function () use ($order): void {
                $freshOrder = self::query()->with(['store', 'table', 'items'])->find($order->id);

                if (! $freshOrder || blank($freshOrder->customer_email)) {
                    return;
                }

                try {
                    Mail::to($freshOrder->customer_email)->send(new CustomerOrderCompletedMail($freshOrder));
                } catch (Throwable $e) {
                    report($e);
                }
            });
        });
    }

    private static function isCompletedStatus(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['complete', 'completed', 'ready', 'ready_for_pickup'], true);
    }

    public function getCustomerStatusLabelAttribute(): string
    {
        return self::customerStatusLabel($this->status, $this->payment_status);
    }

    public static function customerStatusLabel(?string $status, ?string $paymentStatus = null): string
    {
        $normalized = strtolower((string) $status);
        $normalizedPayment = strtolower((string) $paymentStatus);

        if (in_array($normalized, ['complete', 'completed', 'ready', 'ready_for_pickup'], true) && ($normalizedPayment === '' || $normalizedPayment === 'unpaid')) {
            return '待收款';
        }

        return match ($normalized) {
            'pending' => '已送出',
            'accepted', 'confirmed', 'received' => '已接單',
            'preparing', 'processing', 'cooking', 'in_progress' => '製作中',
            'complete', 'completed', 'ready', 'ready_for_pickup' => '餐點完成可取',
            'picked_up', 'collected', 'served' => '已取餐',
            'cancelled', 'canceled' => '訂單已取消',
            default => '訂單狀態更新中',
        };
    }
}
