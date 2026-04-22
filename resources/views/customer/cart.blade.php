@extends('layouts.app')

@section('title', __('customer.cart_title') . ' | ' . config('app.name', 'DineFlow'))
@section('meta_description', __('customer.cart_title'))
@section('meta_robots', 'noindex,nofollow,noarchive')

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
    $isDineIn = isset($table) && $table;

    $routeParams = ['store' => $store];
    if ($isDineIn) {
        $routeParams['table'] = $table;
    }

    $menuRouteName = $isDineIn ? 'customer.dinein.menu' : 'customer.takeout.menu';
    $cartUpdateRouteName = $isDineIn ? 'customer.dinein.cart.items.update' : 'customer.takeout.cart.items.update';
    $cartDestroyRouteName = $isDineIn ? 'customer.dinein.cart.items.destroy' : 'customer.takeout.cart.items.destroy';
    $checkoutRouteName = $isDineIn ? 'customer.dinein.cart.checkout' : 'customer.takeout.cart.checkout';
    $phoneCheckRouteName = $isDineIn ? 'customer.dinein.phone.registered' : 'customer.takeout.phone.registered';
    $couponCheckRouteName = $isDineIn ? 'customer.dinein.coupon.check' : 'customer.takeout.coupon.check';
    $clearCustomerInfoRouteName = $isDineIn ? 'customer.dinein.customer-info.clear' : 'customer.takeout.customer-info.clear';
    $backLabel = $isDineIn ? __('customer.back_to_menu') : __('customer.back_to_takeout_menu');
    $badgeLabel = $isDineIn ? __('customer.cart_title') : __('customer.takeout_cart_badge');
    $orderingAvailable = $orderingAvailable ?? true;
    $oldCouponCode = strtoupper(trim((string) old('coupon_code', '')));
    $oldAppliedCouponCode = strtoupper(trim((string) old('applied_coupon_code', '')));
    $oldAppliedCouponSummary = trim((string) old('applied_coupon_summary', ''));
    $oldAppliedCouponDiscount = max((int) old('applied_coupon_discount', 0), 0);
    $hasOldAppliedCoupon = $oldCouponCode !== ''
        && $oldAppliedCouponCode !== ''
        && $oldCouponCode === $oldAppliedCouponCode
        && $oldAppliedCouponSummary !== '';
    $estimatedReadyMinutes = (int) ($estimatedReadyTime['minutes'] ?? 0);
    $estimatedReadyLabel = $estimatedReadyMinutes > 0
        ? __('customer.estimated_prep_time_only', ['minutes' => $estimatedReadyMinutes])
        : __('customer.estimated_ready_time_unknown');
@endphp
<div class="min-h-screen bg-brand-soft/20">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8 overflow-hidden rounded-[2rem] border border-brand-soft/60 bg-white shadow-[0_24px_60px_rgba(90,30,14,0.12)]">
            <div class="relative isolate overflow-hidden bg-brand-dark px-6 py-8 text-white sm:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.28),_transparent_34%),linear-gradient(135deg,_rgba(90,30,14,0.96),_rgba(236,144,87,0.88))]"></div>
                <div class="absolute -right-12 -top-10 h-36 w-36 rounded-full bg-brand-highlight/20 blur-3xl"></div>
                <div class="absolute -bottom-14 left-10 h-32 w-32 rounded-full bg-brand-accent/20 blur-3xl"></div>
                <div class="relative">
                    <div class="mb-2">
                        <a href="{{ route($menuRouteName, $routeParams) }}"
                           class="inline-flex items-center text-sm font-medium text-white/70 transition hover:text-white">
                            ← {{ $backLabel }}
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-brand-highlight">
                            {{ $badgeLabel }}
                        </span>
                        @if($isDineIn)
                            <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">
                                {{ __('customer.table_no') }} {{ $table->table_no }}
                            </span>
                        @endif
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">
                            {{ $orderingAvailable ? __('customer.open') : __('customer.ordering_closed') }}
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

        <div class="mb-6 hidden rounded-2xl border border-brand-accent/30 bg-brand-accent/10 px-4 py-3 text-sm text-brand-primary shadow-sm" data-cart-sync-notice>
            {{ __('customer.cart_synced_notice') }}
        </div>

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
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-3xl border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                        <div class="border-b border-brand-soft/60 px-6 py-5">
                            <h2 class="text-xl font-bold text-brand-dark">{{ __('customer.cart_contents') }}</h2>
                            <p class="mt-1 text-sm text-brand-primary/70" data-cart-line-count>
                                {{ __('customer.total_products_prefix') }} {{ count($cart) }} {{ __('customer.total_products_suffix') }}
                            </p>
                        </div>

                        <div class="divide-y divide-brand-soft/60" data-cart-items>
                            @foreach($cart as $item)
                                <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between" data-cart-item data-line-key="{{ $item['line_key'] }}">
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
                                        @if(!empty($item['editable_option_groups']) || !empty($item['allow_item_note']))
                                            <form method="POST"
                                                  action="{{ route($cartUpdateRouteName, array_merge($routeParams, ['lineKey' => $item['line_key']])) }}"
                                                  class="mt-2"
                                                  data-option-edit-form>
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="update_options">
                                                <input type="hidden" name="option_payload" value="" data-option-edit-payload>
                                                <input type="hidden" name="item_note" value="{{ $item['item_note'] ?? '' }}" data-option-edit-note>
                                                <button type="button"
                                                        class="inline-flex items-center rounded-lg border border-brand-soft bg-white px-2.5 py-1 text-xs font-semibold text-brand-primary transition hover:bg-brand-soft/30"
                                                        data-open-option-editor
                                                        data-product-name="{{ $item['product_name'] }}"
                                                        data-option-groups='@json($item['editable_option_groups'] ?? [])'
                                                        data-selected-options='@json($item['option_items'] ?? [])'
                                                        data-item-note="{{ $item['item_note'] ?? '' }}"
                                                        data-allow-item-note="{{ !empty($item['allow_item_note']) ? '1' : '0' }}">
                                                    {{ __('customer.select_options_title') }}
                                                </button>
                                            </form>
                                        @endif
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-brand-primary/75">
                                            <span>{{ __('customer.unit_price') }} {{ $currencySymbol }} {{ number_format($item['price']) }}</span>
                                            <span>{{ __('customer.qty') }}</span>
                                            <div class="inline-flex items-center gap-2 rounded-xl border border-brand-soft/70 bg-brand-soft/20 px-2 py-1">
                                                <form method="POST" action="{{ route($cartUpdateRouteName, array_merge($routeParams, ['lineKey' => $item['line_key']])) }}" data-cart-update-form>
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="action" value="decrease">
                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.decrease_qty') }}">−</button>
                                                </form>
                                                <span class="min-w-6 text-center text-sm font-semibold text-brand-dark" data-cart-item-qty>{{ $item['qty'] }}</span>
                                                <form method="POST" action="{{ route($cartUpdateRouteName, array_merge($routeParams, ['lineKey' => $item['line_key']])) }}" data-cart-update-form>
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="action" value="increase">
                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.increase_qty') }}">+</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 sm:flex-col sm:items-end">
                                        <div class="text-lg font-bold text-brand-dark" data-cart-item-subtotal>
                                            {{ $currencySymbol }} {{ number_format($item['subtotal']) }}
                                        </div>
                                        <form method="POST" action="{{ route($cartDestroyRouteName, array_merge($routeParams, ['lineKey' => $item['line_key']])) }}" data-cart-remove-form>
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
                                    <span data-cart-subtotal>{{ $currencySymbol }} {{ number_format($total) }}</span>
                                </div>

                                <div class="flex items-center justify-between text-brand-primary/70">
                                    <span>{{ __('customer.coupon_discount') }}</span>
                                    <span class="font-semibold text-emerald-700" data-coupon-discount>
                                        - {{ $currencySymbol }} {{ number_format($oldAppliedCouponDiscount) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-brand-primary/80">
                                    <span>{{ __('customer.estimated_ready_time') }}</span>
                                    <span class="font-semibold text-brand-dark" data-cart-estimated-ready>
                                        {{ $estimatedReadyLabel }}
                                    </span>
                                </div>

                                <div class="border-t border-brand-soft/60 pt-4">
                                    <div class="flex items-center justify-between text-lg font-bold text-brand-dark">
                                        <span>{{ __('customer.estimated_payable') }}</span>
                                        <span data-cart-payable>
                                            {{ $currencySymbol }} {{ number_format(max($total - $oldAppliedCouponDiscount, 0)) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs text-brand-primary/70" data-coupon-discount-hint>
                                        {{ $hasOldAppliedCoupon ? $oldAppliedCouponSummary : __('customer.coupon_discount_after_submit') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-brand-soft/60 bg-white p-6 shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                            <h2 class="text-xl font-bold text-brand-dark">{{ $isDineIn ? __('customer.order_info') : __('customer.fill_order_info') }}</h2>

                            <form method="POST"
                                  action="{{ route($checkoutRouteName, $routeParams) }}"
                                  class="mt-6 space-y-5"
                                  data-customer-checkout-form
                                  data-phone-check-url="{{ route($phoneCheckRouteName, $routeParams) }}"
                                  data-coupon-check-url="{{ route($couponCheckRouteName, $routeParams) }}">
                                @csrf
                                @unless($isDineIn)
                                    <input type="hidden" name="create_account_with_phone" value="0" data-create-account-with-phone>
                                @endunless

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

                                <div>
                                    <label for="coupon_code" class="mb-2 block text-sm font-medium text-brand-dark">
                                        優惠券代碼
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input id="coupon_code"
                                               type="text"
                                               name="coupon_code"
                                               value="{{ $oldCouponCode }}"
                                               placeholder="例如：WELCOME100"
                                               class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm uppercase text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft"
                                               data-coupon-input>
                                        <button type="button"
                                                class="inline-flex shrink-0 items-center rounded-2xl border border-brand-primary px-4 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-primary hover:text-white"
                                                data-coupon-apply-button>
                                            套用
                                        </button>
                                    </div>
                                    <input type="hidden"
                                           name="applied_coupon_code"
                                           value="{{ $hasOldAppliedCoupon ? $oldAppliedCouponCode : '' }}"
                                           data-applied-coupon-code>
                                   <input type="hidden"
                                           name="applied_coupon_summary"
                                           value="{{ $hasOldAppliedCoupon ? $oldAppliedCouponSummary : '' }}"
                                           data-applied-coupon-summary>
                                   <input type="hidden"
                                           name="applied_coupon_discount"
                                           value="{{ $hasOldAppliedCoupon ? $oldAppliedCouponDiscount : 0 }}"
                                           data-applied-coupon-discount>
                                    <p class="mt-1 text-xs text-brand-primary/70">請先輸入優惠券代碼，再按「套用」。</p>
                                    <p class="mt-2 text-xs text-rose-600 {{ $errors->has('coupon_code') ? '' : 'hidden' }}" data-coupon-error>
                                        {{ $errors->first('coupon_code') }}
                                    </p>
                                    <div class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700 {{ $hasOldAppliedCoupon ? '' : 'hidden' }}"
                                         data-coupon-applied-box>
                                        {{ __('customer.coupon_applied_label') }} <span class="font-semibold" data-coupon-applied-code>{{ $hasOldAppliedCoupon ? $oldAppliedCouponCode : '' }}</span>
                                        <span data-coupon-applied-summary>{{ $hasOldAppliedCoupon ? $oldAppliedCouponSummary : '' }}</span>
                                    </div>
                                </div>

                                <div>
                                    <label for="note" class="mb-2 block text-sm font-medium text-brand-dark">
                                        {{ __('customer.note') }}
                                    </label>
                                    <textarea id="note"
                                              name="note"
                                              rows="4"
                                              class="w-full rounded-2xl border border-brand-soft px-4 py-3 text-sm text-brand-dark focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft">{{ old('note', $rememberedCustomerInfo['note'] ?? '') }}</textarea>
                                </div>

                                @include('customer.partials.invoice-flow-fields')

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
                                                    formaction="{{ route($clearCustomerInfoRouteName, $routeParams) }}"
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
            <div class="rounded-3xl border border-brand-soft/60 bg-white px-6 py-16 text-center shadow-[0_18px_44px_rgba(90,30,14,0.1)]">
                <div class="mx-auto max-w-md">
                    <div class="text-5xl">🛒</div>
                    <h2 class="mt-4 text-2xl font-bold text-brand-dark">{{ __('customer.cart_empty') }}</h2>
                    <p class="mt-3 text-brand-primary/75">
                        {{ __('customer.cart_empty_hint') }}
                    </p>

                    <div class="mt-6">
                        <a href="{{ route($menuRouteName, $routeParams) }}"
                           class="inline-flex items-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                            {{ __('customer.back_to_menu') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-end justify-center bg-black/45 p-4 sm:items-center" data-option-edit-modal>
    <div class="w-full max-w-lg rounded-3xl bg-white p-5 shadow-xl">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold text-brand-dark" data-option-edit-modal-title>{{ __('customer.select_options_title') }}</h3>
            <button type="button" class="rounded-full p-2 text-slate-500 hover:bg-slate-100" data-option-edit-modal-close>✕</button>
        </div>
        <div class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto" data-option-edit-modal-body></div>
        <div class="mt-5 flex gap-3">
            <button type="button" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100" data-option-edit-modal-cancel>{{ __('customer.cancel') }}</button>
            <button type="button" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark" data-option-edit-modal-confirm>{{ __('customer.confirm_add') }}</button>
        </div>
    </div>
</div>

<script>
(() => {
    const isDineIn = @json($isDineIn);
    const cartItemsContainer = document.querySelector('[data-cart-items]');
    const cartLineCount = document.querySelector('[data-cart-line-count]');
    const cartSubtotalEl = document.querySelector('[data-cart-subtotal]');
    const cartPayableEl = document.querySelector('[data-cart-payable]');
    const cartEstimatedReadyEl = document.querySelector('[data-cart-estimated-ready]');
    const couponDiscountEl = document.querySelector('[data-coupon-discount]');
    const couponDiscountHintEl = document.querySelector('[data-coupon-discount-hint]');
    const cartSyncNotice = document.querySelector('[data-cart-sync-notice]');
    const optionEditModal = document.querySelector('[data-option-edit-modal]');
    const optionEditModalTitle = document.querySelector('[data-option-edit-modal-title]');
    const optionEditModalBody = document.querySelector('[data-option-edit-modal-body]');
    const optionEditModalClose = document.querySelector('[data-option-edit-modal-close]');
    const optionEditModalCancel = document.querySelector('[data-option-edit-modal-cancel]');
    const optionEditModalConfirm = document.querySelector('[data-option-edit-modal-confirm]');
    const optionEditTriggers = document.querySelectorAll('[data-open-option-editor]');
    const cartSyncUrl = @json($isDineIn ? route('customer.dinein.cart.sync', $routeParams) : null);
    const cartBroadcastChannel = @json($isDineIn ? ('dinein-cart.' . $store->id . '.' . $table->qr_token) : null);
    const dineInClientStorageKey = 'dinein-realtime-client-id';
    const currentClientId = (() => {
        try {
            const existing = window.sessionStorage.getItem(dineInClientStorageKey);
            if (existing) {
                return existing;
            }

            const generated = typeof window.crypto?.randomUUID === 'function'
                ? window.crypto.randomUUID()
                : `dinein-${Date.now()}-${Math.random().toString(16).slice(2)}`;

            window.sessionStorage.setItem(dineInClientStorageKey, generated);

            return generated;
        } catch (_error) {
            return `dinein-${Date.now()}-${Math.random().toString(16).slice(2)}`;
        }
    })();
    const currencySymbol = @json($currencySymbol);
    const optionEditI18n = {
        optionsTitle: @json(__('customer.select_options_title')),
        optionsTitleWithProduct: @json(__('customer.select_options_title_with_product', ['product' => '__product__'])),
        requiredSuffix: @json(__('customer.required_suffix')),
        requiredError: @json(__('customer.option_required_error', ['group' => '__group__'])),
        maxSelectError: @json(__('customer.option_max_select_error', ['group' => '__group__', 'max' => '__max__'])),
        itemNoteLabel: @json(__('customer.item_note_label')),
        itemNotePlaceholder: @json(__('customer.item_note_placeholder')),
        free: @json(__('customer.free')),
        currencySymbol: @json($currencySymbol),
    };
    const couponReapplyMessage = @json(__('customer.coupon_reapply_after_cart_update'));
    let currentCartTotal = Number(@json((int) $total));
    let appliedCouponDiscount = Number(@json($isDineIn ? 0 : $oldAppliedCouponDiscount));
    let currentEstimatedReadyLabel = @json($estimatedReadyLabel);
    let activeOptionEditForm = null;
    let activeOptionEditGroups = [];
    let activeOptionAllowItemNote = false;
    let cartSyncNoticeTimer = null;

    const formatCurrency = (amount) => `${currencySymbol} ${Number(Math.max(Number(amount || 0), 0)).toLocaleString()}`;

    const updateSummaryTotals = () => {
        const payable = Math.max(currentCartTotal - appliedCouponDiscount, 0);

        if (cartSubtotalEl) {
            cartSubtotalEl.textContent = formatCurrency(currentCartTotal);
        }

        if (couponDiscountEl) {
            couponDiscountEl.textContent = `- ${formatCurrency(appliedCouponDiscount)}`;
        }

        if (cartPayableEl) {
            cartPayableEl.textContent = formatCurrency(payable);
        }

        if (cartEstimatedReadyEl) {
            cartEstimatedReadyEl.textContent = currentEstimatedReadyLabel;
        }
    };

    const showCartSyncNotice = () => {
        if (!cartSyncNotice) {
            return;
        }

        cartSyncNotice.classList.remove('hidden');
        window.clearTimeout(cartSyncNoticeTimer);
        cartSyncNoticeTimer = window.setTimeout(() => {
            cartSyncNotice.classList.add('hidden');
        }, 4000);
    };

    updateSummaryTotals();

    const sendCartRequest = async (form) => {
        const formData = new FormData(form);
        const csrf = form.querySelector('input[name="_token"]')?.value || '';
        const response = await window.fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(isDineIn ? { 'X-DineIn-Client-Id': currentClientId } : {}),
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
        });

        if (!response.ok) {
            throw new Error(`Cart request failed: ${response.status}`);
        }

        return response.json();
    };

    const fetchSyncedCart = async () => {
        if (!cartSyncUrl) {
            return null;
        }

        const response = await window.fetch(cartSyncUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Cart sync failed: ${response.status}`);
        }

        return response.json();
    };

    const syncCartPage = (payload) => {
        const cart = payload?.cart;
        if (!cart || !cartItemsContainer || !cartLineCount) {
            return;
        }

        if (cart.count <= 0) {
            window.location.reload();
            return;
        }

        const existingRows = Array.from(cartItemsContainer.querySelectorAll('[data-cart-item]'));
        const existingKeys = existingRows.map((row) => row.dataset.lineKey || '');
        const nextItems = Array.isArray(cart.items) ? cart.items : [];
        const nextKeys = nextItems.map((item) => item.line_key || '');

        if (
            existingRows.length !== nextItems.length
            || existingKeys.some((key) => !nextKeys.includes(key))
            || nextKeys.some((key) => !existingKeys.includes(key))
        ) {
            window.location.reload();
            return;
        }

        cartLineCount.textContent = `{{ __('customer.total_products_prefix') }} ${cart.line_count} {{ __('customer.total_products_suffix') }}`;
        currentCartTotal = Number(cart.total || 0);
        currentEstimatedReadyLabel = String(cart?.estimated_ready?.label || @json(__('customer.estimated_ready_time_unknown')));
        updateSummaryTotals();

        const itemMap = new Map(nextItems.map((item) => [item.line_key, item]));
        existingRows.forEach((row) => {
            const lineKey = row.dataset.lineKey || '';
            const item = itemMap.get(lineKey);

            if (!item) {
                window.location.reload();
                return;
            }

            const qty = row.querySelector('[data-cart-item-qty]');
            const subtotal = row.querySelector('[data-cart-item-subtotal]');
            if (qty) {
                qty.textContent = item.qty;
            }
            if (subtotal) {
                subtotal.textContent = item.subtotal_display;
            }
        });

        const appliedCode = normalizeCouponCode(appliedCouponCodeInput?.value);
        if (appliedCode !== '') {
            clearAppliedCoupon();
            setCouponError(couponReapplyMessage);
        }
    };

    const subscribeToCartUpdates = () => {
        if (!isDineIn || !window.Echo || !cartBroadcastChannel) {
            return;
        }

        window.Echo.channel(cartBroadcastChannel)
            .listen('.dinein.cart.updated', (event) => {
                if ((event?.source_client_id || '') === currentClientId) {
                    return;
                }

                fetchSyncedCart()
                    .then((payload) => {
                        syncCartPage(payload);
                        showCartSyncNotice();
                    })
                    .catch(() => {
                        window.location.reload();
                    });
            });
    };

    const closeOptionEditModal = () => {
        optionEditModal?.classList.add('hidden');
        optionEditModal?.classList.remove('flex');
        if (optionEditModalBody) {
            optionEditModalBody.innerHTML = '';
        }
        activeOptionEditForm = null;
        activeOptionEditGroups = [];
        activeOptionAllowItemNote = false;
    };

    const parseJsonSafe = (raw, fallback) => {
        try {
            const parsed = JSON.parse(raw || '');
            return parsed ?? fallback;
        } catch (_error) {
            return fallback;
        }
    };

    const openOptionEditModal = (trigger) => {
        const form = trigger.closest('form[data-option-edit-form]');
        if (!form || !optionEditModalBody || !optionEditModalTitle) {
            return;
        }

        const groups = parseJsonSafe(trigger.dataset.optionGroups, []);
        const selected = parseJsonSafe(trigger.dataset.selectedOptions, {});
        const allowItemNote = trigger.dataset.allowItemNote === '1';
        const currentNote = String(trigger.dataset.itemNote || '');
        const productName = String(trigger.dataset.productName || '');
        const selectedMap = {};

        if (selected && typeof selected === 'object') {
            Object.keys(selected).forEach((groupId) => {
                const group = selected[groupId];
                const items = Array.isArray(group?.items) ? group.items : [];
                selectedMap[groupId] = items
                    .map((choice) => String(choice?.id || '').trim())
                    .filter((value) => value !== '');
            });
        }

        activeOptionEditForm = form;
        activeOptionEditGroups = Array.isArray(groups) ? groups : [];
        activeOptionAllowItemNote = allowItemNote;
        optionEditModalTitle.textContent = productName !== ''
            ? optionEditI18n.optionsTitleWithProduct.replace('__product__', productName)
            : optionEditI18n.optionsTitle;
        optionEditModalBody.innerHTML = '';

        activeOptionEditGroups.forEach((group) => {
            const groupId = String(group?.id || '');
            if (!groupId) {
                return;
            }

            const type = group?.type === 'multiple' ? 'multiple' : 'single';
            const required = !!group?.required;
            const maxSelect = Number(group?.max_select || 99);
            const wrapper = document.createElement('div');
            wrapper.className = 'rounded-2xl border border-slate-200 p-4';
            wrapper.dataset.groupId = groupId;
            wrapper.dataset.groupType = type;
            wrapper.dataset.groupRequired = required ? '1' : '0';
            wrapper.dataset.groupMax = String(maxSelect);

            const title = document.createElement('div');
            title.className = 'mb-2 text-sm font-semibold text-slate-800';
            title.textContent = `${group?.name || groupId}${required ? optionEditI18n.requiredSuffix : ''}`;
            wrapper.appendChild(title);

            const choices = Array.isArray(group?.choices) ? group.choices : [];
            const preselected = Array.isArray(selectedMap[groupId]) ? selectedMap[groupId] : [];

            choices.forEach((choice, index) => {
                const choiceId = String(choice?.id || '');
                if (!choiceId) {
                    return;
                }

                const row = document.createElement('label');
                row.className = 'mb-2 flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-sm last:mb-0 hover:bg-slate-50';

                const left = document.createElement('div');
                left.className = 'flex items-center gap-2';

                const input = document.createElement('input');
                input.type = type === 'single' ? 'radio' : 'checkbox';
                input.name = `edit_opt_${groupId}` + (type === 'multiple' ? '[]' : '');
                input.value = choiceId;
                if (preselected.includes(choiceId)) {
                    input.checked = true;
                }
                if (required && type === 'single' && preselected.length === 0 && index === 0) {
                    input.checked = true;
                }

                const text = document.createElement('span');
                text.textContent = String(choice?.name || choiceId);
                left.appendChild(input);
                left.appendChild(text);

                const price = document.createElement('span');
                const value = Number(choice?.price || 0);
                price.className = 'text-xs font-semibold ' + (value > 0 ? 'text-brand-primary' : 'text-slate-500');
                price.textContent = value > 0 ? `+${optionEditI18n.currencySymbol} ${value}` : optionEditI18n.free;

                row.appendChild(left);
                row.appendChild(price);
                wrapper.appendChild(row);
            });

            optionEditModalBody.appendChild(wrapper);
        });

        if (allowItemNote) {
            const noteWrapper = document.createElement('div');
            noteWrapper.className = 'rounded-2xl border border-slate-200 p-4';

            const noteLabel = document.createElement('div');
            noteLabel.className = 'mb-2 text-sm font-semibold text-slate-800';
            noteLabel.textContent = optionEditI18n.itemNoteLabel;

            const noteInput = document.createElement('textarea');
            noteInput.rows = 3;
            noteInput.maxLength = 255;
            noteInput.value = currentNote;
            noteInput.placeholder = optionEditI18n.itemNotePlaceholder;
            noteInput.className = 'w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft';
            noteInput.setAttribute('data-option-edit-note-input', '1');

            noteWrapper.appendChild(noteLabel);
            noteWrapper.appendChild(noteInput);
            optionEditModalBody.appendChild(noteWrapper);
        }

        optionEditModal.classList.remove('hidden');
        optionEditModal.classList.add('flex');
    };

    optionEditTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => openOptionEditModal(trigger));
    });

    optionEditModalClose?.addEventListener('click', closeOptionEditModal);
    optionEditModalCancel?.addEventListener('click', closeOptionEditModal);
    optionEditModal?.addEventListener('click', (event) => {
        if (event.target === optionEditModal) {
            closeOptionEditModal();
        }
    });

    optionEditModalConfirm?.addEventListener('click', () => {
        if (!activeOptionEditForm || !optionEditModalBody) {
            closeOptionEditModal();
            return;
        }

        const payload = {};

        for (const group of activeOptionEditGroups) {
            const groupId = String(group?.id || '');
            if (!groupId) {
                continue;
            }

            const wrapper = optionEditModalBody.querySelector(`[data-group-id="${groupId}"]`);
            if (!wrapper) {
                continue;
            }

            const type = wrapper.dataset.groupType || 'single';
            const required = wrapper.dataset.groupRequired === '1';
            const maxSelect = Number(wrapper.dataset.groupMax || 99);
            const checked = Array.from(wrapper.querySelectorAll('input:checked')).map((input) => input.value);

            if (required && checked.length === 0) {
                alert(optionEditI18n.requiredError.replace('__group__', group?.name || groupId));
                return;
            }

            if (type === 'multiple' && checked.length > maxSelect) {
                alert(optionEditI18n.maxSelectError.replace('__group__', group?.name || groupId).replace('__max__', maxSelect));
                return;
            }

            if (checked.length > 0) {
                payload[groupId] = type === 'single' ? [checked[0]] : checked;
            }
        }

        const payloadInput = activeOptionEditForm.querySelector('[data-option-edit-payload]');
        if (payloadInput) {
            payloadInput.value = JSON.stringify(payload);
        }

        const noteField = activeOptionEditForm.querySelector('[data-option-edit-note]');
        if (noteField) {
            const noteInput = optionEditModalBody.querySelector('[data-option-edit-note-input]');
            noteField.value = activeOptionAllowItemNote && noteInput
                ? String(noteInput.value || '').trim()
                : '';
        }

        const formToSubmit = activeOptionEditForm;
        closeOptionEditModal();
        formToSubmit.submit();
    });

    document.querySelectorAll('[data-cart-update-form], [data-cart-remove-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (form.dataset.submitting === '1') {
                return;
            }

            form.dataset.submitting = '1';

            try {
                const payload = await sendCartRequest(form);
                syncCartPage(payload);
            } catch (_error) {
                form.submit();
            } finally {
                form.dataset.submitting = '0';
            }
        });
    });

    subscribeToCartUpdates();

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

    if (!checkoutForm) {
        return;
    }

    const phoneCheckUrl = checkoutForm.dataset.phoneCheckUrl || '';
    const couponCheckUrl = checkoutForm.dataset.couponCheckUrl || '';
    const couponInput = checkoutForm.querySelector('[data-coupon-input]');
    const couponApplyButton = checkoutForm.querySelector('[data-coupon-apply-button]');
    const couponError = checkoutForm.querySelector('[data-coupon-error]');
    const couponAppliedBox = checkoutForm.querySelector('[data-coupon-applied-box]');
    const couponAppliedCode = checkoutForm.querySelector('[data-coupon-applied-code]');
    const couponAppliedSummary = checkoutForm.querySelector('[data-coupon-applied-summary]');
    const appliedCouponCodeInput = checkoutForm.querySelector('[data-applied-coupon-code]');
    const appliedCouponSummaryInput = checkoutForm.querySelector('[data-applied-coupon-summary]');
    const appliedCouponDiscountInput = checkoutForm.querySelector('[data-applied-coupon-discount]');
    const promptMessage = @json(__('customer.guest_register_points_prompt'));
    const couponAfterSubmitHint = @json(__('customer.coupon_discount_after_submit'));
    const couponApplyFirstMessage = @json(__('customer.coupon_apply_first'));
    const couponEnterCodeMessage = @json(__('customer.coupon_enter_code'));
    const couponCannotValidateMessage = @json(__('customer.coupon_cannot_validate'));
    const couponValidateFailedMessage = @json(__('customer.coupon_validate_failed'));

    const normalizeCouponCode = (value) => String(value || '').trim().toUpperCase();

    const setCouponError = (message) => {
        if (!couponError) {
            return;
        }

        const text = String(message || '').trim();
        couponError.textContent = text;
        couponError.classList.toggle('hidden', text === '');
    };

    const clearAppliedCoupon = () => {
        appliedCouponDiscount = 0;

        if (appliedCouponCodeInput) {
            appliedCouponCodeInput.value = '';
        }

        if (appliedCouponSummaryInput) {
            appliedCouponSummaryInput.value = '';
        }

        if (appliedCouponDiscountInput) {
            appliedCouponDiscountInput.value = '0';
        }

        if (couponAppliedCode) {
            couponAppliedCode.textContent = '';
        }

        if (couponAppliedSummary) {
            couponAppliedSummary.textContent = '';
        }

        if (couponAppliedBox) {
            couponAppliedBox.classList.add('hidden');
        }

        if (couponDiscountHintEl) {
            couponDiscountHintEl.textContent = couponAfterSubmitHint;
        }

        updateSummaryTotals();
    };

    const renderAppliedCoupon = (code, summary, discount) => {
        appliedCouponDiscount = Math.max(Number(discount || 0), 0);

        if (appliedCouponCodeInput) {
            appliedCouponCodeInput.value = code;
        }

        if (appliedCouponSummaryInput) {
            appliedCouponSummaryInput.value = summary;
        }

        if (appliedCouponDiscountInput) {
            appliedCouponDiscountInput.value = String(appliedCouponDiscount);
        }

        if (couponAppliedCode) {
            couponAppliedCode.textContent = code;
        }

        if (couponAppliedSummary) {
            couponAppliedSummary.textContent = summary;
        }

        if (couponAppliedBox) {
            couponAppliedBox.classList.remove('hidden');
        }

        if (couponDiscountHintEl) {
            couponDiscountHintEl.textContent = summary || couponAfterSubmitHint;
        }

        updateSummaryTotals();
    };

    const validateCouponBeforeSubmit = () => {
        if (!couponInput) {
            return true;
        }

        const currentCode = normalizeCouponCode(couponInput.value);
        if (currentCode === '') {
            return true;
        }

        const appliedCode = normalizeCouponCode(appliedCouponCodeInput?.value);
        if (appliedCode === currentCode) {
            return true;
        }

        setCouponError(couponApplyFirstMessage);
        couponInput.focus();

        return false;
    };

    if (couponInput) {
        couponInput.addEventListener('input', () => {
            couponInput.value = normalizeCouponCode(couponInput.value);
            clearAppliedCoupon();
            setCouponError('');
        });

        couponInput.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            couponApplyButton?.click();
        });
    }

    if (couponInput && couponApplyButton) {
        couponApplyButton.addEventListener('click', async () => {
            if (couponApplyButton.dataset.submitting === '1') {
                return;
            }

            const code = normalizeCouponCode(couponInput.value);
            if (code === '') {
                clearAppliedCoupon();
                setCouponError(couponEnterCodeMessage);
                couponInput.focus();
                return;
            }

            if (!couponCheckUrl) {
                setCouponError(couponCannotValidateMessage);
                return;
            }

            couponApplyButton.dataset.submitting = '1';
            setCouponError('');

            try {
                const endpoint = new URL(couponCheckUrl, window.location.origin);
                endpoint.searchParams.set('coupon_code', code);

                const customerEmail = checkoutForm.querySelector('input[name="customer_email"]');
                const customerPhone = checkoutForm.querySelector('input[name="customer_phone"]');
                if (customerEmail?.value) {
                    endpoint.searchParams.set('customer_email', customerEmail.value);
                }

                if (customerPhone?.value) {
                    endpoint.searchParams.set('customer_phone', customerPhone.value);
                }

                const response = await window.fetch(endpoint.toString(), {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload?.ok) {
                    clearAppliedCoupon();
                    setCouponError(payload?.error || couponValidateFailedMessage);
                    return;
                }

                const summary = String(payload?.coupon?.summary || '').trim();
                const discount = Number(payload?.coupon?.discount || 0);
                renderAppliedCoupon(code, summary, discount);
                setCouponError('');
            } catch (_error) {
                clearAppliedCoupon();
                setCouponError(couponCannotValidateMessage);
            } finally {
                couponApplyButton.dataset.submitting = '0';
            }
        });
    }

    checkoutForm.addEventListener('submit', async (event) => {
        if (checkoutForm.dataset.submitting === '1') {
            return;
        }

        event.preventDefault();

        if (!validateCouponBeforeSubmit()) {
            return;
        }

        if (!isGuest || !createAccountInput) {
            checkoutForm.dataset.submitting = '1';
            checkoutForm.submit();
            return;
        }

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

