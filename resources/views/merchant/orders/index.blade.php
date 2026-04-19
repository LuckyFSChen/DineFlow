@extends('layouts.app')

@section('content')
@php
    $selectedStore = $selectedStoreId ? $stores->firstWhere('id', $selectedStoreId) : null;
    $currencyCode = strtolower((string) ($selectedStore->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };

    $sortBy = (string) ($sort['by'] ?? 'created_at');
    $sortDir = (string) ($sort['dir'] ?? 'desc');

    $statusTone = function (?string $status): string {
        return match (strtolower((string) $status)) {
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'accepted', 'confirmed', 'received' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'preparing', 'processing', 'cooking', 'in_progress' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'ready', 'ready_for_pickup' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'complete', 'completed' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    };
    $statusLabels = [
        'pending' => __('merchant.order_status_pending'),
        'accepted' => __('merchant.order_status_accepted'),
        'confirmed' => __('merchant.order_status_confirmed'),
        'received' => __('merchant.order_status_received'),
        'preparing' => __('merchant.order_status_preparing'),
        'processing' => __('merchant.order_status_processing'),
        'cooking' => __('merchant.order_status_cooking'),
        'in_progress' => __('merchant.order_status_in_progress'),
        'complete' => __('merchant.order_status_complete'),
        'completed' => __('merchant.order_status_completed'),
        'ready' => __('merchant.order_status_ready'),
        'ready_for_pickup' => __('merchant.order_status_ready_for_pickup'),
        'cancelled' => __('merchant.order_status_cancelled'),
    ];
    $statusLabel = function (?string $status) use ($statusLabels): string {
        $normalized = strtolower(trim((string) $status));
        if ($normalized === '') {
            return '-';
        }

        return $statusLabels[$normalized] ?? str_replace('_', ' ', $normalized);
    };
    $sortIndicator = function (string $column) use ($sortBy, $sortDir): string {
        if ($sortBy !== $column) {
            return '↕';
        }

        return $sortDir === 'asc' ? '↑' : '↓';
    };
    $buildOrderSearchText = function ($order) use ($statusLabel): string {
        $parts = [
            (string) ($order->order_no ?: $order->id),
            (string) ($order->store->name ?? ''),
            (string) ($order->customer_name ?? ''),
            (string) ($order->customer_phone ?? ''),
            in_array(strtolower((string) $order->order_type), ['takeout', 'take_out'], true)
                ? __('merchant.orders_type_takeout')
                : __('merchant.orders_type_dine_in'),
            $statusLabel($order->status),
            $order->payment_status === 'paid'
                ? __('merchant.orders_payment_paid')
                : __('merchant.orders_payment_unpaid'),
            (string) $order->items->pluck('product_name')->implode(' '),
        ];

        return strtolower(trim((string) preg_replace('/\s+/', ' ', implode(' ', array_filter($parts)))));
    };

    $activeFilters = collect([
        'store_id' => $selectedStoreId,
        'start_date' => $filters['start_date'] ?? '',
        'end_date' => $filters['end_date'] ?? '',
        'status' => $filters['status'] ?? '',
        'payment_status' => $filters['payment_status'] ?? '',
        'order_type' => $filters['order_type'] ?? '',
    ])->filter(fn ($value) => $value !== null && $value !== '')->count();
@endphp

<div class="min-h-screen bg-[radial-gradient(circle_at_20%_10%,#dbeafe_0%,transparent_45%),radial-gradient(circle_at_85%_0%,#dcfce7_0%,transparent_40%),linear-gradient(180deg,#f8fafc_0%,#f1f5f9_45%,#ffffff_100%)] py-8 sm:py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-backend-header
            :title="__('merchant.orders_history_title')"
            :subtitle="__('merchant.orders_history_desc')"
            align="center"
        />

        <section class="mt-6 rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm backdrop-blur sm:p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-slate-800">{{ __('merchant.apply_filter') }}</h2>
                    @if($activeFilters > 0)
                        <span class="inline-flex items-center rounded-full bg-brand-primary/10 px-2 py-0.5 text-xs font-semibold text-brand-primary">
                            {{ __('merchant.orders_filter_active_count', ['count' => $activeFilters]) }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('merchant.orders.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                    {{ __('merchant.reset_all') }}
                </a>
            </div>

            <form id="ordersFilterForm" method="GET" action="{{ route('merchant.orders.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

                @if($stores->count() > 1)
                    <label class="space-y-1">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.store') }}</span>
                        <select name="store_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary">
                            <option value="">{{ __('merchant.all_stores') }}</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected((int) $selectedStoreId === (int) $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="space-y-1">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.start_date') }}</span>
                    <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary">
                </label>

                <label class="space-y-1">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.end_date') }}</span>
                    <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary">
                </label>

                <label class="space-y-1">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.orders_filter_status') }}</span>
                    <select name="status" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary">
                        <option value="">{{ __('merchant.orders_filter_all_statuses') }}</option>
                        @foreach($statusLabels as $status => $label)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.orders_filter_payment') }}</span>
                    <select name="payment_status" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary">
                        <option value="">{{ __('merchant.orders_filter_all_payments') }}</option>
                        <option value="paid" @selected($filters['payment_status'] === 'paid')>{{ __('merchant.orders_payment_paid') }}</option>
                        <option value="unpaid" @selected($filters['payment_status'] === 'unpaid')>{{ __('merchant.orders_payment_unpaid') }}</option>
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.orders_filter_type') }}</span>
                    <select name="order_type" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary">
                        <option value="">{{ __('merchant.orders_filter_all_types') }}</option>
                        <option value="dine_in" @selected($filters['order_type'] === 'dine_in' || $filters['order_type'] === 'dinein')>{{ __('merchant.orders_type_dine_in') }}</option>
                        <option value="takeout" @selected($filters['order_type'] === 'takeout' || $filters['order_type'] === 'take_out')>{{ __('merchant.orders_type_takeout') }}</option>
                    </select>
                </label>

                <label class="space-y-1 md:col-span-2 xl:col-span-5">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.orders_filter_keyword') }}</span>
                    <input
                        id="ordersKeywordInput"
                        type="text"
                        name="table_keyword"
                        value=""
                        placeholder="{{ __('merchant.orders_filter_keyword_placeholder') }}"
                        autocomplete="off"
                        class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-brand-primary focus:ring-brand-primary">
                </label>

                <div class="md:col-span-2 xl:col-span-1 flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                        {{ __('merchant.apply_filter') }}
                    </button>
                </div>
            </form>
        </section>

        <section id="ordersSummaryCards" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.total_orders') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalOrders) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.total_revenue') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $currencySymbol }} {{ number_format($totalAmount) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.orders_paid_count') }}</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($paidOrders) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.orders_avg_ticket') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $currencySymbol }} {{ number_format($averageOrderAmount) }}</p>
            </article>
        </section>

        <section id="ordersResultSection" class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full text-sm" data-datatable data-dt-paging="false" data-dt-info="false" data-dt-searching="false" data-dt-ordering="false">
                    <thead class="bg-slate-100/90 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('merchant.orders_order_no') }}</th>
                            <th class="px-4 py-3 text-left">
                                <button type="button" class="inline-flex items-center gap-1 font-semibold hover:text-slate-900 sortable-header" data-sort-by="store_name">
                                    {{ __('merchant.store') }} <span>{{ $sortIndicator('store_name') }}</span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button type="button" class="inline-flex items-center gap-1 font-semibold hover:text-slate-900 sortable-header" data-sort-by="customer_name">
                                    {{ __('merchant.orders_customer') }} <span>{{ $sortIndicator('customer_name') }}</span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button type="button" class="inline-flex items-center gap-1 font-semibold hover:text-slate-900 sortable-header" data-sort-by="order_type">
                                    {{ __('merchant.orders_type') }} <span>{{ $sortIndicator('order_type') }}</span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button type="button" class="inline-flex items-center gap-1 font-semibold hover:text-slate-900 sortable-header" data-sort-by="status">
                                    {{ __('merchant.orders_status') }} <span>{{ $sortIndicator('status') }}</span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right">
                                <button type="button" class="inline-flex items-center gap-1 font-semibold hover:text-slate-900 sortable-header" data-sort-by="total">
                                    {{ __('merchant.orders_amount') }} <span>{{ $sortIndicator('total') }}</span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button type="button" class="inline-flex items-center gap-1 font-semibold hover:text-slate-900 sortable-header" data-sort-by="created_at">
                                    {{ __('merchant.orders_created_at') }} <span>{{ $sortIndicator('created_at') }}</span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">{{ __('merchant.orders_items_toggle') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($orders as $order)
                            <tr
                                class="align-top hover:bg-slate-50/80"
                                data-order-entry
                                data-order-key="{{ $order->id }}"
                                data-order-search="{{ $buildOrderSearchText($order) }}">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">#{{ $order->order_no ?: $order->id }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ __('merchant.orders_items_count', ['count' => $order->items->count()]) }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->store->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div>{{ $order->customer_name ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->customer_phone ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ in_array(strtolower((string) $order->order_type), ['takeout','take_out'], true) ? __('merchant.orders_type_takeout') : __('merchant.orders_type_dine_in') }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="inline-flex w-fit rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $statusTone($order->status) }}">{{ $statusLabel($order->status) }}</span>
                                        <span class="inline-flex w-fit rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ ($order->payment_status === 'paid') ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                            {{ $order->payment_status === 'paid' ? __('merchant.orders_payment_paid') : __('merchant.orders_payment_unpaid') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $currencySymbol }} {{ number_format((int) $order->total) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    <button type="button" class="order-items-toggle rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-target="order-items-{{ $order->id }}" data-expanded-label="{{ __('merchant.orders_items_collapse') }}" data-collapsed-label="{{ __('merchant.orders_items_expand') }}">
                                        {{ __('merchant.orders_items_expand') }}
                                    </button>
                                    <div id="order-items-{{ $order->id }}" class="mt-2 hidden rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                                        @if($order->items->isNotEmpty())
                                            <div class="space-y-1.5 text-sm text-slate-700">
                                                @foreach($order->items as $item)
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span>{{ $item->product_name }}</span>
                                                        <span class="text-xs text-slate-500">
                                                            x{{ $item->qty }} · {{ $currencySymbol }} {{ number_format((int) $item->subtotal) }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-sm text-slate-500">{{ __('merchant.orders_empty') }}</div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr data-orders-empty-server-desktop data-datatable-row="ignore">
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500">{{ __('merchant.orders_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="ordersDesktopNoMatch" class="hidden px-4 py-10 text-center text-slate-500">{{ __('merchant.orders_empty') }}</div>
            </div>

            <div class="space-y-3 p-3 lg:hidden">
                @forelse($orders as $order)
                    <article class="rounded-xl border border-slate-200 p-3" data-order-entry data-order-search="{{ $buildOrderSearchText($order) }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-bold text-slate-900">#{{ $order->order_no ?: $order->id }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ optional($order->created_at)->format('Y-m-d H:i') }}</p>
                            </div>
                            <p class="text-sm font-bold text-slate-900">{{ $currencySymbol }} {{ number_format((int) $order->total) }}</p>
                        </div>

                        <div class="mt-2 grid gap-1 text-sm text-slate-700">
                            <p>{{ $order->store->name ?? '-' }}</p>
                            <p>{{ $order->customer_name ?: '-' }} | {{ $order->customer_phone ?: '-' }}</p>
                            <p>{{ in_array(strtolower((string) $order->order_type), ['takeout','take_out'], true) ? __('merchant.orders_type_takeout') : __('merchant.orders_type_dine_in') }} | {{ __('merchant.orders_items_count', ['count' => $order->items->count()]) }}</p>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $statusTone($order->status) }}">{{ $statusLabel($order->status) }}</span>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ ($order->payment_status === 'paid') ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                {{ $order->payment_status === 'paid' ? __('merchant.orders_payment_paid') : __('merchant.orders_payment_unpaid') }}
                            </span>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="order-items-toggle rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-target="order-items-mobile-{{ $order->id }}" data-expanded-label="{{ __('merchant.orders_items_collapse') }}" data-collapsed-label="{{ __('merchant.orders_items_expand') }}">
                                {{ __('merchant.orders_items_expand') }}
                            </button>
                            <div id="order-items-mobile-{{ $order->id }}" class="mt-2 hidden rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                                @if($order->items->isNotEmpty())
                                    <div class="space-y-1.5 text-sm text-slate-700">
                                        @foreach($order->items as $item)
                                            <div class="flex items-center justify-between gap-2">
                                                <span>{{ $item->product_name }}</span>
                                                <span class="text-xs text-slate-500">
                                                    x{{ $item->qty }} · {{ $currencySymbol }} {{ number_format((int) $item->subtotal) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-sm text-slate-500">{{ __('merchant.orders_empty') }}</div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500" data-orders-empty-server-mobile>
                        {{ __('merchant.orders_empty') }}
                    </div>
                @endforelse
                <div id="ordersMobileNoMatch" class="hidden rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                    {{ __('merchant.orders_empty') }}
                </div>
            </div>
        </section>

        <div id="ordersPagination" class="mt-4">
            {{ $orders->links() }}
        </div>
        <div id="ordersKeywordPagination" class="mt-4 hidden"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ordersFilterForm');
    if (!form) {
        return;
    }

    const sortByInput = form.querySelector('input[name="sort_by"]');
    const sortDirInput = form.querySelector('input[name="sort_dir"]');
    const keywordInput = document.getElementById('ordersKeywordInput');

    const bindOrderItemToggles = () => {
        document.querySelectorAll('.order-items-toggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const panel = targetId ? document.getElementById(targetId) : null;
                if (!panel) {
                    return;
                }

                panel.classList.toggle('hidden');
                const expanded = !panel.classList.contains('hidden');
                btn.textContent = expanded
                    ? (btn.getAttribute('data-expanded-label') || '')
                    : (btn.getAttribute('data-collapsed-label') || '');
            });
        });
    };

    let allowNativeSubmit = false;
    let activeRequestController = null;
    let keywordPage = 1;
    const keywordPageSize = 10;

    const applyKeywordFilter = (options = {}) => {
        const resetPage = options.resetPage === true;
        const keyword = keywordInput ? keywordInput.value.trim().toLowerCase() : '';
        const hasKeyword = keyword.length > 0;

        const desktopEntries = Array.from(document.querySelectorAll('tr[data-order-entry]'));
        const mobileEntries = Array.from(document.querySelectorAll('article[data-order-entry]'));
        const desktopNoMatch = document.getElementById('ordersDesktopNoMatch');
        const mobileNoMatch = document.getElementById('ordersMobileNoMatch');
        const keywordPagination = document.getElementById('ordersKeywordPagination');

        if (resetPage) {
            keywordPage = 1;
        }

        const desktopMatched = [];
        const mobileMatched = [];

        desktopEntries.forEach((row) => {
            const matched = !hasKeyword || (row.dataset.orderSearch || '').includes(keyword);
            if (!matched) {
                row.classList.add('hidden');
                row.querySelectorAll('.order-items-toggle').forEach((toggleButton) => {
                    const targetId = toggleButton.getAttribute('data-target');
                    const detail = targetId ? document.getElementById(targetId) : null;
                    if (detail) {
                        detail.classList.add('hidden');
                    }
                    toggleButton.textContent = toggleButton.getAttribute('data-collapsed-label') || '';
                });
                return;
            }

            desktopMatched.push(row);
        });

        mobileEntries.forEach((card) => {
            const matched = !hasKeyword || (card.dataset.orderSearch || '').includes(keyword);
            if (!matched) {
                card.classList.add('hidden');
                return;
            }
            mobileMatched.push(card);
        });

        let desktopVisibleCount = desktopMatched.length;
        let mobileVisibleCount = mobileMatched.length;

        if (hasKeyword) {
            const totalItems = Math.max(desktopMatched.length, mobileMatched.length);
            const totalPages = Math.max(1, Math.ceil(totalItems / keywordPageSize));
            keywordPage = Math.min(Math.max(1, keywordPage), totalPages);
            const startIndex = (keywordPage - 1) * keywordPageSize;
            const endIndex = startIndex + keywordPageSize;

            desktopVisibleCount = 0;
            desktopMatched.forEach((row, index) => {
                const visible = index >= startIndex && index < endIndex;
                row.classList.toggle('hidden', !visible);
                if (visible) {
                    desktopVisibleCount += 1;
                }
            });

            mobileVisibleCount = 0;
            mobileMatched.forEach((card, index) => {
                const visible = index >= startIndex && index < endIndex;
                card.classList.toggle('hidden', !visible);
                if (visible) {
                    mobileVisibleCount += 1;
                }
            });

            if (keywordPagination) {
                if (totalPages > 1) {
                    keywordPagination.classList.remove('hidden');
                    keywordPagination.innerHTML = `
                        <div class="flex items-center justify-end gap-2 text-sm">
                            <button type="button" data-keyword-page="${Math.max(1, keywordPage - 1)}" class="rounded border border-slate-300 px-3 py-1 ${keywordPage === 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${keywordPage === 1 ? 'disabled' : ''}>上一頁</button>
                            <span class="text-slate-600">第 ${keywordPage} / ${totalPages} 頁</span>
                            <button type="button" data-keyword-page="${Math.min(totalPages, keywordPage + 1)}" class="rounded border border-slate-300 px-3 py-1 ${keywordPage === totalPages ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${keywordPage === totalPages ? 'disabled' : ''}>下一頁</button>
                        </div>
                    `;

                    keywordPagination.querySelectorAll('button[data-keyword-page]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const nextPage = Number(button.getAttribute('data-keyword-page') || 1);
                            keywordPage = Number.isFinite(nextPage) ? nextPage : 1;
                            applyKeywordFilter();
                        });
                    });
                } else {
                    keywordPagination.classList.add('hidden');
                    keywordPagination.innerHTML = '';
                }
            }
        } else if (keywordPagination) {
            keywordPagination.classList.add('hidden');
            keywordPagination.innerHTML = '';
        }

        if (desktopNoMatch) {
            desktopNoMatch.classList.toggle('hidden', !(hasKeyword && desktopEntries.length > 0 && desktopVisibleCount === 0));
        }

        if (mobileNoMatch) {
            mobileNoMatch.classList.toggle('hidden', !(hasKeyword && mobileEntries.length > 0 && mobileVisibleCount === 0));
        }

        const pagination = document.getElementById('ordersPagination');
        if (pagination) {
            pagination.classList.toggle('hidden', hasKeyword);
        }
    };

    const updateSectionsFromHtml = (html, requestUrl) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        ['ordersSummaryCards', 'ordersResultSection', 'ordersPagination'].forEach((id) => {
            const current = document.getElementById(id);
            const next = doc.getElementById(id);
            if (current && next) {
                current.replaceWith(next);
            }
        });

        if (requestUrl) {
            window.history.replaceState({}, '', requestUrl);
        }
    };

    const buildRequestUrl = (baseUrl = null) => {
        const action = form.getAttribute('action') || window.location.pathname;
        const url = new URL(baseUrl || action, window.location.origin);
        const formData = new FormData(form);
        url.searchParams.delete('keyword');
        url.searchParams.delete('table_keyword');

        if (!baseUrl) {
            url.searchParams.delete('page');
        }

        for (const [key, value] of formData.entries()) {
            if (key === 'table_keyword' || key === 'keyword') {
                continue;
            }

            const text = String(value).trim();
            if (text === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, text);
            }
        }

        return url;
    };

    const submitByAjax = async (baseUrl = null) => {
        const url = buildRequestUrl(baseUrl);

        if (activeRequestController) {
            activeRequestController.abort();
        }

        activeRequestController = new AbortController();

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeRequestController.signal,
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const html = await response.text();
            updateSectionsFromHtml(html, url.toString());
            bindOrderItemToggles();
            bindSortableHeaders();
            bindPaginationLinks();
            applyKeywordFilter();
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            allowNativeSubmit = true;
            form.submit();
        }
    };

    const bindSortableHeaders = () => {
        document.querySelectorAll('.sortable-header').forEach((btn) => {
            btn.addEventListener('click', () => {
                const nextBy = btn.getAttribute('data-sort-by');
                if (!nextBy || !sortByInput || !sortDirInput) {
                    return;
                }

                const currentBy = sortByInput.value;
                const currentDir = sortDirInput.value;
                sortByInput.value = nextBy;
                sortDirInput.value = (currentBy === nextBy && currentDir === 'asc') ? 'desc' : 'asc';
                submitByAjax();
            });
        });
    };

    const bindPaginationLinks = () => {
        const container = document.getElementById('ordersPagination');
        if (!container) {
            return;
        }

        container.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', (event) => {
                if (keywordInput && keywordInput.value.trim() !== '') {
                    event.preventDefault();
                    return;
                }
                event.preventDefault();
                submitByAjax(link.href);
            });
        });
    };

    form.addEventListener('submit', (event) => {
        if (allowNativeSubmit) {
            allowNativeSubmit = false;
            return;
        }

        event.preventDefault();
        submitByAjax();
    });

    if (keywordInput) {
        keywordInput.addEventListener('input', () => {
            applyKeywordFilter({ resetPage: true });
        });
        keywordInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
    }

    bindOrderItemToggles();
    bindSortableHeaders();
    bindPaginationLinks();
    applyKeywordFilter();
});
</script>
@endsection
