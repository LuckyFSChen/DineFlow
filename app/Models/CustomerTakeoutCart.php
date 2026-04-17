<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTakeoutCart extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'cart_items',
    ];

    protected $casts = [
        'cart_items' => 'array',
    ];
}
