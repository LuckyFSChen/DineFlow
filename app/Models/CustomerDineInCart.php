<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDineInCart extends Model
{
    protected $fillable = [
        'store_id',
        'dining_table_id',
        'cart_items',
    ];

    protected $casts = [
        'cart_items' => 'array',
    ];
}
