@extends('layouts.app')

@section('title', __('admin.board_all_title') . ' — ' . $store->name)

@php
$defaultCancelQuickReasons = __('admin.board_cancel_quick_reasons');
if (! is_array($defaultCancelQuickReasons)) {
    $defaultCancelQuickReasons = [];
}
$storeCancelQuickReasons = collect(is_array($store->cancel_quick_reasons) ? $store->cancel_quick_reasons : [])
    ->map(fn($reason) => trim((string) $reason))
    ->filter(fn($reason) => $reason !== '')
    ->values()
    ->all();

$allBoardsI18n = [
    'order_unit' => __('admin.board_order_unit'),
    'waiting_prefix' => __('admin.board_waiting_prefix'),
    'locale_prefix' => __('admin.board_locale_prefix'),
    'accept_prepay' => __('admin.board_action_accept_prepay'),
    'accept_postpay' => __('admin.board_action_accept_postpay'),
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
    'label_kitchen' => __('admin.board_label_kitchen'),
    'label_cashier' => __('admin.board_label_cashier'),
    'status_preparing' => __('admin.board_status_preparing'),
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
<div class="min-h-screen bg-slate-900 text-white" x-data="allBoards()" x-init="init()">

    <div class="sticky top-0 z-20 border-b border-slate-700 bg-slate-900/95 px-4 py-3 backdrop-blur sm:px-6">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700">
                ← {{ __('admin.board_back_to_stores') }}
            </a>
            <div>
                <h1 class="text-lg font-bold text-white">🧩 {{ __('admin.board_all_title') }}</h1>
                <p class="text-xs text-slate-400">{{ $store->name }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                @if($store->is_active)
                    <a href="{{ route('admin.stores.cashier', $store) }}" class="px-3 py-1.5 text-slate-300 transition hover:bg-slate-700">
                        💳 {{ __('admin.board_cashier_title') }}
                    </a>
                    <a href="{{ route('admin.stores.kitchen', $store) }}" class="px-3 py-1.5 text-slate-300 transition hover:bg-slate-700">
                        🍳 {{ __('admin.board_kitchen_title') }}
                    </a>
                @endif
                <span class="px-3 py-1.5 bg-indigo-600 text-white">🧩 {{ __('admin.board_all_title') }}</span>
            </div>

            @if(($availableStores ?? collect())->count() > 1)
                <div class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-2 py-1.5 text-xs">
                    <span class="text-slate-400">{{ __('admin.board_store') }}</span>
                    <select
                        class="rounded border border-slate-600 bg-slate-900 px-2 py-1 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none"
                        onchange="if (this.value) window.location.href = this.value;">
                        @foreach($availableStores as $availableStore)
                            <option value="{{ route('admin.stores.boards', $availableStore) }}" @selected((int) $availableStore->id === (int) $store->id)>
                                {{ $availableStore->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                <button @click="boardFilter = 'all'" :class="boardFilter === 'all' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">{{ __('admin.board_filter_all') }}</button>
                <button @click="boardFilter = 'cashier'" :class="boardFilter === 'cashier' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">{{ __('admin.board_label_cashier') }}</button>
                <button @click="boardFilter = 'kitchen'" :class="boardFilter === 'kitchen' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">{{ __('admin.board_label_kitchen') }}</button>
            </div>

            <div class="flex items-center gap-1.5 rounded-full border border-emerald-700 bg-emerald-900/50 px-3 py-1 text-xs text-emerald-400">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                {{ __('admin.board_live_updating') }}
            </div>

            <span class="rounded-full bg-indigo-600 px-3 py-0.5 text-xs font-bold" x-text="filteredOrders.length + ' ' + i18n.order_unit"></span>
        </div>
        </div>
    </div>

    <div x-show="filteredOrders.length === 0 && !loading" class="flex flex-col items-center justify-center py-32 text-slate-500">
        <svg class="mb-4 h-16 w-16 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-xl font-semibold">{{ __('admin.board_empty_pending') }}</p>
        <p class="mt-1 text-sm">{{ __('admin.board_empty_auto') }}</p>
    </div>

    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-600 border-t-indigo-400"></div>
    </div>

    <div x-show="!loading" class="grid gap-4 p-6" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <template x-for="order in filteredOrders" :key="order.id">
            <div class="flex flex-col rounded-2xl border overflow-hidden transition-all duration-300"
                 :class="cardClass(order)">

                <div class="flex items-start justify-between px-4 pt-4 pb-3 border-b" :class="cardHeaderClass(order)">
                    <div>
                        <span class="font-mono text-lg font-bold text-white" x-text="'#' + (order.order_no || order.id)"></span>
                        <div class="mt-0.5 flex items-center gap-2 text-xs">
                                <span class="text-slate-500" x-text="timeAgo(order.created_at)"></span>
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                    :class="waitBadgeClass(order)"
                                    x-text="i18n.waiting_prefix + waitMinutes(order) + 'm'"></span>
                                <span class="rounded border border-sky-500/40 bg-sky-900/40 px-1.5 py-0.5 text-[10px] font-semibold text-sky-300"
                                    x-text="i18n.locale_prefix + localeLabel(order.order_locale)"></span>
                        </div>
                        <div x-show="order.customer_name" class="mt-0.5 text-xs text-slate-400">
                            👤 <span x-text="order.customer_name"></span>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-1.5">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="boardBadgeClass(order)" x-text="boardLabel(order)"></span>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusBadgeClass(order)" x-text="statusLabel(order)"></span>
                        <template x-if="order.order_type === 'dine_in' && order.table">
                            <span class="rounded bg-slate-700 px-2.5 py-1 text-sm font-semibold text-slate-200">{{ __('admin.board_table_no') }} <span x-text="order.table.table_no"></span></span>
                        </template>
                        <template x-if="order.order_type === 'takeout' || order.order_type === 'take_out'">
                            <span class="rounded bg-orange-800/60 px-2.5 py-1 text-sm font-semibold text-orange-200">{{ __('admin.board_takeout') }}</span>
                        </template>
                    </div>
                </div>

                <div class="flex-1 px-4 py-3 space-y-2">
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-white" x-text="item.qty + '×'"></span>
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

                <template x-if="order.note">
                    <div class="mx-4 mb-3 rounded-lg bg-yellow-900/30 border border-yellow-700/30 px-3 py-2 text-xs text-yellow-300">
                        <span class="font-semibold">{{ __('admin.board_note_label') }}</span><span x-text="order.note"></span>
                    </div>
                </template>

                <div class="border-t border-slate-700/50 px-4 py-3">
                    <div class="flex gap-2" x-show="canAccept(order) || canCancel(order) || canCollect(order) || canComplete(order)">
                        <button x-show="canAccept(order)"
                                @click="updateOrder(order, 'preparing')"
                                :disabled="order._loading"
                                class="flex-1 rounded-xl px-3 py-2 text-xs font-semibold text-white transition disabled:opacity-50"
                                :class="checkoutTiming === 'prepay' ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-blue-600 hover:bg-blue-500'"
                                x-text="checkoutTiming === 'prepay' ? i18n.accept_prepay : i18n.accept_postpay">
                        </button>

                        <button x-show="canCancel(order)"
                                @click="openCancelDialog(order)"
                                :disabled="order._loading"
                                class="flex-1 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-500 disabled:opacity-50">
                            {{ __('admin.board_action_cancel_order') }}
                        </button>

                        <button x-show="canCollect(order)"
                                @click="updateOrder(order, 'paid')"
                                :disabled="order._loading"
                                class="flex-1 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-50">
                            {{ __('admin.board_action_mark_paid') }}
                        </button>

                        <button x-show="canComplete(order)"
                                @click="updateOrder(order, 'completed')"
                                :disabled="order._loading"
                                class="flex-1 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-50">
                            {{ __('admin.board_action_mark_completed') }}
                        </button>
                    </div>
                    <p x-show="!canAccept(order) && !canCancel(order) && !canCollect(order) && !canComplete(order)"
                       class="text-xs text-slate-400">
                        {{ __('admin.board_no_available_actions') }}
                    </p>
                </div>
            </div>
        </template>
    </div>

    <div
        x-show="cancelModalOpen"
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

    <div x-show="newOrderAlert"
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
            <p class="text-xs text-emerald-300">{{ __('admin.board_new_order_added_all') }}</p>
        </div>
    </div>

    <div x-show="errorMessage"
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
function allBoards() {
    return {
        orders: @json($ordersData),
        boardFilter: 'all',
        checkoutTiming: @json($checkoutTiming ?? 'postpay'),
        canCashierActions: @json($canCashierActions),
        canKitchenActions: @json($canKitchenActions),
        cashierOrdersUrl: @json(route('admin.stores.cashier.orders', $store)),
        kitchenOrdersUrl: @json(route('admin.stores.kitchen.orders', $store)),
        cashierStatusUrlTemplate: @json(route('admin.stores.cashier.orders.status', ['store' => $store, 'order' => '__ORDER__'])),
        kitchenStatusUrlTemplate: @json(route('admin.stores.kitchen.orders.status', ['store' => $store, 'order' => '__ORDER__'])),
        i18n: @json($allBoardsI18n),
        loading: false,
        newOrderAlert: false,
        errorMessage: '',
        _pollTimer: null,
        _alertTimer: null,
        _errorTimer: null,
        cancelModalOpen: false,
        cancelTargetOrder: null,
        cancelQuickReasons: [],
        selectedCancelReasons: [],
        cancelReasonOther: '',

        get filteredOrders() {
            if (this.boardFilter === 'cashier') {
                return this.orders.filter((o) => o.board === 'cashier');
            }
            if (this.boardFilter === 'kitchen') {
                return this.orders.filter((o) => o.board === 'kitchen');
            }
            return this.orders;
        },

        init() {
            this.cancelQuickReasons = Array.isArray(this.i18n.cancel_quick_reasons)
                ? this.i18n.cancel_quick_reasons
                : [];
            this._pollTimer = setInterval(() => this.poll(), 10000);
        },

        openCancelDialog(order) {
            if (!order || order._loading || !this.canCancel(order)) {
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
            this.updateOrder(order, 'cancelled', payload);
        },

        async poll() {
            try {
                const [cashierRes, kitchenRes] = await Promise.all([
                    fetch(this.cashierOrdersUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }),
                    fetch(this.kitchenOrdersUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }),
                ]);

                if (!cashierRes.ok || !kitchenRes.ok) {
                    return;
                }

                const cashierOrders = (await cashierRes.json()).map((o) => ({ ...o, board: 'cashier', _loading: false }));
                const kitchenOrders = (await kitchenRes.json()).map((o) => ({ ...o, board: 'kitchen', _loading: false }));
                const merged = this.mergeOrders(cashierOrders, kitchenOrders);

                const oldIds = new Set(this.orders.map((o) => `${o.board}-${o.id}`));
                const hasNew = merged.some((o) => !oldIds.has(`${o.board}-${o.id}`));

                this.orders = merged;

                if (hasNew) {
                    this.showAlert();
                }
            } catch {}
        },

        mergeOrders(cashierOrders, kitchenOrders) {
            const map = new Map();
            [...cashierOrders, ...kitchenOrders].forEach((order) => {
                map.set(`${order.board}-${order.id}`, order);
            });

            return [...map.values()].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        },

        async updateOrder(order, status, payload = {}) {
            order._loading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!token) {
                    this.showError(this.i18n.error_missing_csrf);
                    order._loading = false;
                    return;
                }

                const useKitchen = status === 'completed';
                const template = useKitchen ? this.kitchenStatusUrlTemplate : this.cashierStatusUrlTemplate;
                const url = template.replace('__ORDER__', String(order.id));

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
                    try {
                        const data = await res.json();
                        msg = data.message || msg;
                    } catch {}
                    this.showError(msg);
                    order._loading = false;
                    return;
                }

                await this.poll();
            } catch (e) {
                this.showError(e?.message || this.i18n.error_network);
            }
            order._loading = false;
        },

        canAccept(order) {
            return this.canCashierActions && this.isPending(order);
        },

        canCancel(order) {
            return this.canCashierActions && this.isPending(order);
        },

        canCollect(order) {
            return this.canCashierActions && this.isUnpaidCompleted(order);
        },

        canComplete(order) {
            return this.canKitchenActions && this.isPreparing(order);
        },

        isPending(order) {
            return ['pending', 'accepted', 'confirmed', 'received'].includes(order.status);
        },

        isUnpaidCompleted(order) {
            return ['complete', 'completed', 'ready', 'ready_for_pickup'].includes(order.status)
                && (!order.payment_status || order.payment_status === 'unpaid');
        },

        isPreparing(order) {
            return ['preparing', 'processing', 'cooking', 'in_progress'].includes(order.status);
        },

        boardLabel(order) {
            return order.board === 'kitchen' ? this.i18n.label_kitchen : this.i18n.label_cashier;
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

        cardClass(order) {
            const waitBorderClass = this.waitBorderClass(order);

            if (this.isPreparing(order)) {
                return `${waitBorderClass} bg-blue-950/30`;
            }
            if (this.isUnpaidCompleted(order)) {
                return `${waitBorderClass} bg-emerald-950/20`;
            }
            return `${waitBorderClass} bg-amber-950/30`;
        },

        waitMinutes(order) {
            if (!order?.created_at) {
                return 0;
            }

            return Math.max(0, Math.floor((Date.now() - new Date(order.created_at).getTime()) / 60000));
        },

        waitBorderClass(order) {
            const minutes = this.waitMinutes(order);

            if (minutes >= 10) return 'border-rose-500/70';
            if (minutes >= 5) return 'border-orange-400/70';

            if (this.isPreparing(order)) {
                return 'border-blue-500/50';
            }
            if (this.isUnpaidCompleted(order)) {
                return 'border-emerald-500/50';
            }
            return 'border-amber-500/50';
        },

        waitBadgeClass(order) {
            const minutes = this.waitMinutes(order);

            if (minutes >= 10) return 'bg-rose-500/20 text-rose-300';
            if (minutes >= 5) return 'bg-orange-500/20 text-orange-300';
            return 'bg-emerald-500/20 text-emerald-300';
        },

        cardHeaderClass(order) {
            if (this.isPreparing(order)) {
                return 'border-blue-700/40';
            }
            if (this.isUnpaidCompleted(order)) {
                return 'border-emerald-700/40';
            }
            return 'border-amber-700/40';
        },

        boardBadgeClass(order) {
            return order.board === 'kitchen'
                ? 'bg-blue-500/20 text-blue-300'
                : 'bg-amber-500/20 text-amber-300';
        },

        statusBadgeClass(order) {
            if (this.isPreparing(order)) {
                return 'bg-blue-500/20 text-blue-300';
            }
            if (this.isUnpaidCompleted(order)) {
                return 'bg-emerald-500/20 text-emerald-300';
            }
            return 'bg-amber-500/20 text-amber-300';
        },

        statusLabel(order) {
            if (this.isPreparing(order)) {
                return this.i18n.status_preparing;
            }
            if (this.isUnpaidCompleted(order)) {
                return this.i18n.status_unpaid_collect;
            }
            const map = {
                pending: this.i18n.status_pending,
                accepted: this.i18n.status_accepted,
                confirmed: this.i18n.status_confirmed,
                received: this.i18n.status_received,
            };
            return map[order.status] ?? order.status;
        },

        showAlert() {
            clearTimeout(this._alertTimer);
            this.newOrderAlert = true;
            this._alertTimer = setTimeout(() => { this.newOrderAlert = false; }, 4000);
        },

        showError(message) {
            clearTimeout(this._errorTimer);
            this.errorMessage = message || this.i18n.error_update_failed;
            this._errorTimer = setTimeout(() => { this.errorMessage = ''; }, 5000);
        },

        timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diff < 60) {
                return `${diff}${this.i18n.seconds_ago}`;
            }
            if (diff < 3600) {
                return `${Math.floor(diff / 60)}${this.i18n.minutes_ago}`;
            }
            return `${Math.floor(diff / 3600)}${this.i18n.hours_ago}`;
        },
    };
}
</script>
@endsection
