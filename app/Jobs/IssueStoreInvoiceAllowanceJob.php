<?php

namespace App\Jobs;

use App\Models\StoreInvoice;
use App\Models\StoreInvoiceAllowance;
use App\Services\Invoice\InvoiceGatewayService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IssueStoreInvoiceAllowanceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly int $allowanceId)
    {
    }

    public function handle(InvoiceGatewayService $gateway): void
    {
        $allowance = StoreInvoiceAllowance::query()
            ->with(['invoice', 'store.invoiceSetting'])
            ->find($this->allowanceId);

        if (! $allowance) {
            return;
        }

        if ($allowance->status === 'issued') {
            return;
        }

        $setting = $allowance->store?->invoiceSetting;

        if (! $setting || ! $setting->isReadyForIssue()) {
            $allowance->fill([
                'status' => 'failed',
                'last_error' => '店家尚未完成電子發票開通精靈。',
            ])->save();

            optional($allowance->invoice)->fill([
                'status' => StoreInvoice::STATUS_ALLOWANCE_FAILED,
                'last_error' => '折讓失敗：店家尚未完成電子發票開通精靈。',
            ])->save();

            return;
        }

        $allowance->fill([
            'attempts' => (int) $allowance->attempts + 1,
            'status' => 'pending',
            'upload_status' => 'uploading',
        ])->save();

        try {
            $result = $gateway->issueAllowance($allowance, $setting);

            $allowance->fill([
                'status' => 'issued',
                'allowance_number' => (string) ($result['allowance_number'] ?? ''),
                'issued_at' => Carbon::now(),
                'upload_status' => 'uploaded',
                'uploaded_at' => Carbon::now(),
                'legal_deadline_at' => Carbon::now()->addHours(max((int) config('invoice.allowance_upload_deadline_hours', 48), 1)),
                'provider_payload' => $result['provider_payload'] ?? null,
                'last_error' => null,
            ])->save();

            optional($allowance->invoice)->fill([
                'status' => StoreInvoice::STATUS_ALLOWANCE_ISSUED,
                'last_error' => null,
            ])->save();
        } catch (\Throwable $e) {
            $allowance->fill([
                'status' => 'failed',
                'upload_status' => 'failed',
                'last_error' => $e->getMessage(),
            ])->save();

            optional($allowance->invoice)->fill([
                'status' => StoreInvoice::STATUS_ALLOWANCE_FAILED,
                'last_error' => '折讓失敗：' . $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $allowance = StoreInvoiceAllowance::query()->with('invoice')->find($this->allowanceId);
        if (! $allowance) {
            return;
        }

        $allowance->fill([
            'status' => 'failed',
            'upload_status' => 'failed',
            'last_error' => $e->getMessage(),
        ])->save();

        optional($allowance->invoice)->fill([
            'status' => StoreInvoice::STATUS_ALLOWANCE_FAILED,
            'last_error' => '折讓失敗：' . $e->getMessage(),
        ])->save();
    }
}

