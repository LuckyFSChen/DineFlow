@extends('layouts.app')

@section('title', '後廚看板（製作）— ' . $store->name)

@php
function kitchenFormatOrder(\App\Models\Order $order): array {
    return [
        'id'           => $order->id,
        'order_no'     => $order->order_no,
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
@endphp

@section('content')
<div class="min-h-screen bg-slate-900 text-white" x-data="kitchenBoard()" x-init="init()">

    {{-- Header --}}
    <div class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-700 bg-slate-900/95 px-6 py-3 backdrop-blur">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700">
                ← 回店家管理
            </a>
            <div>
                <h1 class="text-lg font-bold text-white">🍳 後廚看板</h1>
                <p class="text-xs text-slate-400">{{ $store->name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Link to Cashier board --}}
            <a href="{{ route('admin.stores.cashier', $store) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700">
                💳 結帳看板
            </a>

            {{-- Live indicator --}}
            <div class="flex items-center gap-1.5 rounded-full border border-emerald-700 bg-emerald-900/50 px-3 py-1 text-xs text-emerald-400">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                即時更新
            </div>

            {{-- Count badge --}}
            <span class="rounded-full bg-indigo-600 px-3 py-0.5 text-xs font-bold" x-text="filteredOrders.length + ' 單'"></span>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="filteredOrders.length === 0 && !loading" class="flex flex-col items-center justify-center py-32 text-slate-500">
        <svg class="mb-4 h-16 w-16 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-xl font-semibold">目前沒有待製作訂單</p>
        <p class="mt-1 text-sm">新訂單進來時會自動顯示</p>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-600 border-t-indigo-400"></div>
    </div>

    {{-- Order grid --}}
    <div x-show="!loading" class="grid gap-4 p-6" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
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
                                    桌號 <span x-text="order.table.table_no"></span>
                                </span>
                            </template>
                            <template x-if="order.order_type === 'takeout' || order.order_type === 'take_out'">
                                <span class="rounded bg-orange-800/60 px-1.5 py-0.5 text-orange-300">外帶</span>
                            </template>
                            <span class="text-slate-500" x-text="timeAgo(order.created_at)"></span>
                        </div>
                        {{-- Customer name --}}
                        <div x-show="order.customer_name" class="mt-0.5 text-xs text-slate-400">
                            👤 <span x-text="order.customer_name"></span>
                        </div>
                    </div>

                    {{-- Status badge --}}
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-500/20 text-blue-300">
                        製作中
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
                        <span class="font-semibold">備註：</span><span x-text="order.note"></span>
                    </div>
                </template>

                {{-- Action button: complete --}}
                <div class="flex gap-2 border-t border-slate-700/50 px-4 py-3">
                    <button
                        @click="setStatus(order, 'completed')"
                        :disabled="order._loading"
                        class="flex-1 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-50">
                        🍽️ 製作完成
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
            <p class="font-bold text-white">新訂單進來了！</p>
            <p class="text-xs text-emerald-300">已自動加入看板</p>
        </div>
    </div>

    <div x-show="errorMessage" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-6 left-6 z-50 max-w-md rounded-2xl border border-rose-500/50 bg-rose-900 px-5 py-3 text-sm text-rose-100 shadow-xl">
        <p class="font-semibold">操作失敗</p>
        <p class="mt-1" x-text="errorMessage"></p>
    </div>
</div>

<script>
function kitchenBoard() {
    return {
        orders: @json($ordersData),
        statusUrlTemplate: @json(route('admin.stores.kitchen.orders.status', ['store' => $store, 'order' => '__ORDER__'])),
        checkoutTiming: @json($checkoutTiming ?? 'postpay'),
        filter: 'all',
        loading: false,
        newOrderAlert: false,
        errorMessage: '',
        _pollTimer: null,
        _alertTimer: null,
        _errorTimer: null,
        _audioContext: null,
        _audioUnlockHandler: null,

        get filteredOrders() {
            // Kitchen board shows all preparing orders (no other states)
            return this.orders;
        },

        init() {
            this.initAudio();
            this._pollTimer = setInterval(() => this.poll(), 10000);
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
            this.errorMessage = message || '更新狀態失敗，請稍後重試';
            this._errorTimer = setTimeout(() => { this.errorMessage = ''; }, 5000);
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
                this.showError(e?.message || '網路錯誤，請稍後重試');
            }
            order._loading = false;
        },

        isPreparing(order) {
            return ['preparing', 'processing', 'cooking', 'in_progress'].includes(order.status);
        },

        statusLabel(status) {
            const map = {
                preparing:   '製作中',
                processing:  '製作中',
                cooking:     '製作中',
                in_progress: '製作中',
            };
            return map[status] ?? status;
        },

        timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diff < 60)  return diff + ' 秒前';
            if (diff < 3600) return Math.floor(diff / 60) + ' 分鐘前';
            return Math.floor(diff / 3600) + ' 小時前';
        },
    };
}
</script>

@endsection
