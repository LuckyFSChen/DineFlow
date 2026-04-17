@extends('layouts.app')

@section('title', __('admin.board_kitchen_title') . ' - ' . $store->name)

@php
$storeRouteValue = static function ($value) {
    if ($value instanceof \App\Models\Store) {
        return $value->getRouteKey();
    }

    if (is_array($value)) {
        return $value['slug'] ?? $value['id'] ?? null;
    }

    return is_string($value) || is_int($value) ? $value : null;
};

$storeRoute = $storeRouteValue($store);
function kitchenFormatOrder(\App\Models\Order $order): array {
    return [
        'id'           => $order->id,
        'order_no'     => $order->order_no,
        'order_locale' => $order->order_locale,
        'status'       => $order->status,
        'payment_status' => $order->payment_status,
        'order_type'   => $order->order_type,
        'note'         => $order->note,
        'customer_name'=> $order->customer_name,
        'created_at'   => $order->created_at?->toIso8601String(),
        'table'        => ($t = $order->getRelation('table')) ? ['table_no' => $t->table_no] : null,
        'items'        => $order->items->map(fn($i) => [
            'id'             => $i->id,
            'product_name'   => $i->product_name,
            'qty'            => $i->qty,
            'note'           => $i->note,
            'item_status'    => $i->item_status ?: 'preparing',
            'completed_at'   => $i->completed_at?->toIso8601String(),
            'option_summary' => null,
            '_loading'       => false,
        ])->values()->all(),
        '_loading' => false,
    ];
}
$ordersData = $orders->map(fn($o) => kitchenFormatOrder($o))->values()->all();
$kitchenI18n = [
    'order_unit' => __('admin.board_order_unit'),
    'next_refresh' => __('admin.board_next_refresh'),
    'not_updated_yet' => __('admin.board_not_updated_yet'),
    'waiting_prefix' => __('admin.board_waiting_prefix'),
    'locale_prefix' => __('admin.board_locale_prefix'),
    'item_progress' => __('admin.board_item_progress'),
    'item_note_label' => __('admin.board_item_note_label'),
    'processing' => __('admin.board_processing'),
    'mark_completed' => __('admin.board_action_mark_completed'),
    'mark_item_completed' => __('admin.board_action_mark_item_completed'),
    'item_completed' => __('admin.board_item_status_completed'),
    'error_update_failed' => __('admin.board_error_update_failed'),
    'error_missing_csrf' => __('admin.board_error_missing_csrf'),
    'error_network' => __('admin.board_error_network'),
    'status_preparing' => __('admin.board_status_preparing'),
    'seconds_ago' => __('admin.board_time_seconds_ago'),
    'minutes_ago' => __('admin.board_time_minutes_ago'),
    'hours_ago' => __('admin.board_time_hours_ago'),
];
@endphp

@section('content')
<div class="min-h-screen bg-slate-900 text-white" x-data="kitchenBoard()" x-init="init()">

    {{-- Header --}}
    <div class="sticky top-0 z-20 border-b border-slate-700 bg-slate-900/95 px-4 py-3 backdrop-blur sm:px-6">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M8 5 3 10l5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M4 10h13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                {{ __('admin.board_back_to_stores') }}
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-lg font-bold text-white">
                    <svg class="h-5 w-5 text-orange-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 3v7a3 3 0 0 0 3 3V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 3v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M12 3v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M16 3c2.2 0 4 1.8 4 4v6h-4V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M20 13v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    {{ __('admin.board_kitchen_title') }}
                </h1>
                <p class="text-xs text-slate-400">{{ $store->name }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            {{-- Board switch --}}
            <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                <a href="{{ route('admin.stores.boards', ['store' => $storeRoute]) }}"
                   class="px-3 py-1.5 bg-indigo-600 text-white transition hover:bg-indigo-500">
                    {{ $store->name }}
                </a>
            </div>

            @if(($availableStores ?? collect())->count() > 1)
                <div class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-2 py-1.5 text-xs">
                    <span class="text-slate-400">{{ __('admin.board_store') }}</span>
                    <select
                        class="board-store-select rounded border border-slate-600 bg-slate-900 px-2 py-1 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none"
                        onchange="if (this.value) window.location.href = this.value;">
                        @foreach($availableStores as $availableStore)
                            <option value="{{ route('admin.stores.kitchen', ['store' => $storeRouteValue($availableStore)]) }}" @selected((int) $availableStore->id === (int) $store->id)>
                                {{ $availableStore->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="relative">
                <input
                    x-model.trim="searchTerm"
                    type="text"
                    placeholder="{{ __('admin.board_search_placeholder') }}"
                    class="w-52 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none">
            </div>

            <button
                @click="refreshNow()"
                type="button"
                class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-slate-700">
                {{ __('admin.board_refresh_now') }}
            </button>

            {{-- Live indicator --}}
            <div class="flex items-center gap-1.5 rounded-full border border-emerald-700 bg-emerald-900/50 px-3 py-1 text-xs text-emerald-400">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                {{ __('admin.board_live_updating') }}
            </div>

            {{-- Count badge --}}
            <span class="rounded-full bg-indigo-600 px-3 py-0.5 text-xs font-bold" x-text="filteredOrders.length + ' / ' + orders.length + ' ' + i18n.order_unit"></span>
        </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">{{ __('admin.board_stat_preparing') }}</p>
                <p class="text-lg font-bold text-blue-300" x-text="preparingCount"></p>
            </div>
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">{{ __('admin.board_stat_overdue') }}</p>
                <p class="text-lg font-bold text-rose-300" x-text="overdueCount"></p>
            </div>
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">{{ __('admin.board_last_updated') }}</p>
                <p class="text-sm font-semibold text-slate-100" x-text="lastUpdatedText + ' · ' + i18n.next_refresh + nextRefreshIn + 's'"></p>
            </div>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="filteredOrders.length === 0 && !loading" class="flex flex-col items-center justify-center py-32 text-slate-500">
        <svg class="mb-4 h-16 w-16 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-xl font-semibold">{{ __('admin.board_empty_preparing') }}</p>
        <p class="mt-1 text-sm">{{ __('admin.board_empty_auto') }}</p>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-600 border-t-indigo-400"></div>
    </div>

    {{-- Order grid --}}
    <div x-show="!loading" class="grid gap-4 p-4 sm:p-6" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <template x-for="order in filteredOrders" :key="order.id">
            <div class="flex flex-col rounded-2xl border overflow-hidden transition-all duration-300 bg-blue-950/30"
                 :style="waitCardStyle(order)">

                {{-- Order card header --}}
                <div class="flex items-start justify-between px-4 pt-4 pb-3 border-b border-blue-700/40">
                    <div>
                        {{-- Order No --}}
                        <span class="font-mono text-lg font-bold text-white" x-text="'#' + (order.order_no || order.id)"></span>
                        {{-- Meta --}}
                        <div class="mt-0.5 flex items-center gap-2 text-xs">
                            <span class="text-slate-500" x-text="timeAgo(order.created_at)"></span>
                            <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                  :class="waitBadgeClass(order)"
                                  :style="waitBadgeStyle(order)"
                                  x-text="i18n.waiting_prefix + waitMinutes(order) + 'm'"></span>
                                <span class="rounded border border-sky-500/40 bg-sky-900/40 px-1.5 py-0.5 text-[10px] font-semibold text-sky-300"
                                    x-text="i18n.locale_prefix + localeLabel(order.order_locale)"></span>
                        </div>
                        {{-- Customer name --}}
                        <div x-show="order.customer_name" class="mt-0.5 text-xs text-slate-400">
                            <span x-text="order.customer_name"></span>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-1.5">
                        {{-- Status badge --}}
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-500/20 text-blue-300">
                            {{ __('admin.board_status_preparing') }}
                        </span>
                        <template x-if="order.order_type === 'dine_in' && order.table">
                            <span class="rounded bg-slate-700 px-2.5 py-1 text-sm font-semibold text-slate-200">
                                {{ __('admin.board_table_no') }} <span x-text="order.table.table_no"></span>
                            </span>
                        </template>
                        <template x-if="order.order_type === 'takeout' || order.order_type === 'take_out'">
                            <span class="rounded bg-orange-800/60 px-2.5 py-1 text-sm font-semibold text-orange-200">{{ __('admin.board_takeout') }}</span>
                        </template>
                    </div>
                </div>

                {{-- Items list --}}
                <div class="flex-1 px-4 py-3 space-y-2">
                    <div class="mb-1 text-[11px] font-semibold text-slate-300">
                        <span x-text="i18n.item_progress"></span>：<span x-text="completedItemCount(order)"></span>/<span x-text="order.items.length"></span>
                    </div>
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex items-start justify-between gap-2 rounded-lg border px-2 py-2 transition-colors duration-150"
                             role="button"
                             tabindex="0"
                             @click="toggleItemStatus(order, item)"
                             @keydown.enter.prevent="toggleItemStatus(order, item)"
                             @keydown.space.prevent="toggleItemStatus(order, item)"
                             :class="isItemCompleted(item)
                                ? 'border-emerald-500/40 bg-emerald-900/20 cursor-pointer hover:bg-emerald-900/30'
                                : 'border-slate-700/60 bg-slate-900/20 cursor-pointer hover:border-indigo-400/70 hover:bg-slate-900/40'">
                            <div class="flex items-start gap-2">
                                <button
                                    @click.stop="toggleItemStatus(order, item)"
                                    :disabled="item._loading"
                                    type="button"
                                    class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md border transition disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="isItemCompleted(item)
                                        ? 'border-emerald-300 bg-emerald-500 text-white hover:bg-emerald-400'
                                        : 'border-slate-500 bg-slate-800 text-slate-300 hover:border-indigo-400 hover:text-indigo-300'">
                                    <span x-show="item._loading" class="h-3 w-3 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                    <svg x-show="!item._loading && isItemCompleted(item)" class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 10.5 8.2 13.7 15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <svg x-show="!item._loading && !isItemCompleted(item)" class="h-4 w-4 opacity-60" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <rect x="4" y="4" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </button>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold" :class="isItemCompleted(item) ? 'text-emerald-200 line-through' : 'text-white'" x-text="item.product_name"></span>
                                        <span class="text-xs font-semibold" :class="isItemCompleted(item) ? 'text-emerald-300' : 'text-slate-300'" x-text="'x ' + item.qty"></span>
                                    </div>
                                    <div x-show="item.note" class="mt-1 rounded-md border border-yellow-700/40 bg-yellow-900/20 px-2 py-1 text-xs text-yellow-300">
                                        <div class="font-semibold" x-text="i18n.item_note_label"></div>
                                        <div class="mt-0.5 break-words" x-text="item.note"></div>
                                    </div>
                                    <div x-show="item.option_summary" class="mt-0.5 text-xs text-slate-400" x-text="item.option_summary"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Order note --}}
                <template x-if="order.note">
                    <div class="mx-4 mb-3 rounded-lg bg-yellow-900/30 border border-yellow-700/30 px-3 py-2 text-xs text-yellow-300">
                        <span class="font-semibold">{{ __('admin.board_note_label') }}</span><span x-text="order.note"></span>
                    </div>
                </template>

            </div>
        </template>
    </div>

    {{-- New order toast --}}
    <div x-show="newOrderAlert" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border border-emerald-500/50 bg-emerald-900 px-5 py-3 shadow-xl">
        <span class="text-2xl">!</span>
        <div>
            <p class="font-bold text-white">{{ __('admin.board_new_order_arrived') }}</p>
            <p class="text-xs text-emerald-300">{{ __('admin.board_new_order_added') }}</p>
        </div>
    </div>

    <div x-show="errorMessage" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-6 left-6 z-50 max-w-md rounded-2xl border border-rose-500/50 bg-rose-900 px-5 py-3 text-sm text-rose-100 shadow-xl">
        <p class="font-semibold">{{ __('admin.board_operation_failed') }}</p>
        <p class="mt-1" x-text="errorMessage"></p>
    </div>
</div>

<script>
function kitchenBoard() {
    return {
        orders: @json($ordersData),
        statusUrlTemplate: @json(route('admin.stores.kitchen.orders.status', ['store' => $storeRoute, 'order' => '__ORDER__'])),
        checkoutTiming: @json($checkoutTiming ?? 'postpay'),
        i18n: @json($kitchenI18n),
        filter: 'all',
        searchTerm: '',
        loading: false,
        newOrderAlert: false,
        errorMessage: '',
        lastUpdatedAt: null,
        pollSeconds: 10,
        nextRefreshIn: 10,
        _pollTimer: null,
        _countdownTimer: null,
        _alertTimer: null,
        _errorTimer: null,
        _audioContext: null,
        _audioUnlockHandler: null,
        waitConfigKey: 'board_wait_thresholds_v1',
        waitConfig: { orangeStart: 1, orangeEnd: 5, redEnd: 10 },

        get filteredOrders() {
            let result = [...this.orders];
            const keyword = this.searchTerm.toLowerCase();

            if (keyword) {
                result = result.filter((o) => {
                    const orderNo = String(o.order_no || o.id || '').toLowerCase();
                    const customer = String(o.customer_name || '').toLowerCase();
                    const tableNo = String(o.table?.table_no || '').toLowerCase();
                    return orderNo.includes(keyword) || customer.includes(keyword) || tableNo.includes(keyword);
                });
            }

            return result;
        },

        get preparingCount() {
            return this.orders.length;
        },

        get overdueCount() {
            return this.orders.filter((o) => this.waitMinutes(o) >= this.normalizedWaitConfig().redEnd).length;
        },

        localeLabel(locale) {
            const map = {
                zh_TW: 'ZH',
                zh_CN: 'CN',
                en: 'EN',
                vi: 'VI',
            };

            return map[String(locale || '')] || 'ZH';
        },

        get lastUpdatedText() {
            if (!this.lastUpdatedAt) {
                return this.i18n.not_updated_yet;
            }

            const d = new Date(this.lastUpdatedAt);
            return d.toLocaleTimeString();
        },

        init() {
            clearInterval(this._pollTimer);
            clearInterval(this._countdownTimer);
            this.loadWaitConfig();
            this.initAudio();
            this.lastUpdatedAt = Date.now();
            this.nextRefreshIn = this.pollSeconds;
            this._pollTimer = setInterval(() => this.poll(), this.pollSeconds * 1000);
            this._countdownTimer = setInterval(() => this.tickCountdown(), 1000);
        },

        tickCountdown() {
            if (this.nextRefreshIn <= 1) {
                this.nextRefreshIn = this.pollSeconds;
                return;
            }

            this.nextRefreshIn -= 1;
        },

        refreshNow() {
            this.nextRefreshIn = this.pollSeconds;
            this.poll();
        },

        initAudio() {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }

            this._audioContext = new AudioCtx();
            this._audioUnlockHandler = () => {
                if (this._audioContext?.state === 'suspended') {
                    this._audioContext.resume().catch(() => {});
                }

                window.removeEventListener('click', this._audioUnlockHandler);
                window.removeEventListener('keydown', this._audioUnlockHandler);
            };

            window.addEventListener('click', this._audioUnlockHandler, { once: true });
            window.addEventListener('keydown', this._audioUnlockHandler, { once: true });
        },

        async poll() {
            try {
                const res = await fetch('{{ route('admin.stores.kitchen.orders', ['store' => $storeRoute]) }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const fresh = await res.json();
                const freshIds = new Set(fresh.map(o => o.id));
                const oldIds   = new Set(this.orders.map(o => o.id));
                const hasNew   = fresh.some(o => !oldIds.has(o.id));
                this.orders = fresh;
                this.lastUpdatedAt = Date.now();
                if (hasNew) this.showAlert();
            } catch {}
        },

        showAlert() {
            clearTimeout(this._alertTimer);
            this.newOrderAlert = true;
            this.playAlertSound();
            this._alertTimer = setTimeout(() => { this.newOrderAlert = false; }, 4000);
        },

        playAlertSound() {
            if (!this._audioContext) {
                return;
            }

            const now = this._audioContext.currentTime;
            const playBeep = (start, frequency, duration) => {
                const osc = this._audioContext.createOscillator();
                const gain = this._audioContext.createGain();
                osc.type = 'sine';
                osc.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.12, start + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
                osc.connect(gain);
                gain.connect(this._audioContext.destination);
                osc.start(start);
                osc.stop(start + duration);
            };

            if (this._audioContext.state === 'suspended') {
                this._audioContext.resume().catch(() => {});
            }

            playBeep(now, 880, 0.16);
            playBeep(now + 0.22, 1175, 0.2);
        },

        showError(message) {
            clearTimeout(this._errorTimer);
            this.errorMessage = message || this.i18n.error_update_failed;
            this._errorTimer = setTimeout(() => { this.errorMessage = ''; }, 5000);
        },

        isItemCompleted(item) {
            return String(item?.item_status || '').toLowerCase() === 'completed';
        },

        completedItemCount(order) {
            if (!Array.isArray(order?.items)) {
                return 0;
            }

            return order.items.filter((item) => this.isItemCompleted(item)).length;
        },

        toggleItemStatus(order, item) {
            if (item?._loading) {
                return;
            }

            this.setItemStatus(order, item, this.isItemCompleted(item) ? 'preparing' : 'completed');
        },

        async setItemStatus(order, item, status) {
            item._loading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!token) {
                    this.showError(this.i18n.error_missing_csrf);
                    item._loading = false;
                    return;
                }

                const statusUrl = this.statusUrlTemplate.replace('__ORDER__', String(order.id));
                const res = await fetch(
                    statusUrl,
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            status,
                            item_status: status,
                            item_id: item.id,
                        }),
                    }
                );

                if (!res.ok) {
                    let message = `HTTP ${res.status}`;
                    try {
                        const data = await res.json();
                        message = data.message || message;
                    } catch {}
                    this.showError(message);
                    item._loading = false;
                    return;
                }

                if (res.ok) {
                    const data = await res.json();
                    item.item_status = data?.item?.item_status || status;
                    item.completed_at = data?.item?.completed_at || null;

                    if (String(data?.order?.status || '').toLowerCase() === 'completed') {
                        this.orders = this.orders.filter((o) => o.id !== order.id);
                    }
                }
            } catch (e) {
                this.showError(e?.message || this.i18n.error_network);
            }
            item._loading = false;
        },

        isPreparing(order) {
            return ['preparing', 'processing', 'cooking', 'in_progress'].includes(order.status);
        },

        statusLabel(status) {
            const map = {
                preparing:   this.i18n.status_preparing,
                processing:  this.i18n.status_preparing,
                cooking:     this.i18n.status_preparing,
                in_progress: this.i18n.status_preparing,
            };
            return map[status] ?? status;
        },

        timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diff < 60)  return diff + this.i18n.seconds_ago;
            if (diff < 3600) return Math.floor(diff / 60) + this.i18n.minutes_ago;
            return Math.floor(diff / 3600) + this.i18n.hours_ago;
        },

        waitMinutes(order) {
            if (!order?.created_at) {
                return 0;
            }

            return Math.max(0, Math.floor((Date.now() - new Date(order.created_at).getTime()) / 60000));
        },

        waitBadgeClass(order) {
            return '';
        },

        waitBadgeStyle(order) {
            const [r, g, b] = this.waitColor(order);
            return `background-color: rgba(${r}, ${g}, ${b}, 0.18); color: rgb(${r}, ${g}, ${b}); border: 1px solid rgba(${r}, ${g}, ${b}, 0.48);`;
        },

        waitCardStyle(order) {
            const [r, g, b] = this.waitColor(order);
            return `border-color: rgba(${r}, ${g}, ${b}, 0.68); background-color: rgba(${r}, ${g}, ${b}, 0.22);`;
        },

        waitColor(order) {
            const minutes = this.waitMinutes(order);
            const cfg = this.normalizedWaitConfig();
            const green = [16, 185, 129];
            const orange = [249, 115, 22];
            const red = [239, 68, 68];
            const deepRed = [190, 24, 93];

            if (minutes < cfg.orangeStart) return green;
            if (minutes < cfg.orangeEnd) {
                return this.lerpColor(green, orange, (minutes - cfg.orangeStart) / Math.max(1, cfg.orangeEnd - cfg.orangeStart));
            }
            if (minutes < cfg.redEnd) {
                return this.lerpColor(orange, red, (minutes - cfg.orangeEnd) / Math.max(1, cfg.redEnd - cfg.orangeEnd));
            }

            const overRatio = Math.min((minutes - cfg.redEnd) / Math.max(1, cfg.redEnd), 1);
            return this.lerpColor(red, deepRed, overRatio);
        },

        lerpColor(from, to, ratio) {
            const t = Math.min(1, Math.max(0, ratio));
            return [
                Math.round(from[0] + (to[0] - from[0]) * t),
                Math.round(from[1] + (to[1] - from[1]) * t),
                Math.round(from[2] + (to[2] - from[2]) * t),
            ];
        },

        normalizedWaitConfig() {
            const start = Math.max(0, Number(this.waitConfig.orangeStart) || 0);
            const orangeEnd = Math.max(start + 1, Number(this.waitConfig.orangeEnd) || 5);
            const redEnd = Math.max(orangeEnd + 1, Number(this.waitConfig.redEnd) || 10);

            return { orangeStart: start, orangeEnd, redEnd };
        },

        saveWaitConfig() {
            const normalized = this.normalizedWaitConfig();
            this.waitConfig = normalized;
            localStorage.setItem(this.waitConfigKey, JSON.stringify(normalized));
        },

        loadWaitConfig() {
            try {
                const raw = localStorage.getItem(this.waitConfigKey);
                if (!raw) {
                    this.waitConfig = this.normalizedWaitConfig();
                    return;
                }
                const parsed = JSON.parse(raw);
                this.waitConfig = {
                    orangeStart: Number(parsed?.orangeStart ?? 1),
                    orangeEnd: Number(parsed?.orangeEnd ?? 5),
                    redEnd: Number(parsed?.redEnd ?? 10),
                };
                this.waitConfig = this.normalizedWaitConfig();
            } catch {
                this.waitConfig = { orangeStart: 1, orangeEnd: 5, redEnd: 10 };
            }
        },
    };
}
</script>

@endsection
