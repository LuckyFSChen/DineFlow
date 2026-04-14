@extends('layouts.app')

@section('title', '結帳看板 — ' . $store->name)

@php
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
@endphp

@section('content')
<div class="min-h-screen bg-slate-900 text-white" x-data="cashierBoard()" x-init="init()">

    {{-- Header --}}
    <div class="sticky top-0 z-20 border-b border-slate-700 bg-slate-900/95 px-4 py-3 backdrop-blur sm:px-6">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700">
                ← 回店家管理
            </a>
            <div>
                <h1 class="text-lg font-bold text-white">💳 結帳看板</h1>
                <p class="text-xs text-slate-400">{{ $store->name }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            {{-- Board switch --}}
            <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                <span class="px-3 py-1.5 bg-indigo-600 text-white">💳 結帳看板</span>
                @if($store->is_active)
                    <a href="{{ route('admin.stores.kitchen', $store) }}"
                       class="px-3 py-1.5 text-slate-300 transition hover:bg-slate-700">
                        🍳 後廚看板
                    </a>
                @endif
                <a href="{{ route('admin.stores.boards', $store) }}"
                   class="px-3 py-1.5 text-slate-300 transition hover:bg-slate-700">
                    🧩 所有看板
                </a>
            </div>

            @if(($availableStores ?? collect())->count() > 1)
                <div class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-2 py-1.5 text-xs">
                    <span class="text-slate-400">店家</span>
                    <select
                        class="rounded border border-slate-600 bg-slate-900 px-2 py-1 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none"
                        onchange="if (this.value) window.location.href = this.value;">
                        @foreach($availableStores as $availableStore)
                            <option value="{{ route('admin.stores.cashier', $availableStore) }}" @selected((int) $availableStore->id === (int) $store->id)>
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
                    placeholder="搜尋單號/顧客/桌號"
                    class="w-52 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none">
            </div>

            <button
                @click="refreshNow()"
                type="button"
                class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-slate-700">
                立即更新
            </button>

            {{-- Status filter --}}
            <div class="flex rounded-lg border border-slate-700 overflow-hidden text-xs font-semibold">
                <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">全部</button>
                <button @click="filter = 'pending'" :class="filter === 'pending' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">待接單</button>
                <button @click="filter = 'unpaid'" :class="filter === 'unpaid' ? 'bg-emerald-500 text-white' : 'text-slate-400 hover:bg-slate-700'" class="px-3 py-1.5 transition">代收款</button>
            </div>

            {{-- Live indicator --}}
            <div class="flex items-center gap-1.5 rounded-full border border-emerald-700 bg-emerald-900/50 px-3 py-1 text-xs text-emerald-400">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                即時更新
            </div>

            {{-- Count badge --}}
            <span class="rounded-full bg-indigo-600 px-3 py-0.5 text-xs font-bold" x-text="filteredOrders.length + ' / ' + orders.length + ' 單'"></span>
            <span class="rounded-full border border-slate-700 bg-slate-800 px-3 py-0.5 text-xs text-slate-300" x-text="'下次更新 ' + nextRefreshIn + 's'"></span>
        </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">待接單</p>
                <p class="text-lg font-bold text-amber-300" x-text="pendingCount"></p>
            </div>
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">待收款</p>
                <p class="text-lg font-bold text-emerald-300" x-text="unpaidCompletedCount"></p>
            </div>
            <div class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2">
                <p class="text-[11px] text-slate-400">最後更新</p>
                <p class="text-sm font-semibold text-slate-100" x-text="lastUpdatedText"></p>
            </div>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="filteredOrders.length === 0 && !loading" class="flex flex-col items-center justify-center py-32 text-slate-500">
        <svg class="mb-4 h-16 w-16 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-xl font-semibold">目前沒有待處理訂單</p>
        <p class="mt-1 text-sm">新訂單進來時會自動顯示</p>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-600 border-t-indigo-400"></div>
    </div>

    {{-- Order grid --}}
    <div x-show="!loading" class="grid gap-4 p-4 sm:p-6" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <template x-for="order in filteredOrders" :key="order.id">
            <div class="flex flex-col rounded-2xl border overflow-hidden transition-all duration-300"
                 :class="{
                    'border-amber-500/50 bg-amber-950/30': isPending(order),
                    'border-emerald-500/50 bg-emerald-950/20': isUnpaidCompleted(order)
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
                        {{-- Table / Type --}}
                        <div class="mt-0.5 flex items-center gap-2 text-xs">
                            <template x-if="order.order_type === 'dine_in' && order.table">
                                <span class="rounded bg-slate-700 px-1.5 py-0.5 text-slate-300">
                                    桌號 <span x-text="order.table.table_no"></span>
                                </span>
                            </template>
                            <template x-if="order.order_type === 'takeout' || order.order_type === 'take_out'">
                                <span class="rounded bg-orange-800/60 px-1.5 py-0.5 text-orange-300">外帶</span>
                            </template>
                            <span class="text-slate-500" x-text="timeAgo(order.created_at)"></span>
                            <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                  :class="waitBadgeClass(order)"
                                  x-text="'等待 ' + waitMinutes(order) + 'm'"></span>
                                <span class="rounded border border-sky-500/40 bg-sky-900/40 px-1.5 py-0.5 text-[10px] font-semibold text-sky-300"
                                    x-text="'語系 ' + localeLabel(order.order_locale)"></span>
                        </div>
                        {{-- Customer name --}}
                        <div x-show="order.customer_name" class="mt-0.5 text-xs text-slate-400">
                            👤 <span x-text="order.customer_name"></span>
                        </div>
                    </div>

                    {{-- Status badge --}}
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                          :class="{
                            'bg-amber-500/20 text-amber-300': isPending(order),
                            'bg-emerald-500/20 text-emerald-300': isUnpaidCompleted(order)
                          }"
                          x-text="statusLabel(order.status, order.payment_status)">
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
                        <span class="font-semibold">備註：</span><span x-text="order.note"></span>
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
                                x-text="order._loading ? '處理中...' : (checkoutTiming === 'prepay' ? '💳 已收款・開始製作' : '✅ 接單・開始製作')">
                            </button>

                            <button
                                @click="cancelOrder(order)"
                                :disabled="order._loading"
                                class="flex-1 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-500 disabled:opacity-50">
                                <span x-text="order._loading ? '處理中...' : '❌ 取消訂單'"></span>
                            </button>
                        </div>
                    </template>

                    {{-- Collect payment: completed unpaid order --}}
                    <template x-if="isUnpaidCompleted(order)">
                        <button
                            @click="setStatus(order, 'paid')"
                            :disabled="order._loading"
                            class="flex-1 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-50">
                            <span x-text="order._loading ? '處理中...' : '💰 已收款'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- New order toast --}}
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
            <p class="font-bold text-white">新訂單進來了！</p>
            <p class="text-xs text-emerald-300">已自動加入看板</p>
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
        <p class="font-semibold">操作失敗</p>
        <p class="mt-1" x-text="errorMessage"></p>
    </div>
</div>

<script>
function cashierBoard() {
    return {
        orders: @json($ordersData),
        statusUrlTemplate: @json(route('admin.stores.cashier.orders.status', ['store' => $store, 'order' => '__ORDER__'])),
        checkoutTiming: @json($checkoutTiming ?? 'postpay'),
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
                return '尚未更新';
            }

            const d = new Date(this.lastUpdatedAt);
            return d.toLocaleTimeString('zh-TW', { hour12: false });
        },

        init() {
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
                const res = await fetch('{{ route('admin.stores.cashier.orders', $store) }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const fresh = await res.json();
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
            this.errorMessage = message || '更新狀態失敗，請稍後重試';
            this._errorTimer = setTimeout(() => { this.errorMessage = ''; }, 5000);
        },

        // Accept a pending order → send to kitchen board
        acceptOrder(order) {
            this.setStatus(order, 'preparing');
        },

        cancelOrder(order) {
            this.setStatus(order, 'cancelled');
        },

        async setStatus(order, status) {
            order._loading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!token) {
                    this.showError('找不到 CSRF Token，請重新整理頁面');
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
                    body: JSON.stringify({ status }),
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
                this.showError(e?.message || '網路錯誤，請稍後重試');
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
                return '代收款';
            }
            const map = {
                pending:    '待接單',
                accepted:   '已接單',
                confirmed:  '已確認',
                received:   '已收到',
            };
            return map[status] ?? status;
        },

        timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diff < 60)   return diff + ' 秒前';
            if (diff < 3600) return Math.floor(diff / 60) + ' 分鐘前';
            return Math.floor(diff / 3600) + ' 小時前';
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
