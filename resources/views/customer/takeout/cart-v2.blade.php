@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-brand-soft/20 text-brand-dark">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 overflow-hidden rounded-[2rem] border border-brand-soft/60 bg-white shadow-[0_24px_60px_rgba(90,30,14,0.12)]">
            <div class="relative isolate overflow-hidden bg-brand-dark px-6 py-8 text-white">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.22),_transparent_30%),linear-gradient(135deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.92))]"></div>
                <div class="relative">
                    <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}" class="inline-flex items-center text-sm font-medium text-white/70 transition hover:text-white">返回菜單</a>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-brand-highlight">TAKE OUT CART</span>
                        <span class="inline-flex rounded-full border border-brand-soft/30 bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">營業時間 {{ $store->businessHoursLabel() }}</span>
                    </div>
                    <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">{{ $store->name }}</h1>
                    <p class="mt-3 text-sm leading-7 text-white/75 sm:text-base">確認外帶品項與聯絡資訊，送出後店家即可開始處理訂單。</p>
                </div>
            </div>
        </div>

        @if(! $orderingAvailable)
            <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/35 px-4 py-3 text-sm text-brand-dark shadow-sm">{{ $store->orderingClosedMessage() }}</div>
        @endif

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-brand-accent/30 bg-brand-accent/10 px-4 py-3 text-sm text-brand-primary shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/30 px-4 py-4 text-sm text-brand-dark shadow-sm">
                <div class="mb-2 font-semibold">請先確認以下資訊</div>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($cart))
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                        <div class="border-b border-brand-soft/60 px-6 py-5">
                            <h2 class="text-xl font-bold text-brand-dark">購物車品項</h2>
                            <p class="mt-1 text-sm text-brand-primary/70">{{ count($cart) }} 個品項</p>
                        </div>

                        <div class="divide-y divide-brand-soft/50">
                            @foreach($cart as $item)
                                <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-brand-dark">{{ $item['product_name'] }}</h3>
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-brand-primary/70">
                                            <span>單價 NT$ {{ number_format($item['price']) }}</span>
                                            <span>數量 {{ $item['qty'] }}</span>
                                        </div>
                                    </div>
                                    <div class="rounded-full bg-brand-soft/20 px-4 py-2 text-lg font-bold text-brand-primary">NT$ {{ number_format($item['subtotal']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-6">
                        <div class="rounded-[1.75rem] border border-brand-soft/60 bg-white p-6 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                            <h2 class="text-xl font-bold text-brand-dark">訂單摘要</h2>
                            <div class="mt-5 border-t border-brand-soft/50 pt-4">
                                <div class="flex items-center justify-between text-lg font-bold text-brand-dark">
                                    <span>總金額</span>
                                    <span class="text-brand-primary">NT$ {{ number_format($total) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] border border-brand-soft/60 bg-white p-6 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                            <h2 class="text-xl font-bold text-brand-dark">聯絡資訊</h2>
                            <form method="POST" action="{{ route('customer.takeout.cart.checkout', ['store' => $store]) }}" class="mt-6 space-y-5">
                                @csrf
                                <div>
                                    <label for="customer_name" class="mb-2 block text-sm font-medium text-brand-primary">姓名</label>
                                    <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm text-brand-dark focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">
                                </div>
                                <div>
                                    <label for="customer_email" class="mb-2 block text-sm font-medium text-brand-primary">Email</label>
                                    <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm text-brand-dark focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">
                                </div>
                                <div>
                                    <label for="customer_phone" class="mb-2 block text-sm font-medium text-brand-primary">電話</label>
                                    <input id="customer_phone" type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm text-brand-dark focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">
                                </div>
                                <div>
                                    <label for="note" class="mb-2 block text-sm font-medium text-brand-primary">備註</label>
                                    <textarea id="note" name="note" rows="4" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm text-brand-dark focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">{{ old('note') }}</textarea>
                                </div>
                                <button type="submit" @disabled(! $orderingAvailable) class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold shadow-sm transition {{ $orderingAvailable ? 'bg-brand-primary text-white hover:bg-brand-accent hover:text-brand-dark' : 'cursor-not-allowed bg-slate-300 text-slate-500' }}">送出外帶訂單</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-[2rem] border border-brand-soft/60 bg-white px-6 py-16 text-center shadow-[0_20px_40px_rgba(90,30,14,0.08)]">
                <h2 class="text-2xl font-bold text-brand-dark">購物車目前是空的</h2>
                <p class="mt-3 text-brand-primary/75">回到菜單挑選喜歡的餐點，再加入購物車。</p>
                <div class="mt-6">
                    <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}" class="inline-flex items-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">回到菜單</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
