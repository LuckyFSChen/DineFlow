@extends('layouts.app')

@section('title', __('admin.board_cashier_title') . ' — ' . $store->name)

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
function cashierFormatOrder(\App\Models\Order $order): array {
    return [
        'id'             => $order->id,
        'order_no'       => $order->order_no,
        'order_locale'   => $order->order_locale,
        'status'         => $order->status,
        'payment_status' => $order->payment_status,
        'order_type'     => $order->order_type,
        'note'           => $order->note,
        'customer_name'  => $order->customer_name,
        'created_at'     => $order->created_at?->toIso8601String(),
        'table'          => ($t = $order->getRelation('table')) ? ['table_no' => $t->table_no] : null,
        'items'          => $order->items->map(fn($i) => [
            'id'             => $i->id,
            'product_name'   => $i->product_name,
            'qty'            => $i->qty,
            'note'           => $i->note,
            'option_summary' => null,
        ])->values()->all(),
        '_loading' => false,
    ];
}
$ordersData = $orders->map(fn($o) => cashierFormatOrder($o))->values()->all();
$defaultCancelQuickReasons = __('admin.board_cancel_quick_reasons');
if (! is_array($defaultCancelQuickReasons)) {
    $defaultCancelQuickReasons = [];
}
$storeCancelQuickReasons = collect(is_array($store->cancel_quick_reasons) ? $store->cancel_quick_reasons : [])
    ->map(fn($reason) => trim((string) $reason))
    ->filter(fn($reason) => $reason !== '')
    ->values()
    ->all();
$cashierI18n = [
    'order_unit' => __('admin.board_order_unit'),
    'next_refresh' => __('admin.board_next_refresh'),
    'not_updated_yet' => __('admin.board_not_updated_yet'),
    'waiting_prefix' => __('admin.board_waiting_prefix'),
    'locale_prefix' => __('admin.board_locale_prefix'),
    'processing' => __('admin.board_processing'),
    'accept_prepay' => __('admin.board_action_accept_prepay'),
    'accept_postpay' => __('admin.board_action_accept_postpay'),
    'cancel_order' => __('admin.board_action_cancel_order'),
    'mark_paid' => __('admin.board_action_mark_paid'),
    'error_update_failed' => __('admin.board_error_update_failed'),
    'error_missing_csrf' => __('admin.board_error_missing_csrf'),
    'error_network' => __('admin.board_error_network'),
    'cancel_dialog_title' => __('admin.board_cancel_dialog_title'),
    'cancel_dialog_hint' => __('admin.board_cancel_dialog_hint'),
    'cancel_shortcuts_label' => __('admin.board_cancel_shortcuts_label'),
    'cancel_selected_label' => __('admin.board_cancel_selected_label'),
    'cancel_other_label' => __('admin.board_cancel_other_label'),
    'cancel_other_placeholder' => __('admin.board_cancel_other_placeholder'),
    'cancel_confirm' => __('admin.board_cancel_confirm'),
    'cancel_close' => __('admin.board_cancel_close'),
    'cancel_reason_required' => __('admin.board_cancel_reason_required'),
    'cancel_quick_reasons' => $storeCancelQuickReasons !== [] ? $storeCancelQuickReasons : $defaultCancelQuickReasons,
    'status_unpaid_collect' => __('admin.board_status_unpaid_collect'),
    'status_pending' => __('admin.board_status_pending'),
    'status_accepted' => __('admin.board_status_accepted'),
    'status_confirmed' => __('admin.board_status_confirmed'),
    'status_received' => __('admin.board_status_received'),
    'seconds_ago' => __('admin.board_time_seconds_ago'),
    'minutes_ago' => __('admin.board_time_minutes_ago'),
    'hours_ago' => __('admin.board_time_hours_ago'),
];
@endphp

@section('content')
<div class="min-h-screen bg-slate-900 text-white" x-data="cashierBoard()" x-init="init()">

    {{-- Header --}}
    <div class="sticky top-0 z-20 border-b border-slate-700 bg-slate-900/95 px-4 py-3 backdrop-blur sm:px-6">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700">
                ← {{ __('admin.board_back_to_stores') }}
            </a>
            <div>
                <h1 class="text-lg font-bold text-white">💳 {{ __('admin.board_cashier_title') }}</h1>
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
                            <option value="{{ route('admin.stores.cashier', ['store' => $storeRouteValue($availableStore)]) }}" @selected((int) $availableStore->id === (int) $store->id)>
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

            {{-- Status filter --}}
            <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">{{ __('admin.board_filter_all') }}</button>
                <button @click="filter = 'pending'" :class="filter === 'pending' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">{{ __('admin.board_status_pending') }}</button>
                <button @click="filter = 'unpaid'" :class="filter === 'unpaid' ? 'bg-emerald-500 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">{{ __('admin.board_status_unpaid_collect') }}</button>
            </div>

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
                <p class="text-[11px] text-slate-400">{{ __('admin.board_status_pending') }}</p>
                <p class="text-lg font-bold text-amber-300" x-text="pendingCount"></p>
            </div>
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">{{ __('admin.board_stat_unpaid') }}</p>
                <p class="text-lg font-bold text-emerald-300" x-text="unpaidCompletedCount"></p>
            </div>
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">{{ __('admin.board_last_updated') }}</p>
                <p class="board-last-updated-text text-xs text-slate-300 tabular-nums" x-text="lastUpdatedText"></p>
                <p class="board-next-refresh-block mt-1 text-sm font-semibold text-indigo-200 tabular-nums" x-text="i18n.next_refresh + nextRefreshIn + 's'"></p>
            </div>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="filteredOrders.length === 0 && !loading" class="flex flex-col items-center justify-center py-32 text-slate-500">
        <svg class="mb-4 h-16 w-16 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-xl font-semibold">{{ __('admin.board_empty_pending') }}</p>
        <p class="mt-1 text-sm">{{ __('admin.board_empty_auto') }}</p>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-600 border-t-indigo-400"></div>
    </div>

    {{-- Order grid --}}
    <div x-show="!loading" class="grid gap-4 p-4 sm:p-6" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <template x-for="order in filteredOrders" :key="order.id">
            <div class="flex flex-col rounded-2xl border overflow-hidden transition-all duration-300"
                 :style="waitCardStyle(order)"
                 :class="{
                    'bg-amber-950/30': isPending(order),
                    'bg-emerald-950/20': isUnpaidCompleted(order)
                 }">

                {{-- Order card header --}}
                <div class="flex items-start justify-between px-4 pt-4 pb-3 border-b"
                     :class="{
                        'border-amber-700/40': isPending(order),
                        'border-emerald-700/40': isUnpaidCompleted(order)
                     }">
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
                            👤 <span x-text="order.customer_name"></span>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-1.5">
                        {{-- Status badge --}}
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                              :class="{
                                'bg-amber-500/20 text-amber-300': isPending(order),
                                'bg-emerald-500/20 text-emerald-300': isUnpaidCompleted(order)
                              }"
                              x-text="statusLabel(order.status, order.payment_status)">
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
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-white"
                                      x-text="item.qty + '×'"></span>
                                <div>
                                    <span class="text-sm font-semibold text-white" x-text="item.product_name"></span>
                                    <div x-show="item.note" class="mt-0.5 flex items-center gap-1 text-xs text-yellow-400">
                                        <span>📝</span>
                                        <span x-text="item.note"></span>
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

                {{-- Action buttons --}}
                <div class="flex gap-2 border-t border-slate-700/50 px-4 py-3">
                    {{-- Accept: pending → preparing (enters kitchen board) --}}
                    <template x-if="isPending(order)">
                        <div class="flex w-full gap-2">
                            <button
                                @click="acceptOrder(order)"
                                :disabled="order._loading"
                                class="flex-1 rounded-xl px-3 py-2 text-xs font-semibold text-white transition disabled:opacity-50"
                                :class="checkoutTiming === 'prepay' ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-blue-600 hover:bg-blue-500'"
                                x-text="order._loading ? i18n.processing : (checkoutTiming === 'prepay' ? i18n.accept_prepay : i18n.accept_postpay)">
                            </button>

                            <button
                                @click="openCancelDialog(order)"
                                :disabled="order._loading"
                                class="flex-1 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-500 disabled:opacity-50">
                                <span x-text="order._loading ? i18n.processing : i18n.cancel_order"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Collect payment: completed unpaid order --}}
                    <template x-if="isUnpaidCompleted(order)">
                        <button
                            @click="setStatus(order, 'paid')"
                            :disabled="order._loading"
                            class="flex-1 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-50">
                            <span x-text="order._loading ? i18n.processing : i18n.mark_paid"></span>
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Cancel reason modal --}}
    <div
        x-show="cancelModalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 p-4"
        @keydown.escape.window="closeCancelDialog()">
        <div
            class="w-full max-w-xl rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
            @click.outside="closeCancelDialog()">
            <div class="border-b border-slate-700 px-5 py-4">
                <h3 class="text-base font-semibold text-white" x-text="i18n.cancel_dialog_title"></h3>
                <p class="mt-1 text-xs text-slate-400" x-text="i18n.cancel_dialog_hint"></p>
            </div>

            <div class="space-y-4 px-5 py-4">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="i18n.cancel_shortcuts_label"></p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="reason in cancelQuickReasons" :key="reason">
                            <button
                                type="button"
                                @click="toggleCancelReason(reason)"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                                :class="selectedCancelReasons.includes(reason)
                                    ? 'border-rose-400 bg-rose-500/20 text-rose-200'
                                    : 'border-slate-600 bg-slate-800 text-slate-300 hover:border-slate-500 hover:bg-slate-700'"
                                x-text="reason">
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="i18n.cancel_other_label"></p>
                    <textarea
                        x-model.trim="cancelReasonOther"
                        rows="3"
                        :placeholder="i18n.cancel_other_placeholder"
                        class="w-full rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-rose-500 focus:outline-none"></textarea>
                </div>

                <p class="text-xs text-slate-500" x-text="i18n.cancel_selected_label + selectedCancelReasons.length"></p>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-700 px-5 py-4">
                <button
                    type="button"
                    @click="closeCancelDialog()"
                    class="rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-slate-800"
                    x-text="i18n.cancel_close"></button>

                <button
                    type="button"
                    @click="confirmCancelOrder()"
                    class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-500"
                    x-text="i18n.cancel_confirm"></button>
            </div>
        </div>
    </div>

    {{-- New order toast --}}
        <div x-show="newOrderAlert"
            x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border border-emerald-500/50 bg-emerald-900 px-5 py-3 shadow-xl">
        <span class="text-2xl">🔔</span>
        <div>
            <p class="font-bold text-white">{{ __('admin.board_new_order_arrived') }}</p>
            <p class="text-xs text-emerald-300">{{ __('admin.board_new_order_added') }}</p>
        </div>
    </div>

        <div x-show="errorMessage"
            x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-6 left-6 z-50 max-w-md rounded-2xl border border-rose-500/50 bg-rose-900 px-5 py-3 text-sm text-rose-100 shadow-xl">
        <p class="font-semibold">{{ __('admin.board_operation_failed') }}</p>
        <p class="mt-1" x-text="errorMessage"></p>
    </div>
</div>

<script>
function cashierBoard() {
    return {
        orders: @json($ordersData),
        statusUrlTemplate: @json(route('admin.stores.cashier.orders.status', ['store' => $storeRoute, 'order' => '__ORDER__'])),
        checkoutTiming: @json($checkoutTiming ?? 'postpay'),
        i18n: @json($cashierI18n),
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
        cancelModalOpen: false,
        cancelTargetOrder: null,
        cancelQuickReasons: [],
        selectedCancelReasons: [],
        cancelReasonOther: '',

        get filteredOrders() {
            let result = [...this.orders];

            if (this.filter === 'pending') result = result.filter(o => this.isPending(o));
            if (this.filter === 'unpaid')  result = result.filter(o => this.isUnpaidCompleted(o));

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

        get pendingCount() {
            return this.orders.filter((o) => this.isPending(o)).length;
        },

        get unpaidCompletedCount() {
            return this.orders.filter((o) => this.isUnpaidCompleted(o)).length;
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
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            const ss = String(d.getSeconds()).padStart(2, '0');
            return `${hh}:${mm}:${ss}`;
        },

        init() {
            clearInterval(this._pollTimer);
            clearInterval(this._countdownTimer);
            this.loadWaitConfig();
            this.initAudio();
            this.cancelQuickReasons = Array.isArray(this.i18n.cancel_quick_reasons)
                ? this.i18n.cancel_quick_reasons
                : [];
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
            if (!AudioCtx) return;
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
                const res = await fetch('{{ route('admin.stores.cashier.orders', ['store' => $storeRoute]) }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const fresh = await res.json();
                if (!Array.isArray(fresh)) {
                    this.showError(this.i18n.error_update_failed);
                    return;
                }
                const oldIds = new Set(this.orders.map(o => o.id));
                const hasNew = fresh.some(o => !oldIds.has(o.id));
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
            if (!this._audioContext) return;
            const now = this._audioContext.currentTime;
            const playBeep = (start, freq, dur) => {
                const osc  = this._audioContext.createOscillator();
                const gain = this._audioContext.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.12, start + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + dur);
                osc.connect(gain);
                gain.connect(this._audioContext.destination);
                osc.start(start);
                osc.stop(start + dur);
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

        // Accept a pending order → send to kitchen board
        acceptOrder(order) {
            this.setStatus(order, 'preparing');
        },

        openCancelDialog(order) {
            if (!order || order._loading) {
                return;
            }

            this.cancelTargetOrder = order;
            this.selectedCancelReasons = [];
            this.cancelReasonOther = '';
            this.cancelModalOpen = true;
        },

        closeCancelDialog() {
            this.cancelModalOpen = false;
            this.cancelTargetOrder = null;
            this.selectedCancelReasons = [];
            this.cancelReasonOther = '';
        },

        toggleCancelReason(reason) {
            const idx = this.selectedCancelReasons.indexOf(reason);

            if (idx >= 0) {
                this.selectedCancelReasons.splice(idx, 1);
                return;
            }

            this.selectedCancelReasons.push(reason);
        },

        confirmCancelOrder() {
            if (!this.cancelTargetOrder) {
                return;
            }

            const customReason = String(this.cancelReasonOther || '').trim();

            if (this.selectedCancelReasons.length === 0 && customReason === '') {
                this.showError(this.i18n.cancel_reason_required);
                return;
            }

            const order = this.cancelTargetOrder;
            const payload = {
                cancel_reason_options: [...this.selectedCancelReasons],
                cancel_reason_other: customReason,
                cancelReasonOptions: [...this.selectedCancelReasons],
                cancelReasonOther: customReason,
            };

            this.closeCancelDialog();
            this.setStatus(order, 'cancelled', payload);
        },

        async setStatus(order, status, payload = {}) {
            order._loading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!token) {
                    this.showError(this.i18n.error_missing_csrf);
                    order._loading = false;
                    return;
                }
                const url = this.statusUrlTemplate.replace('__ORDER__', String(order.id));
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ status, ...payload }),
                });

                if (!res.ok) {
                    let msg = `HTTP ${res.status}`;
                    try { const d = await res.json(); msg = d.message || msg; } catch {}
                    this.showError(msg);
                    order._loading = false;
                    return;
                }

                // Remove order from cashier board in both cases:
                // - accepted (preparing): it moves to kitchen board
                // - paid: payment collected, done
                // - cancelled: order cancelled
                this.orders = this.orders.filter(o => o.id !== order.id);

            } catch (e) {
                this.showError(e?.message || this.i18n.error_network);
            }
            order._loading = false;
        },

        isPending(order) {
            return ['pending', 'accepted', 'confirmed', 'received'].includes(order.status);
        },

        isUnpaidCompleted(order) {
            return ['complete', 'completed', 'ready', 'ready_for_pickup'].includes(order.status)
                && (!order.payment_status || order.payment_status === 'unpaid');
        },

        statusLabel(status, paymentStatus) {
            if (['complete', 'completed', 'ready', 'ready_for_pickup'].includes(status)
                && (!paymentStatus || paymentStatus === 'unpaid')) {
                return this.i18n.status_unpaid_collect;
            }
            const map = {
                pending:    this.i18n.status_pending,
                accepted:   this.i18n.status_accepted,
                confirmed:  this.i18n.status_confirmed,
                received:   this.i18n.status_received,
            };
            return map[status] ?? status;
        },

        timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diff < 60)   return diff + this.i18n.seconds_ago;
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
