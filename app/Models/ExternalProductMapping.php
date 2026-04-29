<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalProductMapping extends Model
{
    protected $fillable = [
        'store_id',
        'platform',
        'external_item_id',
        'external_item_name',
        'external_category_id',
        'external_category_name',
        'external_price',
        'external_currency',
        'product_id',
        'external_payload',
        'last_seen_at',
    ];

    protected $casts = [
        'external_price' => 'integer',
        'external_payload' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
