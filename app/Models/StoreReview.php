<?php

namespace App\Models;

use App\Support\PhoneFormatter;
use Illuminate\Database\Eloquent\Model;

class StoreReview extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'user_id',
        'rating',
        'order_rating',
        'comment',
        'customer_name',
        'customer_email',
        'customer_phone',
        'is_visible',
    ];

    protected $casts = [
        'rating' => 'integer',
        'order_rating' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCustomerPhoneAttribute($value): ?string
    {
        return PhoneFormatter::format($value);
    }

    public function setCustomerPhoneAttribute($value): void
    {
        $this->attributes['customer_phone'] = PhoneFormatter::digitsOnly(is_string($value) ? $value : null);
    }
}
