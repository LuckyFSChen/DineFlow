@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_12%_0%,#dbeafe_0%,transparent_38%),radial-gradient(circle_at_90%_0%,#dcfce7_0%,transparent_35%),linear-gradient(180deg,#f8fafc_0%,#eef2ff_45%,#ffffff_100%)] py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-backend-header
            title="發票中心"
            subtitle="開通精靈、非同步開票、異常補單與折讓作廢一站管理"
            align="center"
        />

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm backdrop-blur sm:p-5">
            <form method="GET" action="{{ route('merchant.invoices.index') }}" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">店家</span>
                    <select name="store_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary" @disabled($stores->isEmpty())>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected($selectedStore && $selectedStore->id === $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">切換店家</button>
            </form>
        </section>

        @if(! $selectedStore)
            <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600">
                尚未建立店家，請先到店家後台新增門市。
            </section>
        @else
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">待開票訂單</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($metrics['not_issued_orders']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">已開立未上傳</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($metrics['issued_but_not_uploaded']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">作廢/折讓失敗</p>
                    <p class="mt-2 text-2xl font-bold text-rose-600">{{ number_format($metrics['void_or_allowance_failed']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">逾時案件</p>
                    <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format($metrics['overdue_count']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">剩餘字軌</p>
                    <p class="mt-2 text-2xl font-bold {{ $metrics['track_low'] ? 'text-rose-600' : 'text-slate-900' }}">
                        {{ $metrics['track_remaining'] === null ? '-' : number_format($metrics['track_remaining']) }}
                    </p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">空白字軌未上傳</p>
                    <p class="mt-2 text-2xl font-bold {{ $metrics['blank_track_not_uploaded'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($metrics['blank_track_not_uploaded']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">開通狀態</p>
                    <p class="mt-2 text-lg font-bold text-slate-900">{{ $setting?->onboarding_status ?? 'not_started' }}</p>
                </article>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">店家開通精靈</h2>
                        <p class="mt-1 text-sm text-slate-600">把工商憑證、加值中心、字軌、店號、機號等流程整理成可操作欄位。</p>
                    </div>
                    <form method="POST" action="{{ route('merchant.invoices.test-issue') }}">
                        @csrf
                        <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                        <button type="submit" class="inline-flex items-center rounded-xl border border-brand-primary px-3 py-2 text-sm font-semibold text-brand-primary hover:bg-brand-primary hover:text-white">測試開立一張發票</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('merchant.invoices.wizard.update') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                    <input type="hidden" name="wizard_step" value="6">

                    <label class="space-y-2 md:col-span-2 xl:col-span-3">
                        <span class="text-sm font-medium text-slate-700">是否已具備開立電子發票資格</span>
                        <input type="hidden" name="eligible_for_invoice" value="0">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800">
                            <input type="checkbox" name="eligible_for_invoice" value="1" @checked((bool) old('eligible_for_invoice', $setting?->eligible_for_invoice)) class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                            我已確認店家具備電子發票開立資格
                        </label>
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">合作模式</span>
                        <select name="provider_mode" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                            <option value="">請選擇</option>
                            <option value="value_center" @selected(old('provider_mode', $setting?->provider_mode) === 'value_center')>加值中心</option>
                            <option value="turnkey" @selected(old('provider_mode', $setting?->provider_mode) === 'turnkey')>Turnkey</option>
                        </select>
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">合作廠商</span>
                        <input type="text" name="provider_name" value="{{ old('provider_name', $setting?->provider_name) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary" placeholder="例如 XX 加值中心">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">統一編號</span>
                        <input type="text" name="tax_id" value="{{ old('tax_id', $setting?->tax_id) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary" placeholder="8 位數字">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">公司名稱</span>
                        <input type="text" name="company_name" value="{{ old('company_name', $setting?->company_name) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">分店名稱</span>
                        <input type="text" name="branch_name" value="{{ old('branch_name', $setting?->branch_name) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-1 md:col-span-2 xl:col-span-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">地址</span>
                        <input type="text" name="company_address" value="{{ old('company_address', $setting?->company_address) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-1 md:col-span-2 xl:col-span-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">憑證資訊</span>
                        <textarea name="credential_notes" rows="3" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary" placeholder="可填工商憑證序號、API 帳密申請狀態、補件說明等">{{ old('credential_notes', $setting?->credential_notes) }}</textarea>
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">字軌前綴</span>
                        <input type="text" name="invoice_track_prefix" value="{{ old('invoice_track_prefix', $setting?->invoice_track_prefix) }}" class="w-full rounded-xl border-slate-300 text-sm uppercase focus:border-brand-primary focus:ring-brand-primary" placeholder="AB">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">字軌起號</span>
                        <input type="number" min="1" name="invoice_track_start" value="{{ old('invoice_track_start', $setting?->invoice_track_start) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">字軌迄號</span>
                        <input type="number" min="1" name="invoice_track_end" value="{{ old('invoice_track_end', $setting?->invoice_track_end) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">下一張號碼</span>
                        <input type="number" min="1" name="next_invoice_no" value="{{ old('next_invoice_no', $setting?->next_invoice_no) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">店號</span>
                        <input type="text" name="store_no" value="{{ old('store_no', $setting?->store_no) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">機號</span>
                        <input type="text" name="machine_no" value="{{ old('machine_no', $setting?->machine_no) }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-primary focus:ring-brand-primary">
                    </label>

                    <label class="space-y-2 md:col-span-2 xl:col-span-3">
                        <span class="text-sm font-medium text-slate-700">空白字軌上傳狀態</span>
                        <input type="hidden" name="blank_tracks_uploaded" value="0">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800">
                            <input type="checkbox" name="blank_tracks_uploaded" value="1" @checked((bool) old('blank_tracks_uploaded', $setting?->blank_tracks_uploaded_at !== null)) class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                            空白未使用字軌已完成上傳
                        </label>
                    </label>

                    <div class="md:col-span-2 xl:col-span-3">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">儲存開通精靈</button>
                        @if($setting?->last_tested_at)
                            <p class="mt-2 text-xs text-emerald-700">最近測試開立：{{ $setting->last_test_invoice_no }}（{{ $setting->last_tested_at->format('Y-m-d H:i') }}）</p>
                        @endif
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">異常中心：待開票訂單</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left text-slate-600">
                            <tr>
                                <th class="px-3 py-2">訂單</th>
                                <th class="px-3 py-2">金額</th>
                                <th class="px-3 py-2">開票狀態</th>
                                <th class="px-3 py-2">錯誤訊息</th>
                                <th class="px-3 py-2">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pendingOrders as $order)
                                <tr>
                                    <td class="px-3 py-2 font-semibold text-slate-900">#{{ $order->order_no ?: $order->id }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ number_format((int) $order->total) }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $order->invoice?->status ?? 'not_created' }}</td>
                                    <td class="px-3 py-2 text-rose-600">{{ $order->invoice?->last_error ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <form method="POST" action="{{ route('merchant.invoices.orders.retry-issue', ['order' => $order->id]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-brand-primary px-2.5 py-1 text-xs font-semibold text-brand-primary hover:bg-brand-primary hover:text-white">加入補開佇列</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-slate-500">目前沒有待處理開票訂單。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">異常中心：發票/作廢失敗清單</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left text-slate-600">
                            <tr>
                                <th class="px-3 py-2">發票號碼</th>
                                <th class="px-3 py-2">訂單</th>
                                <th class="px-3 py-2">狀態</th>
                                <th class="px-3 py-2">上傳</th>
                                <th class="px-3 py-2">錯誤訊息</th>
                                <th class="px-3 py-2">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="px-3 py-2 font-semibold text-slate-900">{{ $invoice->invoice_number ?: '-' }}</td>
                                    <td class="px-3 py-2 text-slate-700">#{{ $invoice->order?->order_no ?: $invoice->order_id }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $invoice->status }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $invoice->upload_status }}</td>
                                    <td class="px-3 py-2 text-rose-600">{{ $invoice->last_error ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('merchant.invoices.retry-issue', ['invoice' => $invoice->id]) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-brand-primary px-2.5 py-1 text-xs font-semibold text-brand-primary hover:bg-brand-primary hover:text-white">補開</button>
                                            </form>
                                            <form method="POST" action="{{ route('merchant.invoices.retry-upload', ['invoice' => $invoice->id]) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-amber-400 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100">補傳</button>
                                            </form>
                                            <form method="POST" action="{{ route('merchant.invoices.retry-void', ['invoice' => $invoice->id]) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-rose-400 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">作廢重試</button>
                                            </form>
                                        </div>
                                        @if(in_array($invoice->status, ['issued', 'allowance_issued'], true))
                                            <form method="POST" action="{{ route('merchant.invoices.allowances.store', ['invoice' => $invoice->id]) }}" class="mt-2 flex flex-wrap gap-2">
                                                @csrf
                                                <input type="number" min="1" max="{{ (int) $invoice->amount }}" name="amount" class="w-24 rounded-lg border-slate-300 px-2 py-1 text-xs focus:border-brand-primary focus:ring-brand-primary" placeholder="折讓額">
                                                <input type="text" name="reason" class="w-44 rounded-lg border-slate-300 px-2 py-1 text-xs focus:border-brand-primary focus:ring-brand-primary" placeholder="折讓原因">
                                                <button type="submit" class="rounded-lg border border-indigo-400 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">開折讓單</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-slate-500">目前沒有失敗發票。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">異常中心：折讓失敗清單</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left text-slate-600">
                            <tr>
                                <th class="px-3 py-2">折讓單</th>
                                <th class="px-3 py-2">訂單</th>
                                <th class="px-3 py-2">發票</th>
                                <th class="px-3 py-2">金額</th>
                                <th class="px-3 py-2">錯誤訊息</th>
                                <th class="px-3 py-2">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($allowances as $allowance)
                                <tr>
                                    <td class="px-3 py-2 font-semibold text-slate-900">{{ $allowance->allowance_number ?: '-' }}</td>
                                    <td class="px-3 py-2 text-slate-700">#{{ $allowance->order?->order_no ?: $allowance->order_id }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $allowance->invoice?->invoice_number ?: '-' }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ number_format((int) $allowance->amount) }}</td>
                                    <td class="px-3 py-2 text-rose-600">{{ $allowance->last_error ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <form method="POST" action="{{ route('merchant.invoices.allowances.retry', ['allowance' => $allowance->id]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-indigo-400 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">折讓重試</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-slate-500">目前沒有折讓失敗案件。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
