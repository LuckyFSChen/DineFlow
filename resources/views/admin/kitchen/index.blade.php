@extends('layouts.app')

@section('title', __('admin.board_kitchen_title') . ' — ' . $store->name)

@php
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
            'option_summary' => null,
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
    'processing' => __('admin.board_processing'),
    'mark_completed' => __('admin.board_action_mark_completed'),
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
                ← {{ __('admin.board_back_to_stores') }}
            </a>
            <div>
                <h1 class="text-lg font-bold text-white">🍳 {{ __('admin.board_kitchen_title') }}</h1>
                <p class="text-xs text-slate-400">{{ $store->name }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            {{-- Board switch --}}
            <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                <a href="{{ route('admin.stores.cashier', $store) }}"
                   class="px-3 py-1.5 text-slate-300 transition hover:bg-slate-700">
                    💳 {{ __('admin.board_cashier_title') }}
                </a>
                <span class="px-3 py-1.5 bg-indigo-600 text-white">🍳 {{ __('admin.board_kitchen_title') }}</span>
                <a href="{{ route('admin.stores.boards', $store) }}"
                   class="px-3 py-1.5 text-slate-300 transition hover:bg-slate-700">
                    🧩 {{ __('admin.board_all_title') }}
                </a>
            </div>

            @if(($availableStores ?? collect())->count() > 1)
                <div class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-2 py-1.5 text-xs">
                    <span class="text-slate-400">{{ __('admin.board_store') }}</span>
                    <select
                        class="rounded border border-slate-600 bg-slate-900 px-2 py-1 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none"
                        onchange="if (this.value) window.location.href = this.value;">
                        @foreach($availableStores as $availableStore)
                            <option value="{{ route('admin.stores.kitchen', $availableStore) }}" @selected((int) $availableStore->id === (int) $store->id)>
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
            <span class="rounded-full border border-slate-700 bg-slate-800 px-3 py-0.5 text-xs text-slate-300" x-text="i18n.next_refresh + nextRefreshIn + 's'"></span>
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
                <p class="text-sm font-semibold text-slate-100" x-text="lastUpdatedText"></p>
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
            <div class="flex flex-col rounded-2xl border overflow-hidden transition-all duration-300 border-blue-500/50 bg-blue-950/30">

                {{-- Order card header --}}
                <div class="flex items-start justify-between px-4 pt-4 pb-3 border-b border-blue-700/40">
                    <div>
                        {{-- Order No --}}
                        <span class="font-mono text-lg font-bold text-white" x-text="'#' + (order.order_no || order.id)"></span>
                        {{-- Table / Type --}}
                        <div class="mt-0.5 flex items-center gap-2 text-xs">
                            <template x-if="order.order_type === 'dine_in' && order.table">
                                <span class="rounded bg-slate-700 px-1.5 py-0.5 text-slate-300">
                                    {{ __('admin.board_table_no') }} <span x-text="order.table.table_no"></span>
                                </span>
                            </template>
                            <template x-if="order.order_type === 'takeout' || order.order_type === 'take_out'">
                                <span class="rounded bg-orange-800/60 px-1.5 py-0.5 text-orange-300">{{ __('admin.board_takeout') }}</span>
                            </template>
                            <span class="text-slate-500" x-text="timeAgo(order.created_at)"></span>
                            <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                  :class="waitBadgeClass(order)"
                                  x-text="i18n.waiting_prefix + waitMinutes(order) + 'm'"></span>
                                <span class="rounded border border-sky-500/40 bg-sky-900/40 px-1.5 py-0.5 text-[10px] font-semibold text-sky-300"
                                    x-text="i18n.locale_prefix + localeLabel(order.order_locale)"></span>
                        </div>
                        {{-- Customer name --}}
                        <div x-show="order.customer_name" class="mt-0.5 text-xs text-slate-400">
                            👤 <span x-text="order.customer_name"></span>
                        </div>
                    </div>

                    {{-- Status badge --}}
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-500/20 text-blue-300">
                        {{ __('admin.board_status_preparing') }}
                    </span>
                </div>

                {{-- Items list --}}
                <div class="flex-1 px-4 py-3 space-y-2">
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-white"
                                      x-text="item.qty + '×'"></span>
                                <div>
                                    <span class="text-sm font-semibold text-white" x-text="item.product_name"></span>
                                    {{-- Item note --}}
                                    <div x-show="item.note" class="mt-0.5 flex items-center gap-1 text-xs text-yellow-400">
                                        <span>📝</span>
                                        <span x-text="item.note"></span>
                                    </div>
                                    {{-- Options --}}
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

                {{-- Action button: complete --}}
                <div class="flex gap-2 border-t border-slate-700/50 px-4 py-3">
                    <button
                        @click="setStatus(order, 'completed')"
                        :disabled="order._loading"
                        class="flex-1 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-50">
                        <span x-text="order._loading ? i18n.processing : i18n.mark_completed"></span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- New order toast --}}
    <div x-show="newOrderAlert" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border border-emerald-500/50 bg-emerald-900 px-5 py-3 shadow-xl">
        <span class="text-2xl">🔔</span>
        <div>
            <p class="font-bold text-white">{{ __('admin.board_new_order_arrived') }}</p>
            <p class="text-xs text-emerald-300">{{ __('admin.board_new_order_added') }}</p>
        </div>
    </div>

    <div x-show="errorMessage" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-6 left-6 z-50 max-w-md rounded-2xl border border-rose-500/50 bg-rose-900 px-5 py-3 text-sm text-rose-100 shadow-xl">
        <p class="font-semibold">{{ __('admin.board_operation_failed') }}</p>
        <p class="mt-1" x-text="errorMessage"></p>
    </div>
</div>

<script>
function kitchenBoard() {
    return {
        orders: @json($ordersData),
        statusUrlTemplate: @json(route('admin.stores.kitchen.orders.status', ['store' => $store, 'order' => '__ORDER__'])),
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
            return this.orders.filter((o) => this.waitMinutes(o) >= 20).length;
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
                const res = await fetch('{{ route('admin.stores.kitchen.orders', $store) }}', {
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

        async setStatus(order, status) {
            order._loading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!token) {
                    this.showError(this.i18n.error_missing_csrf);
                    order._loading = false;
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
                        body: JSON.stringify({ status }),
                    }
                );

                if (!res.ok) {
                    let message = `HTTP ${res.status}`;
                    try {
                        const data = await res.json();
                        message = data.message || message;
                    } catch {}
                    this.showError(message);
                    order._loading = false;
                    return;
                }

                if (res.ok) {
                    const data = await res.json();
                    // Once completed, remove from kitchen board (goes to cashier board)
                    this.orders = this.orders.filter(o => o.id !== order.id);
                }
            } catch (e) {
                this.showError(e?.message || this.i18n.error_network);
            }
            order._loading = false;
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
            const minutes = this.waitMinutes(order);

            if (minutes >= 20) return 'bg-rose-500/20 text-rose-300';
            if (minutes >= 10) return 'bg-amber-500/20 text-amber-300';
            return 'bg-emerald-500/20 text-emerald-300';
        },
    };
}
</script>

@endsection
