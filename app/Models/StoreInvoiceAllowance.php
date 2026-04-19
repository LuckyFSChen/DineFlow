<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreInvoiceAllowance extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'store_invoice_id',
        'status',
        'allowance_number',
        'amount',
        'reason',
        'attempts',
        'upload_status',
        'issued_at',
        'uploaded_at',
        'legal_deadline_at',
        'last_error',
        'provider_payload',
    ];

    protected $casts = [
        'amount' => 'integer',
        'attempts' => 'integer',
        'issued_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'legal_deadline_at' => 'datetime',
        'provider_payload' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice()
    {
        return $this->belongsTo(StoreInvoice::class, 'store_invoice_id');
    }
}

