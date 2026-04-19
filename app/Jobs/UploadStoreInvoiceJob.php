<?php

namespace App\Jobs;

use App\Models\StoreInvoice;
use App\Services\Invoice\InvoiceGatewayService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UploadStoreInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly int $invoiceId)
    {
    }

    public function handle(InvoiceGatewayService $gateway): void
    {
        $invoice = StoreInvoice::query()->find($this->invoiceId);

        if (! $invoice || $invoice->status !== StoreInvoice::STATUS_ISSUED) {
            return;
        }

        if ($invoice->upload_status === 'uploaded') {
            return;
        }

        $invoice->fill([
            'upload_status' => 'uploading',
        ])->save();

        try {
            $gateway->uploadInvoice($invoice);

            $invoice->fill([
                'upload_status' => 'uploaded',
                'uploaded_at' => Carbon::now(),
                'last_error' => null,
            ])->save();
        } catch (\Throwable $e) {
            $invoice->fill([
                'upload_status' => 'failed',
                'last_error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $invoice = StoreInvoice::query()->find($this->invoiceId);
        if (! $invoice) {
            return;
        }

        $invoice->fill([
            'upload_status' => 'failed',
            'last_error' => $e->getMessage(),
        ])->save();
    }
}

