<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreInvoice extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_FAILED = 'failed';
    public const STATUS_VOID_PENDING = 'void_pending';
    public const STATUS_VOIDED = 'voided';
    public const STATUS_VOID_FAILED = 'void_failed';
    public const STATUS_ALLOWANCE_PENDING = 'allowance_pending';
    public const STATUS_ALLOWANCE_ISSUED = 'allowance_issued';
    public const STATUS_ALLOWANCE_FAILED = 'allowance_failed';

    protected $fillable = [
        'store_id',
        'order_id',
        'status',
        'invoice_number',
        'random_number',
        'invoice_flow',
        'carrier_type',
        'carrier_code',
        'donation_code',
        'company_tax_id',
        'amount',
        'issue_attempts',
        'void_attempts',
        'upload_status',
        'issued_at',
        'voided_at',
        'uploaded_at',
        'legal_deadline_at',
        'last_error',
        'qr_code_url',
        'pdf_url',
        'provider_payload',
    ];

    protected $casts = [
        'amount' => 'integer',
        'issue_attempts' => 'integer',
        'void_attempts' => 'integer',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
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

    public function allowances()
    {
        return $this->hasMany(StoreInvoiceAllowance::class);
    }
}

