<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單成功｜DineFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-orange-50 text-gray-900">
    <div class="min-h-screen">
        <main class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
            {{-- Success Banner --}}
            <section class="rounded-3xl border border-green-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-2xl">
                            ✓
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                            訂單送出成功
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-gray-500">
                            您的訂單已成功送出，店家將開始處理。若有填寫 Email，後續可用來接收訂單通知。
                        </p>
                    </div>

                    <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-left sm:min-w-[220px]">
                        <p class="text-xs font-medium uppercase tracking-wide text-orange-500">Order No.</p>
                        <p class="mt-1 text-lg font-bold text-gray-900">{{ $order->order_no }}</p>
                    </div>
                </div>
            </section>

            {{-- Order Summary --}}
            <section class="mt-6 rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">訂單資訊</h2>

                @php
                    $isTakeout = ($order->order_type ?? null) === 'takeout';
                @endphp

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">店家</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $order->store->name }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">桌號</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $isTakeout ? 'takeout' : ($order->table->table_no ?? '-') }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">訂單狀態</p>
                        <p class="mt-1 font-semibold text-orange-600">{{ $order->customer_status_label }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">訂單金額</p>
                        <p class="mt-1 font-semibold text-gray-900">NT$ {{ number_format($order->total) }}</p>
                    </div>
                </div>
            </section>

            {{-- Items --}}
            <section class="mt-6 rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-bold">餐點明細</h2>
                    <span class="text-sm text-gray-400">{{ $order->items->count() }} 項</span>
                </div>

                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-orange-50 bg-orange-50/50 px-4 py-4">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ $item->product_name }}
                                </h3>
                                @if(!empty($item->note))
                                    <p class="mt-1 text-xs text-orange-600">{{ $item->note }}</p>
                                @endif
                                <p class="mt-1 text-sm text-gray-500">
                                    單價 NT$ {{ number_format($item->price) }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-sm text-gray-500">x {{ $item->qty }}</p>
                                <p class="mt-1 text-base font-bold text-orange-600">
                                    NT$ {{ number_format($item->subtotal) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 border-t border-orange-100 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-base font-medium text-gray-600">總計</span>
                        <span class="text-2xl font-bold text-orange-600">
                            NT$ {{ number_format($order->total) }}
                        </span>
                    </div>
                </div>
            </section>

            {{-- Action --}}
            <section class="mt-6">
                <div class="rounded-3xl border border-orange-100 bg-white p-5 text-center shadow-sm">
                    <p class="text-sm text-gray-500">
                        您可以返回菜單繼續加點，或等待店家出餐。
                    </p>

                    <a href="{{ $isTakeout ? route('customer.takeout.menu', ['store' => $store]) : route('customer.dinein.menu', ['store' => $store, 'table' => $order->table]) }}"
                       class="mt-4 inline-flex h-11 items-center justify-center rounded-2xl bg-orange-500 px-5 text-sm font-semibold text-white hover:bg-orange-600">
                        返回菜單
                    </a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>