@extends('layouts.app')

@section('content')
@php
    $selectedStore = $selectedStoreId ? $stores->firstWhere('id', $selectedStoreId) : null;
    $chartCurrencyCode = strtolower((string) ($selectedStore->currency ?? 'twd'));
    $chartCurrencySymbol = match ($chartCurrencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
    $storeQuery = array_filter([
        'store_id' => $selectedStoreId,
        'trend_granularity' => $trendGranularity,
        'hour_step' => $hourStep,
        'compare_start_date' => $compareStartDate,
        'compare_end_date' => $compareEndDate,
    ], fn ($value) => $value !== null && $value !== '');
    $quickRanges = [
        ['label' => __('merchant.today'), 'start' => now()->toDateString(), 'end' => now()->toDateString()],
        ['label' => __('merchant.last_7_days'), 'start' => now()->subDays(6)->toDateString(), 'end' => now()->toDateString()],
        ['label' => __('merchant.last_30_days'), 'start' => now()->subDays(29)->toDateString(), 'end' => now()->toDateString()],
    ];
@endphp

<div class="min-h-screen bg-gradient-to-b from-slate-100 via-slate-50 to-white py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-backend-header
            :title="__('merchant.financial_title')"
            :subtitle="__('merchant.financial_desc')"
            align="center"
        >
            <x-slot name="actions">
                <form id="financial-filter-form" method="GET" class="grid gap-3 rounded-xl border border-white/30 bg-white/10 p-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <label class="space-y-1 text-left">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-100/90">{{ __('merchant.start_date') }}</span>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900">
                    </label>
                    <label class="space-y-1 text-left">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-100/90">{{ __('merchant.end_date') }}</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900">
                    </label>
                    <label class="space-y-1 text-left">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-100/90">{{ __('merchant.compare_start_date') }}</span>
                        <input type="date" name="compare_start_date" value="{{ $compareStartDate }}" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900" placeholder="{{ __('merchant.compare_start_date') }}">
                    </label>
                    <label class="space-y-1 text-left">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-100/90">{{ __('merchant.compare_end_date') }}</span>
                        <input type="date" name="compare_end_date" value="{{ $compareEndDate }}" class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900" placeholder="{{ __('merchant.compare_end_date') }}">
                    </label>

                    <label class="space-y-1 text-left">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-100/90">{{ __('merchant.trend_granularity') }}</span>
                        <select name="trend_granularity" class="w-full rounded-lg border-slate-200 bg-white text-sm font-semibold text-slate-900 [color-scheme:light] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" style="color:#0f172a;background-color:#ffffff;">
                            <option class="bg-white text-slate-900" value="day" @selected($trendGranularity === 'day')>{{ __('merchant.trend_granularity_day') }}</option>
                            <option class="bg-white text-slate-900" value="hour" @selected($trendGranularity === 'hour')>{{ __('merchant.trend_granularity_hour') }}</option>
                        </select>
                    </label>

                    <label class="space-y-1 text-left">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-100/90">{{ __('merchant.hour_step') }}</span>
                        <select name="hour_step" class="w-full rounded-lg border-slate-200 bg-white text-sm font-semibold text-slate-900 [color-scheme:light] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" style="color:#0f172a;background-color:#ffffff;">
                            @foreach([1,2,3,4,6,12] as $step)
                                <option class="bg-white text-slate-900" value="{{ $step }}" @selected((int) $hourStep === $step)>{{ __('merchant.hour_step_option', ['hours' => $step]) }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if($stores->count() > 1)
                        <label class="space-y-1 text-left">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-100/90">{{ __('merchant.store') }}</span>
                            <select name="store_id" class="w-full rounded-lg border-slate-200 bg-white text-sm font-semibold text-slate-900 [color-scheme:light] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" style="color:#0f172a;background-color:#ffffff;">
                                <option class="bg-white text-slate-900" value="">{{ __('merchant.all_stores') }}</option>
                                @foreach($stores as $store)
                                    <option class="bg-white text-slate-900" value="{{ $store->id }}" @selected((int) $selectedStoreId === (int) $store->id)>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <div class="sm:col-span-2 lg:col-span-4 xl:col-span-5 flex flex-wrap items-center gap-2">
                        <button type="submit" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">{{ __('merchant.apply_filter') }}</button>
                        <a href="{{ route('merchant.reports.financial') }}" class="rounded-lg border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-white/20">{{ __('merchant.reset_all') }}</a>
                        @if($comparison)
                            <a href="{{ route('merchant.reports.financial', array_merge($storeQuery, ['start_date' => $startDate, 'end_date' => $endDate, 'compare_start_date' => '', 'compare_end_date' => ''])) }}" class="rounded-lg border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-white/20">{{ __('merchant.clear_compare') }}</a>
                        @endif
                    </div>
                </form>
            </x-slot>

            <x-slot name="extra">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full border border-white/30 bg-white/10 px-3 py-1 text-slate-100">{{ __('merchant.range') }}：{{ $startDate }} ～ {{ $endDate }}</span>
                    <span class="rounded-full border border-white/30 bg-white/10 px-3 py-1 text-slate-100">{{ __('merchant.store') }}：{{ $selectedStoreId ? ($stores->firstWhere('id', $selectedStoreId)->name ?? __('merchant.unknown_store')) : __('merchant.all_stores') }}</span>
                    <span class="rounded-full border border-white/30 bg-white/10 px-3 py-1 text-slate-100">{{ __('merchant.trend_granularity') }}：{{ $trendGranularity === 'hour' ? __('merchant.trend_granularity_hour') : __('merchant.trend_granularity_day') }}{{ $trendGranularity === 'hour' ? ' / ' . __('merchant.hour_step_option', ['hours' => $hourStep]) : '' }}</span>
                    @if($comparison)
                        <span class="rounded-full border border-white/30 bg-white/10 px-3 py-1 text-slate-100">{{ __('merchant.compare_range') }}：{{ $comparison['start_date'] }} ～ {{ $comparison['end_date'] }}</span>
                    @endif
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($quickRanges as $range)
                        <a href="{{ route('merchant.reports.financial', array_merge($storeQuery, ['start_date' => $range['start'], 'end_date' => $range['end']])) }}"
                           class="rounded-full border {{ $startDate === $range['start'] && $endDate === $range['end'] ? 'border-brand-highlight bg-brand-highlight/30 text-white' : 'border-white/30 bg-white/10 text-slate-100 hover:bg-white/20' }} px-3 py-1.5 text-xs font-semibold">
                            {{ $range['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="#summary-kpis" class="rounded-full border border-white/30 bg-white/10 px-3 py-1.5 text-xs font-semibold text-slate-100 hover:bg-white/20">{{ __('merchant.total_revenue') }}</a>
                    <a href="#trend-section" class="rounded-full border border-white/30 bg-white/10 px-3 py-1.5 text-xs font-semibold text-slate-100 hover:bg-white/20">{{ __('merchant.revenue_trend') }}</a>
                    <a href="#ranking-section" class="rounded-full border border-white/30 bg-white/10 px-3 py-1.5 text-xs font-semibold text-slate-100 hover:bg-white/20">{{ __('merchant.top_products') }}</a>
                    <a href="#share-section" class="rounded-full border border-white/30 bg-white/10 px-3 py-1.5 text-xs font-semibold text-slate-100 hover:bg-white/20">{{ __('merchant.product_share_title') }}</a>
                </div>
            </x-slot>
        </x-backend-header>

        @if(session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div id="summary-kpis" class="grid gap-4 scroll-mt-24 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.total_revenue') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $chartCurrencySymbol }} {{ number_format($totalRevenue) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('merchant.exclude_cancelled') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.total_orders') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalOrders) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('merchant.order_status_scope') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.avg_order_value') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $chartCurrencySymbol }} {{ number_format($avgOrderValue) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('merchant.avg_formula') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.items_sold') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($itemsSold) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('merchant.items_sold_hint') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.total_profit') }}</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $chartCurrencySymbol }} {{ number_format($totalProfit) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('merchant.profit_formula') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.gross_margin_rate') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($grossMarginRate, 1) }}%</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('merchant.gross_margin_formula') }}</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ __('merchant.monthly_target') }}</p>
                        <p class="mt-2 text-2xl font-bold text-indigo-900">{{ $chartCurrencySymbol }} {{ number_format($monthlyRevenueTarget) }}</p>
                        <p class="mt-1 text-xs text-indigo-700">{{ __('merchant.month_revenue_actual') }}：{{ $chartCurrencySymbol }} {{ number_format($currentMonthRevenue) }}</p>
                        <p class="text-xs text-indigo-700">{{ __('merchant.month_target_remaining') }}：{{ $chartCurrencySymbol }} {{ number_format($monthlyTargetRemaining) }}</p>
                    </div>

                    @if($canEditMonthlyTarget && $selectedStore)
                        <form method="POST" action="{{ route('merchant.reports.financial.monthly-target') }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                            <input type="hidden" name="end_date" value="{{ $endDate }}">
                            <input type="hidden" name="compare_start_date" value="{{ $compareStartDate }}">
                            <input type="hidden" name="compare_end_date" value="{{ $compareEndDate }}">
                            <input type="hidden" name="trend_granularity" value="{{ $trendGranularity }}">
                            <input type="hidden" name="hour_step" value="{{ $hourStep }}">
                            <input
                                type="number"
                                min="0"
                                step="100"
                                name="monthly_revenue_target"
                                value="{{ $monthlyRevenueTarget }}"
                                class="w-36 rounded-lg border-indigo-300 bg-white text-sm text-slate-900"
                            >
                            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">{{ __('merchant.save_target') }}</button>
                        </form>
                    @else
                        <p class="text-xs font-medium text-indigo-700">{{ __('merchant.monthly_target_hint_choose_store') }}</p>
                    @endif
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-xs font-semibold text-indigo-700">
                        <span>{{ __('merchant.month_target_progress') }}</span>
                        <span>{{ number_format($monthlyTargetProgress, 1) }}%</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-indigo-100">
                        <div class="h-2.5 rounded-full bg-indigo-500" style="width: {{ min(100, $monthlyTargetProgress) }}%"></div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800">{{ __('merchant.compare_summary') }}</h3>
                @if($comparison)
                    <p class="mt-1 text-xs text-slate-500">{{ $comparison['start_date'] }} ~ {{ $comparison['end_date'] }}</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">{{ __('merchant.total_revenue') }}</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ $chartCurrencySymbol }} {{ number_format($comparison['total_revenue']) }}</p>
                            <p class="mt-1 text-xs font-semibold {{ $comparison['delta_revenue'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $comparison['delta_revenue'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_revenue']) }} ({{ $comparison['delta_revenue_ratio'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_revenue_ratio'], 1) }}%)</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">{{ __('merchant.total_orders') }}</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ number_format($comparison['total_orders']) }}</p>
                            <p class="mt-1 text-xs font-semibold {{ $comparison['delta_orders'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $comparison['delta_orders'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_orders']) }} ({{ $comparison['delta_orders_ratio'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_orders_ratio'], 1) }}%)</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">{{ __('merchant.avg_order_value') }}</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ $chartCurrencySymbol }} {{ number_format($comparison['avg_order_value']) }}</p>
                            <p class="mt-1 text-xs font-semibold {{ $comparison['delta_avg_order_value'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $comparison['delta_avg_order_value'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_avg_order_value']) }} ({{ $comparison['delta_avg_order_value_ratio'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_avg_order_value_ratio'], 1) }}%)</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">{{ __('merchant.total_profit') }}</p>
                            <p class="mt-1 text-lg font-bold text-emerald-700">{{ $chartCurrencySymbol }} {{ number_format($comparison['total_profit']) }}</p>
                            <p class="mt-1 text-xs font-semibold {{ $comparison['delta_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $comparison['delta_profit'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_profit']) }} ({{ $comparison['delta_profit_ratio'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_profit_ratio'], 1) }}%)</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        {{ __('merchant.gross_margin_rate') }}: {{ number_format($comparison['gross_margin_rate'], 1) }}%
                        <span class="{{ $comparison['delta_gross_margin_rate'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            ({{ $comparison['delta_gross_margin_rate'] >= 0 ? '+' : '' }}{{ number_format($comparison['delta_gross_margin_rate'], 1) }}%)
                        </span>
                    </p>
                @else
                    <p class="mt-3 text-sm text-slate-500">{{ __('merchant.compare_empty_hint') }}</p>
                @endif
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ __('merchant.dinein_revenue') }}</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ $chartCurrencySymbol }} {{ number_format($dineInRevenue) }}</p>
                <p class="mt-1 text-xs text-amber-700">{{ __('merchant.revenue_ratio') }}：{{ number_format($dineInRevenueRatio, 1) }}%</p>
                <p class="text-xs text-amber-700">{{ __('merchant.order_ratio') }}：{{ number_format($dineInOrdersRatio, 1) }}% ({{ number_format($dineInOrders) }} {{ __('merchant.orders') }})</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ __('merchant.takeout_revenue') }}</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">{{ $chartCurrencySymbol }} {{ number_format($takeoutRevenue) }}</p>
                <p class="mt-1 text-xs text-emerald-700">{{ __('merchant.revenue_ratio') }}：{{ number_format($takeoutRevenueRatio, 1) }}%</p>
                <p class="text-xs text-emerald-700">{{ __('merchant.order_ratio') }}：{{ number_format($takeoutOrdersRatio, 1) }}% ({{ number_format($takeoutOrders) }} {{ __('merchant.orders') }})</p>
            </div>
        </div>

        <div id="trend-section" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm scroll-mt-24">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('merchant.revenue_trend') }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $trendGranularity === 'hour' ? __('merchant.revenue_trend_desc_hourly', ['hours' => $hourStep]) : __('merchant.revenue_trend_desc') }}
            </p>
            <div class="mt-4 h-[320px]">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>

        <div id="ranking-section" class="mt-6 grid gap-6 lg:grid-cols-2 scroll-mt-24">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('merchant.top_products') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm" data-datatable data-dt-paging="false" data-dt-info="false">
                        <thead class="bg-slate-100 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left">{{ __('merchant.rank') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('merchant.product') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('merchant.qty') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('merchant.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $index => $product)
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-2 text-slate-500">#{{ $index + 1 }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $product->display_name }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ number_format((int) $product->sold_qty) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-900">{{ $chartCurrencySymbol }} {{ number_format((int) $product->sold_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-slate-500">{{ __('merchant.no_sales_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('merchant.store_revenue_distribution') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm" data-datatable data-dt-paging="false" data-dt-info="false">
                        <thead class="bg-slate-100 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left">{{ __('merchant.store') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('merchant.orders') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('merchant.revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($storeRevenue as $row)
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $row->store_name }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ number_format((int) $row->order_count) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-900">{{ $chartCurrencySymbol }} {{ number_format((int) $row->revenue) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-6 text-center text-slate-500">{{ __('merchant.no_store_revenue_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="share-section" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm scroll-mt-24">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('merchant.product_share_title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('merchant.product_share_desc') }}</p>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs font-semibold tracking-wide text-slate-600">{{ __('merchant.product_share_picker_title') }} <span id="product-share-selected-count" class="ms-1 rounded-full bg-slate-200 px-2 py-0.5 text-[11px] text-slate-700">0</span></p>
                    <div class="flex items-center gap-3">
                        <button type="button" id="product-share-select-all" class="text-xs font-semibold text-slate-700 hover:text-slate-900">{{ __('merchant.select_all') }}</button>
                        <button type="button" id="product-share-select-top5" class="text-xs font-semibold text-slate-700 hover:text-slate-900">{{ __('merchant.select_top5') }}</button>
                        <button type="button" id="product-share-reset" class="text-xs font-semibold text-brand-primary hover:text-brand-accent">{{ __('merchant.reset_all') }}</button>
                    </div>
                </div>
                <div id="product-share-picker" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3"></div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(280px,420px)_1fr]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.rank_total_sales') }}</p>
                            <p id="product-share-total" class="mt-1 text-xl font-bold text-slate-900">{{ $chartCurrencySymbol }} 0</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">{{ __('merchant.top_ratio') }}</p>
                            <p id="product-share-top" class="text-sm font-semibold text-slate-800">-</p>
                        </div>
                    </div>

                    <div id="product-share-chart-wrap" class="h-[300px]">
                        <canvas id="product-share-chart"></canvas>
                    </div>

                    <div id="product-share-empty" class="hidden rounded-lg border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-500">
                        {{ __('merchant.no_share_data') }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('merchant.share_legend') }}</p>
                    <div id="product-share-legend" class="mt-3 grid gap-2 sm:grid-cols-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const filterForm = document.getElementById('financial-filter-form');
    const trendGranularitySelect = filterForm?.querySelector('select[name="trend_granularity"]');
    const hourStepSelect = filterForm?.querySelector('select[name="hour_step"]');

    const syncHourStepState = () => {
        if (!trendGranularitySelect || !hourStepSelect) {
            return;
        }

        const useHourly = trendGranularitySelect.value === 'hour';
        hourStepSelect.disabled = !useHourly;
        hourStepSelect.classList.toggle('opacity-60', !useHourly);
        hourStepSelect.classList.toggle('cursor-not-allowed', !useHourly);
    };

    trendGranularitySelect?.addEventListener('change', syncHourStepState);
    syncHourStepState();

    const labels = @json($chartLabels);
    const revenue = @json($chartRevenue);
    const orders = @json($chartOrders);
    const chartRevenueLabel = @json(__('merchant.chart_revenue_label', ['currency' => $chartCurrencySymbol]));
    const chartOrdersLabel = @json(__('merchant.chart_orders_label'));
    const chartCurrencySymbol = @json($chartCurrencySymbol);
    const productShareLabels = @json($topProducts->pluck('display_name')->values());
    const productShareValues = @json($topProducts->pluck('sold_amount')->map(fn($v) => (int) $v)->values());
    const canRenderChart = typeof Chart !== 'undefined';

    const el = document.getElementById('revenue-chart');
    if (el && canRenderChart) {
        new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: chartRevenueLabel,
                        data: revenue,
                        yAxisID: 'yRevenue',
                        borderColor: '#ec9057',
                        backgroundColor: 'rgba(236, 144, 87, 0.2)',
                        fill: true,
                        tension: 0.3,
                    },
                    {
                        label: chartOrdersLabel,
                        data: orders,
                        yAxisID: 'yOrders',
                        borderColor: '#5A1E0E',
                        backgroundColor: 'rgba(90, 30, 14, 0.15)',
                        fill: false,
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 0,
                            maxTicksLimit: 18,
                        }
                    },
                    yRevenue: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: { display: true, text: chartRevenueLabel }
                    },
                    yOrders: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: chartOrdersLabel }
                    }
                }
            }
        });
    }

    const productShareEl = document.getElementById('product-share-chart');
    const productShareLegendEl = document.getElementById('product-share-legend');
    const productSharePickerEl = document.getElementById('product-share-picker');
    const productShareSelectAllEl = document.getElementById('product-share-select-all');
    const productShareSelectTop5El = document.getElementById('product-share-select-top5');
    const productShareResetEl = document.getElementById('product-share-reset');
    const productShareSelectedCountEl = document.getElementById('product-share-selected-count');
    const productShareTotalEl = document.getElementById('product-share-total');
    const productShareTopEl = document.getElementById('product-share-top');
    const productShareEmptyEl = document.getElementById('product-share-empty');
    const productShareChartWrap = document.getElementById('product-share-chart-wrap');

    if (canRenderChart && productShareEl && productShareLegendEl && productSharePickerEl && Array.isArray(productShareValues)) {
        const pieColors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#06b6d4', '#e11d48'];
        const selectedShareIndices = new Set(productShareValues.map((_, idx) => idx).slice(0, Math.min(5, productShareValues.length)));
        let productShareChart = null;

        const buildFilteredShareRows = () => productShareValues
            .map((value, idx) => ({ index: idx, label: productShareLabels[idx], value: Number(value || 0), color: pieColors[idx % pieColors.length] }))
            .filter((row) => selectedShareIndices.has(row.index) && row.value > 0);

        const syncPickerChecks = () => {
            productSharePickerEl.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                input.checked = selectedShareIndices.has(Number(input.value));
            });
        };

        const renderShare = () => {
            const rows = buildFilteredShareRows();
            const totalShareValue = rows.reduce((sum, row) => sum + row.value, 0);
            const selectedCount = selectedShareIndices.size;

            productShareLegendEl.innerHTML = '';

            if (productShareSelectedCountEl) {
                productShareSelectedCountEl.textContent = `${selectedCount}`;
            }

            if (productShareTotalEl) {
                productShareTotalEl.textContent = `${chartCurrencySymbol} ${totalShareValue.toLocaleString('zh-TW')}`;
            }

            if (rows.length > 0 && productShareTopEl) {
                const topRow = rows.reduce((max, row) => row.value > max.value ? row : max, rows[0]);
                const topRate = totalShareValue > 0 ? (topRow.value / totalShareValue) * 100 : 0;
                productShareTopEl.textContent = `${topRow.label} (${topRate.toFixed(1)}%)`;
            } else if (productShareTopEl) {
                productShareTopEl.textContent = '-';
            }

            if (rows.length === 0 || totalShareValue <= 0) {
                if (productShareChart) {
                    productShareChart.destroy();
                    productShareChart = null;
                }
                if (productShareChartWrap) {
                    productShareChartWrap.classList.add('hidden');
                }
                if (productShareEmptyEl) {
                    productShareEmptyEl.classList.remove('hidden');
                }
                return;
            }

            if (productShareChartWrap) {
                productShareChartWrap.classList.remove('hidden');
            }
            if (productShareEmptyEl) {
                productShareEmptyEl.classList.add('hidden');
            }

            if (productShareChart) {
                productShareChart.destroy();
            }

            productShareChart = new Chart(productShareEl, {
                type: 'doughnut',
                data: {
                    labels: rows.map((row) => row.label),
                    datasets: [
                        {
                            data: rows.map((row) => row.value),
                            backgroundColor: rows.map((row) => row.color),
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 8,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const val = Number(ctx.raw || 0);
                                    const ratio = totalShareValue > 0 ? (val / totalShareValue) * 100 : 0;
                                    return `${ctx.label}: ${chartCurrencySymbol} ${val.toLocaleString('zh-TW')} (${ratio.toFixed(1)}%)`;
                                },
                            }
                        }
                    }
                }
            });

            rows.forEach((row) => {
                const ratio = totalShareValue > 0 ? (row.value / totalShareValue) * 100 : 0;

                const itemBtn = document.createElement('button');
                itemBtn.type = 'button';
                itemBtn.className = 'flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-left transition hover:border-slate-300 hover:bg-slate-50';

                const left = document.createElement('div');
                left.className = 'min-w-0';

                const title = document.createElement('p');
                title.className = 'truncate text-sm font-medium text-slate-800';
                title.textContent = row.label;

                const sub = document.createElement('p');
                sub.className = 'text-xs text-slate-500';
                sub.textContent = `${chartCurrencySymbol} ${row.value.toLocaleString('zh-TW')}`;

                const badge = document.createElement('span');
                badge.className = 'rounded-full px-2 py-1 text-xs font-semibold text-white';
                badge.style.backgroundColor = row.color;
                badge.textContent = `${ratio.toFixed(1)}%`;

                left.appendChild(title);
                left.appendChild(sub);
                itemBtn.appendChild(left);
                itemBtn.appendChild(badge);

                productShareLegendEl.appendChild(itemBtn);
            });
        };

        productShareLabels.forEach((label, idx) => {
            const checkboxWrap = document.createElement('label');
            checkboxWrap.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = String(idx);
            checkbox.checked = selectedShareIndices.has(idx);
            checkbox.className = 'rounded border-slate-300 text-brand-primary focus:ring-brand-primary';

            const dot = document.createElement('span');
            dot.className = 'inline-block h-2.5 w-2.5 rounded-full';
            dot.style.backgroundColor = pieColors[idx % pieColors.length];

            const text = document.createElement('span');
            text.className = 'truncate';
            text.textContent = `${idx + 1}. ${label}`;

            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    selectedShareIndices.add(idx);
                } else {
                    selectedShareIndices.delete(idx);
                }

                renderShare();
            });

            checkboxWrap.appendChild(checkbox);
            checkboxWrap.appendChild(dot);
            checkboxWrap.appendChild(text);
            productSharePickerEl.appendChild(checkboxWrap);
            });

        if (productShareResetEl) {
            productShareResetEl.addEventListener('click', () => {
                selectedShareIndices.clear();
                syncPickerChecks();
                renderShare();
            });
        }

        if (productShareSelectTop5El) {
            productShareSelectTop5El.addEventListener('click', () => {
                selectedShareIndices.clear();
                productShareValues.forEach((_, idx) => {
                    if (idx < 5) {
                        selectedShareIndices.add(idx);
                    }
                });
                syncPickerChecks();
                renderShare();
            });
        }

        if (productShareSelectAllEl) {
            productShareSelectAllEl.addEventListener('click', () => {
                selectedShareIndices.clear();
                productShareValues.forEach((_, idx) => {
                    selectedShareIndices.add(idx);
                });
                syncPickerChecks();
                renderShare();
            });
        }

        renderShare();
    }
})();
</script>
@endsection
