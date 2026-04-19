<?php

namespace App\Services\Invoice;

use App\Models\Order;
use App\Models\StoreInvoice;
use App\Models\StoreInvoiceAllowance;
use App\Models\StoreInvoiceSetting;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceGatewayService
{
    public function issueOrderInvoice(Order $order, StoreInvoiceSetting $setting): array
    {
        if (! $setting->isReadyForIssue()) {
            throw new RuntimeException('店家尚未完成電子發票開通設定。');
        }

        return DB::transaction(function () use ($setting, $order): array {
            $lockedSetting = StoreInvoiceSetting::query()
                ->whereKey($setting->id)
                ->lockForUpdate()
                ->firstOrFail();

            $invoiceNumber = $this->consumeInvoiceNumber($lockedSetting);
            $randomNumber = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $this->simulateFailure('issue', '開立發票 API 暫時無回應。');

            return [
                'invoice_number' => $invoiceNumber,
                'random_number' => $randomNumber,
                'qr_code_url' => url('/invoice/qr/' . $invoiceNumber),
                'pdf_url' => url('/invoice/pdf/' . $invoiceNumber),
                'provider_payload' => [
                    'provider' => $lockedSetting->provider_name,
                    'provider_mode' => $lockedSetting->provider_mode,
                    'store_no' => $lockedSetting->store_no,
                    'machine_no' => $lockedSetting->machine_no,
                    'order_no' => $order->order_no,
                    'response_at' => now()->toIso8601String(),
                ],
            ];
        });
    }

    public function uploadInvoice(StoreInvoice $invoice): array
    {
        $this->simulateFailure('upload', '發票上傳失敗，稍後重試。');

        return [
            'uploaded_at' => now()->toIso8601String(),
            'provider_trace_id' => 'UPL-' . strtoupper(bin2hex(random_bytes(6))),
        ];
    }

    public function voidInvoice(StoreInvoice $invoice): array
    {
        $this->simulateFailure('void', '作廢傳輸失敗，請稍後重試。');

        return [
            'voided_at' => now()->toIso8601String(),
            'provider_trace_id' => 'VOID-' . strtoupper(bin2hex(random_bytes(6))),
        ];
    }

    public function issueAllowance(StoreInvoiceAllowance $allowance, StoreInvoiceSetting $setting): array
    {
        if (! $setting->isReadyForIssue()) {
            throw new RuntimeException('店家尚未完成電子發票開通設定。');
        }

        $this->simulateFailure('allowance', '折讓單傳輸失敗，請稍後重試。');

        $allowanceNo = 'ALW' . now()->format('ymdHis') . random_int(10, 99);

        return [
            'allowance_number' => $allowanceNo,
            'issued_at' => now()->toIso8601String(),
            'provider_payload' => [
                'provider' => $setting->provider_name,
                'store_no' => $setting->store_no,
                'machine_no' => $setting->machine_no,
                'invoice_number' => $allowance->invoice?->invoice_number,
            ],
        ];
    }

    public function issueTestInvoice(StoreInvoiceSetting $setting): array
    {
        if (! $setting->isReadyForIssue()) {
            throw new RuntimeException('請先完成開通精靈必要欄位。');
        }

        return DB::transaction(function () use ($setting): array {
            $lockedSetting = StoreInvoiceSetting::query()
                ->whereKey($setting->id)
                ->lockForUpdate()
                ->firstOrFail();

            $invoiceNumber = $this->consumeInvoiceNumber($lockedSetting);

            return [
                'invoice_number' => $invoiceNumber,
                'tested_at' => now(),
            ];
        });
    }

    private function consumeInvoiceNumber(StoreInvoiceSetting $setting): string
    {
        $prefix = strtoupper(trim((string) $setting->invoice_track_prefix));
        if ($prefix === '') {
            throw new RuntimeException('字軌前綴未設定。');
        }

        $start = max((int) $setting->invoice_track_start, 1);
        $end = max((int) $setting->invoice_track_end, 0);
        $next = (int) ($setting->next_invoice_no ?: $start);

        if ($next < $start) {
            $next = $start;
        }

        if ($end > 0 && $next > $end) {
            throw new RuntimeException('字軌號碼已用完，請先上傳新字軌。');
        }

        $setting->next_invoice_no = $next + 1;
        $setting->save();

        return substr($prefix, 0, 2) . str_pad((string) $next, 8, '0', STR_PAD_LEFT);
    }

    private function simulateFailure(string $stage, string $message): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (! config('invoice.simulate_failures', false)) {
            return;
        }

        $rate = (float) config('invoice.failure_rates.' . $stage, 0);
        if ($rate <= 0) {
            return;
        }

        $sample = random_int(0, 10000) / 10000;
        if ($sample < $rate) {
            throw new RuntimeException($message);
        }
    }
}

