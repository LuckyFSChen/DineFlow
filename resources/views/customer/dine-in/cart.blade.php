<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer.cart_title') }}｜DineFlow</title>
    @include('partials.favicon')
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
    $phoneDigits = match (strtolower((string) ($store->country_code ?? 'tw'))) {
        'cn' => 11,
        default => 10,
    };
    $isGuest = ! auth()->check();
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

                        @if(isset($member) && $member)
                            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                會員點數：<span class="font-semibold">{{ number_format((int) $member->points_balance) }}</span> 點
                            </div>
                        @endif

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
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST" action="{{ route('customer.dinein.cart.items.update', ['store' => $store, 'table' => $table, 'lineKey' => $item['line_key']]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="decrease">
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-orange-200 bg-white text-sm font-bold text-orange-600 transition hover:bg-orange-100" aria-label="{{ __('customer.decrease_qty') }}">−</button>
                                            </form>
                                            <p class="min-w-8 text-sm font-semibold text-gray-600">{{ $item['qty'] }}</p>
                                            <form method="POST" action="{{ route('customer.dinein.cart.items.update', ['store' => $store, 'table' => $table, 'lineKey' => $item['line_key']]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="increase">
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-orange-200 bg-white text-sm font-bold text-orange-600 transition hover:bg-orange-100" aria-label="{{ __('customer.increase_qty') }}">+</button>
                                            </form>
                                            <form method="POST" action="{{ route('customer.dinein.cart.items.destroy', ['store' => $store, 'table' => $table, 'lineKey' => $item['line_key']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-1 inline-flex items-center rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">{{ __('customer.remove_item') }}</button>
                                            </form>
                                        </div>
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

                        <form method="POST" action="{{ route('customer.dinein.cart.checkout', ['store' => $store, 'table' => $table]) }}" class="space-y-5" data-customer-checkout-form data-phone-check-url="{{ route('customer.dinein.phone.registered', ['store' => $store, 'table' => $table]) }}">
                            @csrf
                            <input type="hidden" name="create_account_with_phone" value="0" data-create-account-with-phone>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('customer.name') }}</label>
                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                    {{ __('customer.name') }}
                                    <span class="text-red-600 font-bold">*</span>
                                </label>
                                <input type="text"
                                                    name="customer_name"
                                                    value="{{ old('customer_name', $rememberedCustomerInfo['customer_name'] ?? '') }}"
                                                    class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                                    placeholder="{{ __('customer.name_placeholder') }}" required>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('auth.Email') }}</label>
                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                    {{ __('auth.Email') }}
                                    <span class="text-red-600 font-bold">*</span>
                                </label>
                                <input type="email"
                                                    name="customer_email"
                                                    value="{{ old('customer_email', $rememberedCustomerInfo['customer_email'] ?? '') }}"
                                                    class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                                    placeholder="{{ __('customer.email_placeholder') }}" required>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('customer.phone') }}</label>
                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                    {{ __('customer.phone') }}
                                    <span class="text-red-600 font-bold">*</span>
                                </label>
                                <input type="text"
                                       name="customer_phone"
                                       value="{{ old('customer_phone', $rememberedCustomerInfo['customer_phone'] ?? '') }}"
                                       inputmode="numeric"
                                       maxlength="{{ $phoneDigits + 2 }}"
                                       pattern="[0-9-]*"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="{{ __('customer.phone_placeholder') }}" required>
                                    <p class="mt-1 text-xs text-orange-600">{{ __('customer.phone_format_hint', ['digits' => $phoneDigits]) }}</p>
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
                                <label class="mb-2 block text-sm font-medium text-gray-700">優惠券代碼</label>
                                <input type="text"
                                       name="coupon_code"
                                       value="{{ old('coupon_code') }}"
                                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm uppercase focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200"
                                       placeholder="例如：WELCOME100">
                                <p class="mt-1 text-xs text-orange-600">若為點數券，請先填寫手機或 Email 以辨識會員。</p>
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
        const maxDigits = @json($phoneDigits);
        if (!input) {
            return;
        }

        const normalizePhoneInput = (raw) => {
            const digits = String(raw || '').replace(/\D/g, '').slice(0, Number(maxDigits || 10));

            if (digits.length <= 4) {
                return digits;
            }

            if (digits.length <= 7) {
                return `${digits.slice(0, 4)}-${digits.slice(4)}`;
            }

            return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
        };

        const apply = () => {
            input.value = normalizePhoneInput(input.value);
        };

        input.setAttribute('maxlength', String((maxDigits || 10) + 2));
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9-]*');
        input.addEventListener('input', apply);
        input.addEventListener('blur', apply);
        apply();

        const checkoutForm = document.querySelector('[data-customer-checkout-form]');
        const createAccountInput = checkoutForm?.querySelector('[data-create-account-with-phone]');
        const isGuest = @json($isGuest);

        if (!checkoutForm || !createAccountInput || !isGuest) {
            return;
        }

        const promptMessage = @json(__('customer.guest_register_points_prompt'));

        const phoneCheckUrl = checkoutForm.dataset.phoneCheckUrl || '';

        checkoutForm.addEventListener('submit', async (event) => {
            if (checkoutForm.dataset.submitting === '1') {
                return;
            }

            event.preventDefault();

            const digits = String(input.value || '').replace(/\D/g, '');
            if (!digits) {
                createAccountInput.value = '0';
                checkoutForm.dataset.submitting = '1';
                checkoutForm.submit();
                return;
            }

            let isRegistered = false;

            if (phoneCheckUrl) {
                try {
                    const checkEndpoint = new URL(phoneCheckUrl, window.location.origin);
                    checkEndpoint.searchParams.set('customer_phone', input.value);

                    const response = await window.fetch(checkEndpoint.toString(), {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.ok) {
                        const payload = await response.json();
                        isRegistered = Boolean(payload?.registered);
                    }
                } catch (_error) {
                    isRegistered = false;
                }
            }

            createAccountInput.value = isRegistered ? '0' : (window.confirm(promptMessage) ? '1' : '0');
            checkoutForm.dataset.submitting = '1';
            checkoutForm.submit();
        });
    })();
    </script>
</body>
</html>
