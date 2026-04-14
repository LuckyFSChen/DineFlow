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

            @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                <div class="mb-6 rounded-2xl border border-orange-100 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-gray-900">近期訂單狀態</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($orderHistory->take(5) as $historyOrder)
                            <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100">{{ $historyOrder->order_no }} ・ {{ $historyOrder->customer_status_label }}</a>
                        @endforeach
                    </div>
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
                                        @if(!empty($item['option_label']))
                                            <p class="mt-1 text-xs text-orange-600">{{ $item['option_label'] }}</p>
                                        @endif
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
                                        value="{{ old('customer_name', $rememberedCustomerInfo['customer_name'] ?? '') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="例如：Lucky">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Email</label>
                                <input type="email"
                                       name="customer_email"
                                        value="{{ old('customer_email', $rememberedCustomerInfo['customer_email'] ?? '') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="例如：lucky@example.com">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">電話</label>
                                <input type="text"
                                       name="customer_phone"
                                       value="{{ old('customer_phone', $rememberedCustomerInfo['customer_phone'] ?? '') }}"
                                       inputmode="numeric"
                                       maxlength="12"
                                       pattern="09[0-9]{2}-[0-9]{3}-[0-9]{3}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="例如：0922-333-444">
                                <p class="mt-1 text-xs text-orange-600">請輸入格式：0922-333-444</p>
                            </div>

                            <div>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        name="remember_customer_info"
                                        value="1"
                                        @checked(old('remember_customer_info', !empty($rememberedCustomerInfo)))
                                        class="h-4 w-4 rounded border-gray-300 text-orange-500 focus:ring-orange-300"
                                    >
                                    記住這次填寫的訂單資訊（姓名 / Email / 電話）
                                </label>

                                @if(!empty($rememberedCustomerInfo))
                                    <form method="POST" action="{{ route('customer.dinein.customer-info.clear', ['store' => $store, 'table' => $table]) }}" class="mt-2">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-xl border border-orange-200 bg-white px-3 py-1.5 text-xs font-semibold text-orange-600 transition hover:bg-orange-50"
                                        >
                                            清除已記住資訊
                                        </button>
                                    </form>
                                @endif
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

    <script>
    (() => {
        const input = document.querySelector('input[name="customer_phone"]');
        if (!input) {
            return;
        }

        const formatTaiwanMobile = (raw) => {
            const digits = String(raw || '').replace(/\D/g, '').slice(0, 10);

            if (digits.length <= 4) {
                return digits;
            }

            if (digits.length <= 7) {
                return `${digits.slice(0, 4)}-${digits.slice(4)}`;
            }

            return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
        };

        const apply = () => {
            input.value = formatTaiwanMobile(input.value);
        };

        input.setAttribute('maxlength', '12');
        input.setAttribute('inputmode', 'numeric');
        input.addEventListener('input', apply);
        input.addEventListener('blur', apply);
        apply();
    })();
    </script>
</body>
</html>