<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'store_id',
        'dining_table_id',
        'order_type',
        'cart_token',
        'order_no',
        'status',
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
    }

    public function getCustomerStatusLabelAttribute(): string
    {
        return self::customerStatusLabel($this->status);
    }

    public static function customerStatusLabel(?string $status): string
    {
        $normalized = strtolower((string) $status);

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
