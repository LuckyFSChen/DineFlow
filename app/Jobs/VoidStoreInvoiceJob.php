<?php

namespace App\Jobs;

use App\Models\StoreInvoice;
use App\Services\Invoice\InvoiceGatewayService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VoidStoreInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly int $invoiceId)
    {
    }

    public function handle(InvoiceGatewayService $gateway): void
    {
        $invoice = StoreInvoice::query()->with('order')->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        if ($invoice->status === StoreInvoice::STATUS_VOIDED) {
            return;
        }

        $orderStatus = strtolower((string) ($invoice->order?->status ?? ''));
        if (! in_array($orderStatus, ['cancel', 'cancelled', 'canceled'], true)) {
            return;
        }

        $invoice->fill([
            'status' => StoreInvoice::STATUS_VOID_PENDING,
            'void_attempts' => (int) $invoice->void_attempts + 1,
        ])->save();

        try {
            $gateway->voidInvoice($invoice);

            $invoice->fill([
                'status' => StoreInvoice::STATUS_VOIDED,
                'voided_at' => Carbon::now(),
                'upload_status' => 'uploaded',
                'uploaded_at' => Carbon::now(),
                'last_error' => null,
            ])->save();
        } catch (\Throwable $e) {
            $invoice->fill([
                'status' => StoreInvoice::STATUS_VOID_FAILED,
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
            'status' => StoreInvoice::STATUS_VOID_FAILED,
            'last_error' => $e->getMessage(),
        ])->save();
    }
}

