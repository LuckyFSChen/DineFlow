<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Jobs\IssueOrderInvoiceJob;
use App\Jobs\IssueStoreInvoiceAllowanceJob;
use App\Jobs\UploadStoreInvoiceJob;
use App\Jobs\VoidStoreInvoiceJob;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreInvoice;
use App\Models\StoreInvoiceAllowance;
use App\Models\StoreInvoiceSetting;
use App\Services\Invoice\InvoiceGatewayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceCenterController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stores = Store::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'currency']);

        $selectedStore = null;

        if ($stores->isNotEmpty()) {
            $selectedStore = $stores->count() === 1 ? $stores->first() : null;

            if ($request->filled('store_id')) {
                $candidateStore = $stores->firstWhere('id', (int) $request->input('store_id'));
                if ($candidateStore) {
                    $selectedStore = $candidateStore;
                }
            }

            if (! $selectedStore) {
                $selectedStore = $stores->first();
            }
        }

        $setting = null;
        $metrics = [
            'not_issued_orders' => 0,
            'issued_but_not_uploaded' => 0,
            'void_or_allowance_failed' => 0,
            'overdue_count' => 0,
            'track_remaining' => null,
            'track_low' => false,
            'blank_track_not_uploaded' => 0,
        ];
        $pendingOrders = collect();
        $invoices = collect();
        $allowances = collect();

        if ($selectedStore) {
            $setting = StoreInvoiceSetting::query()->firstOrCreate(
                ['store_id' => $selectedStore->id],
                [
                    'onboarding_status' => 'not_started',
                    'wizard_step' => 1,
                    'eligible_for_invoice' => false,
                ]
            );

            $metrics = $this->buildMetrics($selectedStore, $setting);

            $pendingOrders = Order::query()
                ->where('store_id', $selectedStore->id)
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancel', 'cancelled', 'canceled'])
                ->where(function ($query): void {
                    $query->whereDoesntHave('invoice')
                        ->orWhereHas('invoice', function ($invoiceQuery): void {
                            $invoiceQuery->whereIn('status', [
                                StoreInvoice::STATUS_PENDING,
                                StoreInvoice::STATUS_PROCESSING,
                                StoreInvoice::STATUS_FAILED,
                            ]);
                        });
                })
                ->with(['invoice:id,order_id,status,last_error'])
                ->latest('id')
                ->limit(25)
                ->get(['id', 'store_id', 'order_no', 'status', 'payment_status', 'total', 'created_at']);

            $invoices = StoreInvoice::query()
                ->where('store_id', $selectedStore->id)
                ->where(function ($query): void {
                    $query->whereIn('status', [
                        StoreInvoice::STATUS_FAILED,
                        StoreInvoice::STATUS_VOID_FAILED,
                        StoreInvoice::STATUS_ALLOWANCE_FAILED,
                    ])->orWhere('upload_status', 'failed');
                })
                ->with('order:id,order_no')
                ->latest('id')
                ->limit(30)
                ->get();

            $allowances = StoreInvoiceAllowance::query()
                ->where('store_id', $selectedStore->id)
                ->where('status', 'failed')
                ->with(['invoice:id,invoice_number', 'order:id,order_no'])
                ->latest('id')
                ->limit(20)
                ->get();
        }

        return view('merchant.invoices.index', [
            'stores' => $stores,
            'selectedStore' => $selectedStore,
            'setting' => $setting,
            'metrics' => $metrics,
            'pendingOrders' => $pendingOrders,
            'invoices' => $invoices,
            'allowances' => $allowances,
        ]);
    }

    public function updateWizard(Request $request): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, (int) $request->input('store_id'));

        $validated = $request->validate([
            'wizard_step' => ['nullable', 'integer', 'min:1', 'max:6'],
            'eligible_for_invoice' => ['nullable', 'boolean'],
            'provider_mode' => ['nullable', 'in:value_center,turnkey'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:16'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'credential_notes' => ['nullable', 'string'],
            'invoice_track_prefix' => ['nullable', 'string', 'max:8'],
            'invoice_track_start' => ['nullable', 'integer', 'min:1'],
            'invoice_track_end' => ['nullable', 'integer', 'min:1'],
            'next_invoice_no' => ['nullable', 'integer', 'min:1'],
            'store_no' => ['nullable', 'string', 'max:16'],
            'machine_no' => ['nullable', 'string', 'max:16'],
            'blank_tracks_uploaded' => ['nullable', 'boolean'],
        ]);

        $setting = StoreInvoiceSetting::query()->firstOrCreate(['store_id' => $store->id]);

        $eligible = (bool) ($validated['eligible_for_invoice'] ?? false);

        $requiredFieldMap = [
            'provider_mode' => '請選擇合作串接方式（加值中心或 Turnkey）。',
            'tax_id' => '請填寫統一編號。',
            'company_name' => '請填寫公司名稱。',
            'branch_name' => '請填寫分店名稱。',
            'company_address' => '請填寫公司/分店地址。',
            'invoice_track_prefix' => '請設定字軌前綴。',
            'invoice_track_start' => '請設定字軌起號。',
            'invoice_track_end' => '請設定字軌迄號。',
            'next_invoice_no' => '請設定下一張可用發票號碼。',
            'store_no' => '請填寫店號。',
            'machine_no' => '請填寫機號。',
        ];

        $errors = [];
        if ($eligible) {
            foreach ($requiredFieldMap as $field => $message) {
                if (! filled($validated[$field] ?? null)) {
                    $errors[$field] = $message;
                }
            }

            $trackStart = (int) ($validated['invoice_track_start'] ?? 0);
            $trackEnd = (int) ($validated['invoice_track_end'] ?? 0);
            $nextNo = (int) ($validated['next_invoice_no'] ?? 0);

            if ($trackStart > 0 && $trackEnd > 0 && $trackStart > $trackEnd) {
                $errors['invoice_track_end'] = '字軌迄號必須大於或等於起號。';
            }

            if ($nextNo > 0 && $trackStart > 0 && $nextNo < $trackStart) {
                $errors['next_invoice_no'] = '下一張發票號碼不可小於字軌起號。';
            }

            if ($nextNo > 0 && $trackEnd > 0 && $nextNo > $trackEnd) {
                $errors['next_invoice_no'] = '下一張發票號碼不可超過字軌迄號。';
            }
        }

        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        $setting->fill([
            'wizard_step' => max((int) ($validated['wizard_step'] ?? $setting->wizard_step ?? 1), 1),
            'eligible_for_invoice' => $eligible,
            'provider_mode' => $validated['provider_mode'] ?? null,
            'provider_name' => $validated['provider_name'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'branch_name' => $validated['branch_name'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'credential_notes' => $validated['credential_notes'] ?? null,
            'invoice_track_prefix' => strtoupper((string) ($validated['invoice_track_prefix'] ?? '')) ?: null,
            'invoice_track_start' => $validated['invoice_track_start'] ?? null,
            'invoice_track_end' => $validated['invoice_track_end'] ?? null,
            'next_invoice_no' => $validated['next_invoice_no'] ?? null,
            'store_no' => $validated['store_no'] ?? null,
            'machine_no' => $validated['machine_no'] ?? null,
            'blank_tracks_uploaded_at' => $request->boolean('blank_tracks_uploaded') ? now() : null,
        ]);

        $setting->onboarding_status = $this->resolveOnboardingStatus($setting);
        $setting->save();

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '發票開通精靈已更新。');
    }

    public function runTestIssue(Request $request, InvoiceGatewayService $gateway): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, (int) $request->input('store_id'));

        $setting = StoreInvoiceSetting::query()->firstOrCreate(['store_id' => $store->id]);

        if (! $setting->isReadyForIssue()) {
            return back()->withErrors([
                'test_issue' => '尚未完成開通精靈必填欄位，無法測試開立。',
            ]);
        }

        $result = $gateway->issueTestInvoice($setting);

        $setting->fill([
            'last_tested_at' => now(),
            'last_test_invoice_no' => (string) ($result['invoice_number'] ?? ''),
            'wizard_step' => max((int) $setting->wizard_step, 6),
            'onboarding_status' => 'live',
        ])->save();

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '測試開立成功，發票號碼：' . $setting->last_test_invoice_no);
    }

    public function retryIssue(Request $request, StoreInvoice $invoice): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, $invoice->store_id);
        $this->ensureInvoiceBelongsToStore($invoice, $store);

        IssueOrderInvoiceJob::dispatch($invoice->order_id);

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '已加入補開佇列。');
    }

    public function retryOrderIssue(Request $request, Order $order): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, $order->store_id);

        if ($order->store_id !== $store->id) {
            abort(404);
        }

        IssueOrderInvoiceJob::dispatch($order->id);

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '已加入補開佇列。');
    }

    public function retryUpload(Request $request, StoreInvoice $invoice): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, $invoice->store_id);
        $this->ensureInvoiceBelongsToStore($invoice, $store);

        UploadStoreInvoiceJob::dispatch($invoice->id);

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '已加入補傳佇列。');
    }

    public function retryVoid(Request $request, StoreInvoice $invoice): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, $invoice->store_id);
        $this->ensureInvoiceBelongsToStore($invoice, $store);

        VoidStoreInvoiceJob::dispatch($invoice->id);

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '已加入作廢重試佇列。');
    }

    public function createAllowance(Request $request, StoreInvoice $invoice): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, $invoice->store_id);
        $this->ensureInvoiceBelongsToStore($invoice, $store);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (int) $validated['amount'];
        if ($amount > (int) $invoice->amount) {
            return back()->withErrors([
                'amount' => '折讓金額不可大於原發票金額。',
            ]);
        }

        $allowance = StoreInvoiceAllowance::query()->create([
            'store_id' => $store->id,
            'order_id' => $invoice->order_id,
            'store_invoice_id' => $invoice->id,
            'status' => 'pending',
            'amount' => $amount,
            'reason' => trim((string) ($validated['reason'] ?? '')) ?: null,
            'upload_status' => 'pending',
            'legal_deadline_at' => now()->addHours(max((int) config('invoice.allowance_upload_deadline_hours', 48), 1)),
        ]);

        $invoice->fill([
            'status' => StoreInvoice::STATUS_ALLOWANCE_PENDING,
        ])->save();

        IssueStoreInvoiceAllowanceJob::dispatch($allowance->id);

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '折讓單已送出，系統將非同步開立。');
    }

    public function retryAllowance(Request $request, StoreInvoiceAllowance $allowance): RedirectResponse
    {
        $store = $this->resolveMerchantStore($request, $allowance->store_id);

        if ($allowance->store_id !== $store->id) {
            abort(404);
        }

        IssueStoreInvoiceAllowanceJob::dispatch($allowance->id);

        return redirect()
            ->route('merchant.invoices.index', ['store_id' => $store->id])
            ->with('status', '已加入折讓補開佇列。');
    }

    private function resolveMerchantStore(Request $request, int $storeId): Store
    {
        $store = Store::query()
            ->where('id', $storeId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $store) {
            abort(404);
        }

        return $store;
    }

    private function ensureInvoiceBelongsToStore(StoreInvoice $invoice, Store $store): void
    {
        if ($invoice->store_id !== $store->id) {
            abort(404);
        }
    }

    private function resolveOnboardingStatus(StoreInvoiceSetting $setting): string
    {
        if (! $setting->eligible_for_invoice) {
            return 'not_started';
        }

        if ($setting->isReadyForIssue()) {
            return $setting->last_tested_at ? 'live' : 'ready';
        }

        return 'in_progress';
    }

    private function buildMetrics(Store $store, StoreInvoiceSetting $setting): array
    {
        $notIssuedOrders = Order::query()
            ->where('store_id', $store->id)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancel', 'cancelled', 'canceled'])
            ->where(function ($query): void {
                $query->whereDoesntHave('invoice')
                    ->orWhereHas('invoice', function ($invoiceQuery): void {
                        $invoiceQuery->whereNotIn('status', [
                            StoreInvoice::STATUS_ISSUED,
                            StoreInvoice::STATUS_VOIDED,
                            StoreInvoice::STATUS_ALLOWANCE_ISSUED,
                        ]);
                    });
            })
            ->count();

        $issuedButNotUploaded = StoreInvoice::query()
            ->where('store_id', $store->id)
            ->where('status', StoreInvoice::STATUS_ISSUED)
            ->where('upload_status', '!=', 'uploaded')
            ->count();

        $voidOrAllowanceFailed = StoreInvoice::query()
            ->where('store_id', $store->id)
            ->whereIn('status', [StoreInvoice::STATUS_VOID_FAILED, StoreInvoice::STATUS_ALLOWANCE_FAILED])
            ->count();

        $voidOrAllowanceFailed += StoreInvoiceAllowance::query()
            ->where('store_id', $store->id)
            ->where('status', 'failed')
            ->count();

        $overdueInvoices = StoreInvoice::query()
            ->where('store_id', $store->id)
            ->whereNotNull('legal_deadline_at')
            ->where('legal_deadline_at', '<', now())
            ->where(function ($query): void {
                $query->where('upload_status', '!=', 'uploaded')
                    ->orWhereIn('status', [
                        StoreInvoice::STATUS_PENDING,
                        StoreInvoice::STATUS_PROCESSING,
                        StoreInvoice::STATUS_FAILED,
                        StoreInvoice::STATUS_VOID_FAILED,
                        StoreInvoice::STATUS_ALLOWANCE_FAILED,
                    ]);
            })
            ->count();

        $overdueAllowances = StoreInvoiceAllowance::query()
            ->where('store_id', $store->id)
            ->whereNotNull('legal_deadline_at')
            ->where('legal_deadline_at', '<', now())
            ->where(function ($query): void {
                $query->where('upload_status', '!=', 'uploaded')
                    ->orWhere('status', 'failed');
            })
            ->count();

        $trackRemaining = $setting->remainingTrackCount();

        return [
            'not_issued_orders' => $notIssuedOrders,
            'issued_but_not_uploaded' => $issuedButNotUploaded,
            'void_or_allowance_failed' => $voidOrAllowanceFailed,
            'overdue_count' => $overdueInvoices + $overdueAllowances,
            'track_remaining' => $trackRemaining,
            'track_low' => $trackRemaining !== null && $trackRemaining <= 20,
            'blank_track_not_uploaded' => $setting->blank_tracks_uploaded_at ? 0 : 1,
        ];
    }
}

