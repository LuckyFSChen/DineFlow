<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\StoreInvoice;
use App\Services\Invoice\InvoiceGatewayService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IssueOrderInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(InvoiceGatewayService $gateway): void
    {
        $order = Order::query()
            ->with(['store.invoiceSetting', 'invoice'])
            ->find($this->orderId);

        if (! $order) {
            return;
        }

        $paymentStatus = strtolower((string) $order->payment_status);
        if ($paymentStatus !== 'paid') {
            return;
        }

        $invoice = StoreInvoice::query()->firstOrNew([
            'order_id' => $order->id,
        ], [
            'store_id' => $order->store_id,
        ]);

        if ($invoice->exists && $invoice->status === StoreInvoice::STATUS_ISSUED) {
            if ($invoice->upload_status !== 'uploaded') {
                UploadStoreInvoiceJob::dispatch($invoice->id);
            }

            return;
        }

        if ($invoice->exists && $invoice->status === StoreInvoice::STATUS_VOIDED) {
            return;
        }

        $setting = $order->store?->invoiceSetting;

        $invoice->fill([
            'store_id' => $order->store_id,
            'invoice_flow' => (string) ($order->invoice_flow ?? 'none'),
            'carrier_type' => $this->resolveCarrierType($order->invoice_flow),
            'carrier_code' => $order->invoice_mobile_barcode ?: $order->invoice_member_carrier_code,
            'donation_code' => $order->invoice_donation_code,
            'company_tax_id' => $order->invoice_company_tax_id,
            'amount' => max((int) $order->total, 0),
            'status' => StoreInvoice::STATUS_PROCESSING,
            'issue_attempts' => (int) $invoice->issue_attempts + 1,
            'upload_status' => 'pending',
        ]);
        $invoice->save();

        if (! $setting || ! $setting->isReadyForIssue()) {
            $invoice->fill([
                'status' => StoreInvoice::STATUS_FAILED,
                'last_error' => '店家尚未完成電子發票開通精靈。',
            ])->save();

            return;
        }

        try {
            $result = $gateway->issueOrderInvoice($order, $setting);

            $invoice->fill([
                'status' => StoreInvoice::STATUS_ISSUED,
                'invoice_number' => (string) ($result['invoice_number'] ?? ''),
                'random_number' => (string) ($result['random_number'] ?? ''),
                'issued_at' => Carbon::now(),
                'upload_status' => 'pending',
                'last_error' => null,
                'qr_code_url' => $result['qr_code_url'] ?? null,
                'pdf_url' => $result['pdf_url'] ?? null,
                'provider_payload' => $result['provider_payload'] ?? null,
                'legal_deadline_at' => Carbon::now()->addHours(max((int) config('invoice.upload_deadline_hours', 48), 1)),
            ])->save();

            UploadStoreInvoiceJob::dispatch($invoice->id);
        } catch (\Throwable $e) {
            $invoice->fill([
                'status' => StoreInvoice::STATUS_FAILED,
                'last_error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $invoice = StoreInvoice::query()
            ->where('order_id', $this->orderId)
            ->first();

        if (! $invoice) {
            return;
        }

        $invoice->fill([
            'status' => StoreInvoice::STATUS_FAILED,
            'last_error' => $e->getMessage(),
        ])->save();
    }

    private function resolveCarrierType(?string $flow): ?string
    {
        return match ((string) $flow) {
            'mobile_barcode' => 'mobile',
            'member_carrier' => 'member',
            'donation_code' => 'donation',
            'company_tax_id' => 'company',
            default => null,
        };
    }
}

