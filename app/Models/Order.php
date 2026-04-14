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
}
