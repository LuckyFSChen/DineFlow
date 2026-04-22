@extends('layouts.app')

@section('content')
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
<div class="min-h-screen bg-brand-soft/20">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8 overflow-hidden rounded-[2rem] border border-brand-soft/60 bg-white shadow-[0_24px_60px_rgba(90,30,14,0.12)]">
            <div class="relative isolate overflow-hidden bg-brand-dark px-6 py-8 text-white sm:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.28),_transparent_34%),linear-gradient(135deg,_rgba(90,30,14,0.96),_rgba(236,144,87,0.88))]"></div>
                <div class="absolute -right-12 -top-10 h-36 w-36 rounded-full bg-brand-highlight/20 blur-3xl"></div>
                <div class="absolute -bottom-14 left-10 h-32 w-32 rounded-full bg-brand-accent/20 blur-3xl"></div>
                <div class="relative">
            <div class="mb-2">
                <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}"
                   class="inline-flex items-center text-sm font-medium text-white/70 transition hover:text-white">
                    ← {{ __('customer.back_to_takeout_menu') }}
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-brand-highlight">
                    {{ __('customer.takeout_cart_badge') }}
                </span>
            </div>

            <h1 class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                {{ $store->name }}
            </h1>

            <p class="mt-3 text-sm leading-6 text-white/75 sm:text-base">
                {{ __('customer.confirm_before_submit') }}
            </p>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-brand-accent/30 bg-brand-accent/10 px-4 py-3 text-sm text-brand-primary shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/30 px-4 py-4 text-sm text-brand-dark shadow-sm">
                <div class="mb-2 font-semibold">{{ __('customer.confirm_fields') }}</div>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(isset($orderHistory) && $orderHistory->isNotEmpty())
            <div class="mb-6 rounded-2xl border border-brand-soft/60 bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-brand-dark">{{ __('customer.recent_orders') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($orderHistory->take(5) as $historyOrder)
                        <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex items-center rounded-xl border border-brand-soft bg-brand-soft/20 px-3 py-1.5 text-xs font-semibold text-brand-primary transition hover:bg-brand-highlight/50">{{ $historyOrder->order_no }} ・ {{ $historyOrder->customer_status_label }}</a>
                    @endforeach
                </div>
                <a href="{{ route('customer.order.history') }}" class="mt-3 inline-flex items-center rounded-xl border border-brand-soft bg-white px-3 py-1.5 text-xs font-semibold text-brand-primary transition hover:bg-brand-soft/30">{{ __('customer.view_my_order_history') }}</a>
            </div>
        @endif

        @if(!empty($cart))
            <div class="grid gap-8 lg:grid-cols-3">
                {{-- Cart Items --}}
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-3xl border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                        <div class="border-b border-brand-soft/60 px-6 py-5">
                            <h2 class="text-xl font-bold text-brand-dark">{{ __('customer.cart_contents') }}</h2>
                            <p class="mt-1 text-sm text-brand-primary/70">
                                {{ __('customer.total_products_prefix') }} {{ count($cart) }} {{ __('customer.total_products_suffix') }}
                            </p>
                        </div>

                        <div class="divide-y divide-brand-soft/60">
                            @foreach($cart as $item)
                                <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-lg font-semibold text-brand-dark">
                                            {{ $item['product_name'] }}
                                        </h3>
                                        @if(!empty($item['option_label']))
                                            <p class="mt-1 text-xs text-brand-primary">{{ $item['option_label'] }}</p>
                                        @endif
                                        @if(!empty($item['item_note']))
                                            <p class="mt-1 text-xs text-amber-700">{{ __('customer.item_note_prefix') }} {{ $item['item_note'] }}</p>
                                        @endif
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-brand-primary/75">
                                            <span>{{ __('customer.unit_price') }} {{ $currencySymbol }} {{ number_format($item['price']) }}</span>
                                            <span>{{ __('customer.qty') }}</span>
                                            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-soft/70 bg-brand-soft/20 px-2 py-1">
                                                <form method="POST" action="{{ route('customer.takeout.cart.items.update', ['store' => $store, 'lineKey' => $item['line_key']]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="action" value="decrease">
                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.decrease_qty') }}">−</button>
                                                </form>
                                                <span class="min-w-6 text-center text-sm font-semibold text-brand-dark">{{ $item['qty'] }}</span>
                                                <form method="POST" action="{{ route('customer.takeout.cart.items.update', ['store' => $store, 'lineKey' => $item['line_key']]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="action" value="increase">
                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.increase_qty') }}">+</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 sm:flex-col sm:items-end">
                                        <div class="text-lg font-bold text-brand-dark">
                                            {{ $currencySymbol }} {{ number_format($item['subtotal']) }}
                                        </div>
                                        <form method="POST" action="{{ route('customer.takeout.cart.items.destroy', ['store' => $store, 'lineKey' => $item['line_key']]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">{{ __('customer.remove_item') }}</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Summary + Checkout --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-6">
                        <div class="rounded-3xl border border-brand-soft/60 bg-white p-6 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                            <h2 class="text-xl font-bold text-brand-dark">{{ __('customer.order_summary') }}</h2>

                            @if(isset($member) && $member)
                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    會員點數：<span class="font-semibold">{{ number_format((int) $member->points_balance) }}</span> 點
                                </div>
                            @endif

                            <div class="mt-5 space-y-4 text-sm">
                                <div class="flex items-center justify-between text-brand-primary/80">
                                    <span>{{ __('customer.subtotal') }}</span>
                                    <span>{{ $currencySymbol }} {{ number_format($total) }}</span>
                                </div>

                                <div class="flex items-center justify-between text-brand-primary/70">
                                    <span>{{ __('customer.coupon_discount') }}</span>
                                    <span>{{ __('customer.coupon_discount_after_submit') }}</span>
                                </div>

                                <div class="border-t border-brand-soft/60 pt-4">
                                    <div class="flex items-center justify-between text-lg font-bold text-brand-dark">
                                        <span>{{ __('customer.estimated_payable') }}</span>
                                        <span>{{ $currencySymbol }} {{ number_format($total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-brand-soft/60 bg-white p-6 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                            <h2 class="text-xl font-bold text-brand-dark">{{ __('customer.fill_order_info') }}</h2>

                            <form method="POST"
                                  action="{{ route('customer.takeout.cart.checkout', ['store' => $store]) }}"
                                                                    class="mt-6 space-y-5"
                                                          data-customer-checkout-form
                                                          data-phone-check-url="{{ route('customer.takeout.phone.registered', ['store' => $store]) }}">
                                @csrf
                                                                <input type="hidden" name="create_account_with_phone" value="0" data-create-account-with-phone>

                                <div>
                                    <label for="customer_name" class="mb-2 block text-sm font-medium text-brand-dark">
                                        {{ __('customer.name') }}
                                        <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <input id="customer_name"
                                                                                     type="text"
                                                                                     name="customer_name"
                                                                                     value="{{ old('customer_name', $rememberedCustomerInfo['customer_name'] ?? '') }}"
                                                                                     class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft" required>
                                </div>

                                <div>
                                    <label for="customer_email" class="mb-2 block text-sm font-medium text-brand-dark">
                                        {{ __('auth.Email') }}
                                        <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <input id="customer_email"
                                                                                     type="email"
                                                                                     name="customer_email"
                                                                                     value="{{ old('customer_email', $rememberedCustomerInfo['customer_email'] ?? '') }}"
                                                                                     class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft" required>
                                </div>

                                <div>
                                    <label for="customer_phone" class="mb-2 block text-sm font-medium text-brand-dark">
                                        {{ __('customer.phone') }}
                                        <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <input id="customer_phone"
                                                                                     type="text"
                                                                                     name="customer_phone"
                                                                                     value="{{ old('customer_phone', $rememberedCustomerInfo['customer_phone'] ?? '') }}"
                                                                                     inputmode="numeric"
                                                                                     maxlength="{{ $phoneDigits + 2 }}"
                                                                                     pattern="[0-9-]*"
                                                                                     placeholder="{{ __('customer.phone_placeholder') }}"
                                                                                     class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft" required>
                                                                         <p class="mt-1 text-xs text-brand-primary/70">{{ __('customer.phone_format_hint', ['digits' => $phoneDigits]) }}</p>
                                </div>

                                @if($isGuest)
                                    <div>
                                        <label class="inline-flex items-center gap-2 text-sm text-brand-dark">
                                            <input
                                                type="checkbox"
                                                name="remember_customer_info"
                                                value="1"
                                                @checked(old('remember_customer_info', !empty($rememberedCustomerInfo)))
                                                class="h-4 w-4 rounded border-brand-soft text-brand-primary focus:ring-brand-highlight"
                                            >
                                            {{ __('customer.remember_info') }}
                                        </label>

                                        @if(!empty($rememberedCustomerInfo))
                                                <div class="mt-2">
                                                    <button
                                                        type="submit"
                                                        formaction="{{ route('customer.takeout.customer-info.clear', ['store' => $store]) }}"
                                                        formmethod="POST"
                                                        formnovalidate
                                                        class="inline-flex items-center rounded-xl border border-brand-soft bg-white px-3 py-1.5 text-xs font-semibold text-brand-primary transition hover:bg-brand-soft/30"
                                                    >
                                                        {{ __('customer.clear_remembered_info') }}
                                                    </button>
                                                </div>
                                        @endif
                                    </div>
                                @endif

                                <div>
                                    <label for="coupon_code" class="mb-2 block text-sm font-medium text-brand-dark">
                                        優惠券代碼
                                    </label>
                                    <input id="coupon_code"
                                           type="text"
                                           name="coupon_code"
                                           value="{{ old('coupon_code') }}"
                                           placeholder="例如：WELCOME100"
                                           class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm uppercase text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft">
                                    <p class="mt-1 text-xs text-brand-primary/70">若為會員券，請先填寫手機或 Email 以辨識會員。</p>
                                </div>

                                <div>
                                    <label for="note" class="mb-2 block text-sm font-medium text-brand-dark">
                                        {{ __('customer.note') }}
                                    </label>
                                    <textarea id="note"
                                              name="note"
                                              rows="4"
                                              class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft">{{ old('note') }}</textarea>
                                </div>

                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:bg-brand-accent hover:text-brand-dark">
                                    {{ __('customer.submit_order') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Empty Cart --}}
            <div class="rounded-3xl border border-brand-soft/60 bg-white px-6 py-16 text-center shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                <div class="mx-auto max-w-md">
                    <div class="text-5xl">🛒</div>
                    <h2 class="mt-4 text-2xl font-bold text-brand-dark">{{ __('customer.cart_empty') }}</h2>
                    <p class="mt-3 text-brand-primary/75">
                        {{ __('customer.cart_empty_hint') }}
                    </p>

                    <div class="mt-6">
                        <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}"
                           class="inline-flex items-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                            {{ __('customer.back_to_menu') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
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
@endsection

