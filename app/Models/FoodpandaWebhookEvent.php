<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodpandaWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event_type',
        'foodpanda_store_id',
        'foodpanda_order_id',
        'local_store_id',
        'status',
        'processed_at',
        'error_message',
        'payload',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'local_store_id');
    }
}
