<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberPointLedger extends Model
{
    protected $fillable = [
        'store_id',
        'member_id',
        'order_id',
        'points_change',
        'balance_after',
        'type',
        'note',
    ];

    protected $casts = [
        'points_change' => 'integer',
        'balance_after' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

