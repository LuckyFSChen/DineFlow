<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreInvoiceSetting extends Model
{
    protected $fillable = [
        'store_id',
        'onboarding_status',
        'wizard_step',
        'eligible_for_invoice',
        'provider_mode',
        'provider_name',
        'tax_id',
        'company_name',
        'branch_name',
        'company_address',
        'credential_notes',
        'credential_files',
        'invoice_track_prefix',
        'invoice_track_start',
        'invoice_track_end',
        'next_invoice_no',
        'store_no',
        'machine_no',
        'last_tested_at',
        'last_test_invoice_no',
        'blank_tracks_uploaded_at',
    ];

    protected $casts = [
        'wizard_step' => 'integer',
        'eligible_for_invoice' => 'boolean',
        'credential_files' => 'array',
        'invoice_track_start' => 'integer',
        'invoice_track_end' => 'integer',
        'next_invoice_no' => 'integer',
        'last_tested_at' => 'datetime',
        'blank_tracks_uploaded_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function isReadyForIssue(): bool
    {
        if (! $this->eligible_for_invoice) {
            return false;
        }

        return filled($this->provider_mode)
            && filled($this->tax_id)
            && filled($this->company_name)
            && filled($this->branch_name)
            && filled($this->company_address)
            && filled($this->invoice_track_prefix)
            && filled($this->invoice_track_start)
            && filled($this->invoice_track_end)
            && filled($this->next_invoice_no)
            && filled($this->store_no)
            && filled($this->machine_no);
    }

    public function remainingTrackCount(): ?int
    {
        if (! filled($this->invoice_track_end) || ! filled($this->next_invoice_no)) {
            return null;
        }

        return max(((int) $this->invoice_track_end) - ((int) $this->next_invoice_no) + 1, 0);
    }
}

