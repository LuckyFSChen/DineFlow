@extends('layouts.app')

@section('content')
@php
    $storeQuery = $selectedStoreId ? ['store_id' => $selectedStoreId] : [];
    $quickRanges = [
        ['label' => '今天', 'start' => now()->toDateString(), 'end' => now()->toDateString()],
        ['label' => '近 7 天', 'start' => now()->subDays(6)->toDateString(), 'end' => now()->toDateString()],
        ['label' => '近 30 天', 'start' => now()->subDays(29)->toDateString(), 'end' => now()->toDateString()],
    ];
@endphp

<div class="min-h-screen bg-gradient-to-b from-slate-100 via-slate-50 to-white py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-slate-600">區間：{{ $startDate }} 至 {{ $endDate }}</span>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-slate-600">門市：{{ $selectedStoreId ? ($stores->firstWhere('id', $selectedStoreId)->name ?? '未知門市') : '全部門市' }}</span>
            </div>

            <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">店家財務報表</h1>
                    <p class="mt-2 text-slate-600">即時檢視營收、商品與門市表現，快速找到重點變化。</p>
            </div>

                <form method="GET" class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-3 {{ $stores->count() > 1 ? 'lg:grid-cols-4' : '' }}">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="rounded-lg border-slate-300 bg-white text-sm">
                    <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-lg border-slate-300 bg-white text-sm">

                    @if($stores->count() > 1)
                        <select name="store_id" class="rounded-lg border-slate-300 bg-white text-sm">
                            <option value="">全部門市</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected((int) $selectedStoreId === (int) $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <button type="submit" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">套用篩選</button>
                </form>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($quickRanges as $range)
                    <a href="{{ route('merchant.reports.financial', array_merge($storeQuery, ['start_date' => $range['start'], 'end_date' => $range['end']])) }}"
                       class="rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-slate-400 hover:bg-slate-100">
                        {{ $range['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">總營收</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">NT$ {{ number_format($totalRevenue) }}</p>
                <p class="mt-1 text-xs text-slate-500">已排除取消訂單</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">訂單數</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalOrders) }}</p>
                <p class="mt-1 text-xs text-slate-500">完成、處理中與待處理</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">平均客單</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">NT$ {{ number_format($avgOrderValue) }}</p>
                <p class="mt-1 text-xs text-slate-500">總營收 / 訂單數</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">售出件數</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($itemsSold) }}</p>
                <p class="mt-1 text-xs text-slate-500">商品銷量總和</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">內用營收</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">NT$ {{ number_format($dineInRevenue) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">外帶營收</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">NT$ {{ number_format($takeoutRevenue) }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">營收趨勢</h2>
            <p class="mt-1 text-sm text-slate-500">依日期查看營收與訂單變化。</p>
            <div class="mt-4 h-[320px]">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">熱銷商品排行</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left">排名</th>
                                <th class="px-3 py-2 text-left">商品</th>
                                <th class="px-3 py-2 text-right">售出數量</th>
                                <th class="px-3 py-2 text-right">銷售額</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $index => $product)
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-2 text-slate-500">#{{ $index + 1 }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $product->display_name }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ number_format((int) $product->sold_qty) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-900">NT$ {{ number_format((int) $product->sold_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-slate-500">此區間尚無銷售資料</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">門市營收分佈</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left">門市</th>
                                <th class="px-3 py-2 text-right">訂單數</th>
                                <th class="px-3 py-2 text-right">營收</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($storeRevenue as $row)
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $row->store_name }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ number_format((int) $row->order_count) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-900">NT$ {{ number_format((int) $row->revenue) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-6 text-center text-slate-500">此區間尚無門市營收資料</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">商品銷售占比圓餅圖</h2>
            <p class="mt-1 text-sm text-slate-500">先勾選要分析的商品，再看占比與明細。</p>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs font-semibold tracking-wide text-slate-600">選擇要納入圓餅圖的商品 <span id="product-share-selected-count" class="ms-1 rounded-full bg-slate-200 px-2 py-0.5 text-[11px] text-slate-700">0</span></p>
                    <div class="flex items-center gap-3">
                        <button type="button" id="product-share-select-all" class="text-xs font-semibold text-slate-700 hover:text-slate-900">全選</button>
                        <button type="button" id="product-share-select-top5" class="text-xs font-semibold text-slate-700 hover:text-slate-900">勾選前五名</button>
                        <button type="button" id="product-share-reset" class="text-xs font-semibold text-brand-primary hover:text-brand-accent">全部重設</button>
                    </div>
                </div>
                <div id="product-share-picker" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3"></div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(280px,420px)_1fr]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">排行商品總銷售額</p>
                            <p id="product-share-total" class="mt-1 text-xl font-bold text-slate-900">NT$ 0</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">占比最高</p>
                            <p id="product-share-top" class="text-sm font-semibold text-slate-800">-</p>
                        </div>
                    </div>

                    <div id="product-share-chart-wrap" class="h-[300px]">
                        <canvas id="product-share-chart"></canvas>
                    </div>

                    <div id="product-share-empty" class="hidden rounded-lg border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-500">
                        此區間沒有可視化商品占比資料
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">商品占比明細</p>
                    <div id="product-share-legend" class="mt-3 grid gap-2 sm:grid-cols-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const labels = @json($chartLabels);
    const revenue = @json($chartRevenue);
    const orders = @json($chartOrders);
    const productShareLabels = @json($topProducts->pluck('display_name')->values());
    const productShareValues = @json($topProducts->pluck('sold_amount')->map(fn($v) => (int) $v)->values());

    const el = document.getElementById('revenue-chart');
    if (!el || typeof Chart === 'undefined') {
        return;
    }

    new Chart(el, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: '營收 (NT$)',
                    data: revenue,
                    yAxisID: 'yRevenue',
                    borderColor: '#ec9057',
                    backgroundColor: 'rgba(236, 144, 87, 0.2)',
                    fill: true,
                    tension: 0.3,
                },
                {
                    label: '訂單數',
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
                yRevenue: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    title: { display: true, text: '營收 (NT$)' }
                },
                yOrders: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: '訂單數' }
                }
            }
        }
    });

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

    if (productShareEl && productShareLegendEl && productSharePickerEl && Array.isArray(productShareValues)) {
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
                productShareTotalEl.textContent = `NT$ ${totalShareValue.toLocaleString('zh-TW')}`;
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
                                    return `${ctx.label}: NT$ ${val.toLocaleString('zh-TW')} (${ratio.toFixed(1)}%)`;
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
                sub.textContent = `NT$ ${row.value.toLocaleString('zh-TW')}`;

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
