<?php

namespace App\Models;

use App\Support\PhoneFormatter;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'email',
        'phone',
        'invoice_carrier_code',
        'invoice_carrier_bound_at',
        'points_balance',
        'total_spent',
        'total_orders',
        'last_order_at',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'total_spent' => 'integer',
        'total_orders' => 'integer',
        'last_order_at' => 'datetime',
        'invoice_carrier_bound_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function pointLedgers()
    {
        return $this->hasMany(MemberPointLedger::class);
    }

    public function getPhoneAttribute($value): ?string
    {
        return PhoneFormatter::format($value);
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = PhoneFormatter::digitsOnly(is_string($value) ? $value : null);
    }

    public function displayName(): string
    {
        $name = trim((string) ($this->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        if (filled($this->phone)) {
            return (string) $this->phone;
        }

        if (filled($this->email)) {
            return (string) $this->email;
        }

        return '匿名會員';
    }
}
