<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>內用購物車 - DineFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-soft/20 text-brand-dark">
    <div class="min-h-screen pb-32">
        <header class="sticky top-0 z-30 border-b border-brand-soft/60 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-5xl px-4 py-5">
                <a href="{{ route('customer.dinein.menu', ['store' => $store, 'table' => $table]) }}" class="mb-3 inline-flex items-center text-sm font-medium text-brand-primary transition hover:text-brand-dark">返回菜單</a>
                <h1 class="text-2xl font-bold tracking-tight text-brand-dark">內用購物車</h1>
                <p class="mt-1 text-sm text-brand-primary/75">桌號 {{ $table->table_no }}，營業時間 {{ $store->businessHoursLabel() }}</p>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-6">
            @if(! $orderingAvailable)
                <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/35 px-4 py-3 text-sm text-brand-dark">{{ $store->orderingClosedMessage() }}</div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/30 px-4 py-3 text-sm text-brand-dark">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if(empty($cart))
                <div class="rounded-[2rem] border border-brand-soft/60 bg-white px-6 py-12 text-center shadow-[0_20px_40px_rgba(90,30,14,0.08)]">
                    <h2 class="text-xl font-bold text-brand-dark">購物車目前是空的</h2>
                    <p class="mt-2 text-sm leading-6 text-brand-primary/75">回到菜單挑選餐點後，再加入本桌訂單。</p>
                    <a href="{{ route('customer.dinein.menu', ['store' => $store, 'table' => $table]) }}" class="mt-6 inline-flex h-11 items-center justify-center rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">返回菜單</a>
                </div>
            @else
                <div class="space-y-6">
                    <section class="rounded-[1.75rem] border border-brand-soft/60 bg-white p-5 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-brand-dark">餐點明細</h2>
                            <span class="text-sm text-brand-primary/70">{{ count($cart) }} 個品項</span>
                        </div>
                        <div class="space-y-4">
                            @foreach($cart as $item)
                                <div class="flex items-center justify-between gap-4 rounded-2xl border border-brand-soft/50 bg-brand-soft/12 px-4 py-4">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base font-semibold text-brand-dark">{{ $item['product_name'] }}</h3>
                                        <p class="mt-1 text-sm text-brand-primary/70">單價 NT$ {{ number_format($item['price']) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-brand-primary/70">x {{ $item['qty'] }}</p>
                                        <p class="mt-1 text-base font-bold text-brand-primary">NT$ {{ number_format($item['subtotal']) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6 border-t border-brand-soft/50 pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-medium text-brand-primary">總金額</span>
                                <span class="text-2xl font-bold text-brand-primary">NT$ {{ number_format($total) }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[1.75rem] border border-brand-soft/60 bg-white p-5 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                        <div class="mb-5">
                            <h2 class="text-lg font-bold text-brand-dark">送單資訊</h2>
                            <p class="mt-1 text-sm text-brand-primary/70">填寫聯絡資訊或備註後，就可以送出本桌訂單。</p>
                        </div>

                        <form method="POST" action="{{ route('customer.dinein.cart.checkout', ['store' => $store, 'table' => $table]) }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="mb-2 block text-sm font-medium text-brand-primary">姓名</label>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-brand-primary">Email</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-brand-primary">電話</label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-brand-primary">備註</label>
                                <textarea name="note" rows="4" class="w-full rounded-2xl border border-brand-soft/70 px-4 py-3 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40">{{ old('note') }}</textarea>
                            </div>
                            <button type="submit" @disabled(! $orderingAvailable) class="inline-flex h-12 w-full items-center justify-center rounded-2xl px-5 text-base font-semibold shadow-sm transition {{ $orderingAvailable ? 'bg-brand-primary text-white hover:bg-brand-accent hover:text-brand-dark' : 'cursor-not-allowed bg-slate-300 text-slate-500' }}">送出內用訂單</button>
                        </form>
                    </section>
                </div>
            @endif
        </main>
    </div>
</body>
</html>
