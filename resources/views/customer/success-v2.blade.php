<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單完成 - DineFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-soft/20 text-brand-dark">
    <div class="min-h-screen">
        <main class="mx-auto max-w-5xl px-4 py-8 sm:py-12">
            <section class="overflow-hidden rounded-[2rem] border border-brand-soft/60 bg-white shadow-[0_24px_60px_rgba(90,30,14,0.12)]">
                <div class="relative isolate overflow-hidden bg-brand-dark px-6 py-8 text-white">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.22),_transparent_30%),linear-gradient(135deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.92))]"></div>
                    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-highlight text-2xl text-brand-dark">✓</div>
                            <h1 class="text-2xl font-bold tracking-tight">訂單已成功送出</h1>
                            <p class="mt-2 text-sm leading-6 text-white/75">店家已收到你的訂單，接下來可以依照訂單編號與明細進行確認。</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-left sm:min-w-[220px]">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-brand-highlight/80">Order No.</p>
                            <p class="mt-1 text-lg font-bold text-white">{{ $order->order_no }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-6 rounded-[1.75rem] border border-brand-soft/60 bg-white p-5 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                <h2 class="text-lg font-bold text-brand-dark">訂單資訊</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-brand-soft/18 px-4 py-4">
                        <p class="text-sm text-brand-primary/70">店家</p>
                        <p class="mt-1 font-semibold text-brand-dark">{{ $order->store->name }}</p>
                    </div>
                    <div class="rounded-2xl bg-brand-soft/18 px-4 py-4">
                        <p class="text-sm text-brand-primary/70">點餐方式</p>
                        <p class="mt-1 font-semibold text-brand-dark">{{ $order->order_type === 'takeout' ? '外帶' : '內用' }}</p>
                    </div>
                    @if($order->table)
                        <div class="rounded-2xl bg-brand-soft/18 px-4 py-4">
                            <p class="text-sm text-brand-primary/70">桌號</p>
                            <p class="mt-1 font-semibold text-brand-dark">{{ $order->table->table_no }}</p>
                        </div>
                    @endif
                    <div class="rounded-2xl bg-brand-soft/18 px-4 py-4">
                        <p class="text-sm text-brand-primary/70">總金額</p>
                        <p class="mt-1 font-semibold text-brand-dark">NT$ {{ number_format($order->total) }}</p>
                    </div>
                </div>
            </section>

            <section class="mt-6 rounded-[1.75rem] border border-brand-soft/60 bg-white p-5 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-brand-dark">餐點明細</h2>
                    <span class="text-sm text-brand-primary/70">{{ $order->items->count() }} 個品項</span>
                </div>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-brand-soft/50 bg-brand-soft/12 px-4 py-4">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-semibold text-brand-dark">{{ $item->product_name }}</h3>
                                <p class="mt-1 text-sm text-brand-primary/70">單價 NT$ {{ number_format($item->price) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-brand-primary/70">x {{ $item->qty }}</p>
                                <p class="mt-1 text-base font-bold text-brand-primary">NT$ {{ number_format($item->subtotal) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mt-6">
                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-white p-5 text-center shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                    @if($order->order_type === 'takeout')
                        <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}" class="inline-flex h-11 items-center justify-center rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">回到外帶菜單</a>
                    @elseif($order->table)
                        <a href="{{ route('customer.dinein.menu', ['store' => $store, 'table' => $order->table]) }}" class="inline-flex h-11 items-center justify-center rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">回到內用菜單</a>
                    @endif
                </div>
            </section>
        </main>
    </div>
</body>
</html>
