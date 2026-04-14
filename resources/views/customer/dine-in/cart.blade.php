<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>購物車｜DineFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-orange-50 text-gray-900">
    <div class="min-h-screen pb-32">
        {{-- Header --}}
        <header class="sticky top-0 z-30 border-b border-orange-100 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-3xl px-4 py-4">
                <a href="{{ route('customer.menu', $token) }}"
                   class="mb-3 inline-flex items-center text-sm font-medium text-orange-600 hover:text-orange-700">
                    ← 返回菜單
                </a>

                <h1 class="text-2xl font-bold tracking-tight">購物車</h1>
                <p class="mt-1 text-sm text-gray-500">桌號：{{ $table->table_no }}</p>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-6">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (empty($cart))
                <div class="rounded-3xl border border-dashed border-orange-200 bg-white px-6 py-12 text-center shadow-sm">
                    <div class="mx-auto max-w-sm">
                        <h2 class="text-xl font-bold text-gray-900">購物車目前是空的</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-500">
                            先回到菜單挑選喜歡的餐點，再回來確認訂單。
                        </p>

                        <a href="{{ route('customer.menu', $token) }}"
                           class="mt-6 inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white hover:bg-orange-600">
                            前往菜單
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-6">
                    {{-- Order Items --}}
                    <section class="rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-bold">已選餐點</h2>
                            <span class="text-sm text-gray-400">{{ count($cart) }} 項</span>
                        </div>

                        <div class="space-y-4">
                            @foreach ($cart as $item)
                                <div class="flex items-center justify-between gap-4 rounded-2xl border border-orange-50 bg-orange-50/50 px-4 py-4">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base font-semibold text-gray-900">
                                            {{ $item['product_name'] }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            單價 NT$ {{ number_format($item['price']) }}
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">x {{ $item['qty'] }}</p>
                                        <p class="mt-1 text-base font-bold text-orange-600">
                                            NT$ {{ number_format($item['subtotal']) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 border-t border-orange-100 pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-medium text-gray-600">訂單總計</span>
                                <span class="text-2xl font-bold text-orange-600">
                                    NT$ {{ number_format($total) }}
                                </span>
                            </div>
                        </div>
                    </section>

                    {{-- Customer Form --}}
                    <section class="rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-lg font-bold">訂單資訊</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                可選填聯絡資訊，方便店家聯繫或寄送訂單通知。
                            </p>
                        </div>

                        <form method="POST" action="{{ route('customer.cart.submit', $token) }}" class="space-y-5">
                            @csrf

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">姓名</label>
                                <input type="text"
                                       name="customer_name"
                                       value="{{ old('customer_name') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="例如：Lucky">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Email</label>
                                <input type="email"
                                       name="customer_email"
                                       value="{{ old('customer_email') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="例如：lucky@example.com">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">電話</label>
                                <input type="text"
                                       name="customer_phone"
                                       value="{{ old('customer_phone') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="例如：0912345678">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">備註</label>
                                <textarea name="note"
                                          rows="4"
                                          class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                          placeholder="例如：不要香菜、餐點先上、稍晚一起出">{{ old('note') }}</textarea>
                            </div>

                            <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-gray-600">
                                送出訂單後，店家將開始處理您的餐點。
                            </div>

                            <button type="submit"
                                    class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-orange-500 px-5 text-base font-semibold text-white shadow-sm transition hover:bg-orange-600 active:scale-[0.99]">
                                確認送出訂單
                            </button>
                        </form>
                    </section>
                </div>
            @endif
        </main>
    </div>
</body>
</html>