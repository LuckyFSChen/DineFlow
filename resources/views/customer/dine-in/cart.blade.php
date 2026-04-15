<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer.cart_title') }}｜DineFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
@endphp
<body class="bg-orange-50 text-gray-900">
    <div class="min-h-screen pb-32">
        {{-- Header --}}
        <header class="sticky top-0 z-30 border-b border-orange-100 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-3xl px-4 py-4">
                <div class="mb-3 flex items-center justify-between">
                    <a href="{{ route('customer.dinein.menu', ['store' => $store, 'table' => $table]) }}"
                       class="inline-flex items-center text-sm font-medium text-orange-600 hover:text-orange-700">
                        ← {{ __('customer.back_to_menu') }}
                    </a>
                    <x-lang-switcher />
                </div>

                <h1 class="text-2xl font-bold tracking-tight">{{ __('customer.cart_title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('customer.table_no') }}：{{ $table->table_no }}</p>
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
                    <p class="text-sm font-semibold text-gray-900">{{ __('customer.recent_orders') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($orderHistory->take(5) as $historyOrder)
                            <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100">{{ $historyOrder->order_no }} ・ {{ $historyOrder->customer_status_label }}</a>
                        @endforeach
                    </div>
                    <a href="{{ route('customer.order.history', ['store' => $store]) }}" class="mt-3 inline-flex items-center rounded-xl border border-orange-200 bg-white px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-50">{{ __('customer.view_my_order_history') }}</a>
                </div>
            @endif

            @if (empty($cart))
                <div class="rounded-3xl border border-dashed border-orange-200 bg-white px-6 py-12 text-center shadow-sm">
                    <div class="mx-auto max-w-sm">
                        <h2 class="text-xl font-bold text-gray-900">{{ __('customer.cart_empty') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-500">
                            {{ __('customer.cart_empty_hint') }}
                        </p>

                        <a href="{{ route('customer.dinein.menu', ['store' => $store, 'table' => $table]) }}"
                           class="mt-6 inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white hover:bg-orange-600">
                            {{ __('customer.go_to_menu') }}
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-6">
                    {{-- Order Items --}}
                    <section class="rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-bold">{{ __('customer.selected_items') }}</h2>
                            <span class="text-sm text-gray-400">{{ count($cart) }} {{ __('customer.items') }}</span>
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
                                        @if(!empty($item['item_note']))
                                            <p class="mt-1 text-xs text-amber-700">{{ __('customer.item_note_prefix') }} {{ $item['item_note'] }}</p>
                                        @endif
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ __('customer.unit_price') }} {{ $currencySymbol }} {{ number_format($item['price']) }}
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">x {{ $item['qty'] }}</p>
                                        <p class="mt-1 text-base font-bold text-orange-600">
                                            {{ $currencySymbol }} {{ number_format($item['subtotal']) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 border-t border-orange-100 pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-medium text-gray-600">{{ __('customer.order_total') }}</span>
                                <span class="text-2xl font-bold text-orange-600">
                                    {{ $currencySymbol }} {{ number_format($total) }}
                                </span>
                            </div>
                        </div>
                    </section>

                    {{-- Customer Form --}}
                    <section class="rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-lg font-bold">{{ __('customer.order_info') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('customer.order_info_hint') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('customer.dinein.cart.checkout', ['store' => $store, 'table' => $table]) }}" class="space-y-5">
                            @csrf

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('customer.name') }}</label>
                                <input type="text"
                                       name="customer_name"
                                        value="{{ old('customer_name', $rememberedCustomerInfo['customer_name'] ?? '') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                    placeholder="{{ __('customer.name_placeholder') }}">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('auth.Email') }}</label>
                                <input type="email"
                                       name="customer_email"
                                        value="{{ old('customer_email', $rememberedCustomerInfo['customer_email'] ?? '') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                    placeholder="{{ __('customer.email_placeholder') }}">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('customer.phone') }}</label>
                                <input type="text"
                                       name="customer_phone"
                                       value="{{ old('customer_phone', $rememberedCustomerInfo['customer_phone'] ?? '') }}"
                                       inputmode="numeric"
                                       maxlength="12"
                                       pattern="09[0-9]{2}-[0-9]{3}-[0-9]{3}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                        placeholder="{{ __('customer.phone_placeholder') }}">
                                    <p class="mt-1 text-xs text-orange-600">{{ __('customer.phone_format_hint') }}</p>
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
                                    {{ __('customer.remember_info') }}
                                </label>

                                @if(!empty($rememberedCustomerInfo))
                                    <div class="mt-2">
                                        <button
                                            type="submit"
                                            formaction="{{ route('customer.dinein.customer-info.clear', ['store' => $store, 'table' => $table]) }}"
                                            formmethod="POST"
                                            formnovalidate
                                            class="inline-flex items-center rounded-xl border border-orange-200 bg-white px-3 py-1.5 text-xs font-semibold text-orange-600 transition hover:bg-orange-50"
                                        >
                                            {{ __('customer.clear_remembered_info') }}
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('customer.note') }}</label>
                                <textarea name="note"
                                          rows="4"
                                          class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                          placeholder="{{ __('customer.note_placeholder') }}">{{ old('note') }}</textarea>
                            </div>

                            <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-gray-600">
                                {{ __('customer.submit_order_hint') }}
                            </div>

                            <button type="submit"
                                    class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-orange-500 px-5 text-base font-semibold text-white shadow-sm transition hover:bg-orange-600 active:scale-[0.99]">
                                {{ __('customer.submit_order') }}
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