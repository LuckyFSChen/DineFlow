@extends('layouts.app')

@section('title', __('merchant_order.page_title'))

@section('content')
@php
    $embedded = (bool) ($embedded ?? request()->boolean('embedded'));
    $workspace = (bool) ($workspace ?? false);
    $orderFormRouteParameters = ['store' => $store];
    if ($workspace) {
        $orderFormRouteParameters['workspace'] = 1;
    } elseif (request()->boolean('embedded')) {
        $orderFormRouteParameters['embedded'] = 1;
    }
    $totalProducts = $categories->sum(fn ($category) => $category->products->count());
    $activeTableCount = $tables->where('status', '!=', 'inactive')->count();
    $phoneDigits = strtolower((string) ($store->country_code ?? 'tw')) === 'cn' ? 11 : 10;
    $numberLocale = str_replace('_', '-', app()->getLocale());
    $oldCouponCode = strtoupper(trim((string) old('coupon_code', '')));
    $oldAppliedCouponSummary = trim((string) old('applied_coupon_summary', ''));
    $oldAppliedCouponDiscount = max((int) old('applied_coupon_discount', 0), 0);
    $oldAppliedCouponName = trim((string) old('applied_coupon_name', ''));
    $ui = [
        'selectedTableNone' => __('merchant_order.selected_table_none'),
        'selectedTablePrefix' => __('merchant_order.selected_table_prefix'),
        'tableOpenOrderBadge' => __('merchant_order.table_open_order_badge'),
        'tableNewOrderBadge' => __('merchant_order.table_new_order_badge'),
        'openOrderPrefix' => __('merchant_order.open_order_prefix'),
        'itemNoteLabel' => __('merchant_order.item_note_label'),
        'noDescription' => __('merchant_order.no_description'),
        'optionGroupsLabel' => __('merchant_order.option_groups_label', ['count' => ':count']),
        'noOptionsLabel' => __('merchant_order.no_options_label'),
        'requiredGroupsLabel' => __('merchant_order.required_groups_label', ['count' => ':count']),
        'noRequiredGroupsLabel' => __('merchant_order.no_required_groups_label'),
        'allowItemNote' => __('merchant_order.allow_item_note'),
        'modalGroupFallback' => __('merchant_order.modal_group_fallback'),
        'modalSingleChoice' => __('merchant_order.modal_single_choice'),
        'modalMultipleChoice' => __('merchant_order.modal_multiple_choice', ['count' => ':count']),
        'modalRequiredTag' => __('merchant_order.modal_required_tag'),
        'modalOptionalTag' => __('merchant_order.modal_optional_tag'),
        'modalFreeChoice' => __('merchant_order.modal_free_choice'),
        'modalExtraPricePrefix' => __('merchant_order.modal_extra_price_prefix'),
        'validationRequiredGroupAlert' => __('merchant_order.validation_required_group_alert', ['group' => ':group']),
        'couponLookupButton' => __('merchant_order.coupon_lookup_button'),
        'couponLookupLoading' => __('merchant_order.coupon_lookup_loading'),
        'couponPhoneRequired' => __('merchant_order.coupon_phone_required'),
        'couponAddItemsFirst' => __('merchant_order.coupon_add_items_first'),
        'couponNoAvailable' => __('merchant_order.coupon_no_available'),
        'couponLookupFailed' => __('merchant_order.coupon_lookup_failed'),
        'couponSelectionStale' => __('merchant_order.coupon_selection_stale'),
        'couponAppliedLabel' => __('merchant_order.coupon_applied_label'),
        'couponAppliedState' => __('merchant_order.coupon_applied_state'),
        'couponMemberPoints' => __('merchant_order.coupon_member_points', ['points' => ':points']),
        'couponApplyButton' => __('merchant_order.coupon_apply_button'),
        'couponRemoveButton' => __('merchant_order.coupon_remove_button'),
        'orderTypeDineIn' => __('merchant_order.order_type_dine_in'),
        'orderTypeTakeout' => __('merchant_order.order_type_takeout'),
        'takeoutSelectedLabel' => __('merchant_order.takeout_selected_label'),
        'submitDineInOrder' => __('merchant_order.submit_dine_in_order'),
        'submitTakeoutOrder' => __('merchant_order.submit_takeout_order'),
    ];
@endphp

<div
    class="{{ $workspace ? '' : 'min-h-screen ' }}bg-slate-50"
    x-data="merchantOrderPage({
        categories: @js($categoriesPayload),
        tables: @js($tablesPayload),
        tablesRefreshUrl: @js(route('admin.stores.orders.tables', ['store' => $store])),
        couponLookupUrl: @js(route('admin.stores.orders.coupons', ['store' => $store])),
        initialCartItems: @js($initialCartItems),
        currencySymbol: @js($currencySymbol),
        defaultOrderType: @js($defaultOrderType),
        defaultTableId: @js($defaultTableId),
        defaultCustomerPhone: @js($defaultCustomerPhone),
        initialCouponCode: @js($oldCouponCode),
        initialCouponSummary: @js($oldAppliedCouponSummary),
        initialCouponDiscount: @js($oldAppliedCouponDiscount),
        initialCouponName: @js($oldAppliedCouponName),
        initialCouponError: @js($errors->first('coupon_code')),
        locale: @js($numberLocale),
        ui: @js($ui),
    })"
    x-init="init()"
>
    <div class="w-full px-4 pb-32 pt-6 sm:px-6 lg:px-8 xl:pb-6">
        <div class="admin-hero mb-6 rounded-3xl px-5 py-5 md:px-7">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('merchant_order.hero_badge') }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ __('merchant_order.hero_title') }}</h1>
                    <p class="mt-2 text-slate-600">{{ __('merchant_order.hero_description', ['store' => $store->name]) }}</p>
                </div>

                @if (! $embedded)
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-900 bg-slate-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                            {{ __('merchant_order.back_to_stores') }}
                        </a>
                        <a href="{{ route('admin.stores.workspace', ['store' => $store, 'tab' => 'boards']) }}" class="inline-flex items-center justify-center rounded-2xl border border-orange-300 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-700 transition hover:bg-orange-100">
                            {{ __('merchant_order.all_boards') }}
                        </a>
                    </div>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('merchant_order.kpi_total_tables') }}</p>
                    <p class="value mt-2 text-slate-900">{{ $tables->count() }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('merchant_order.kpi_active_tables') }}</p>
                    <p class="value mt-2 text-emerald-700">{{ $activeTableCount }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('merchant_order.kpi_categories') }}</p>
                    <p class="value mt-2 text-cyan-700">{{ $categories->count() }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('merchant_order.kpi_products') }}</p>
                    <p class="value mt-2 text-indigo-700">{{ $totalProducts }}</p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form id="merchant-order-form" method="POST" action="{{ route('admin.stores.orders.store', $orderFormRouteParameters) }}" class="grid min-w-0 gap-6 xl:grid-cols-[380px,minmax(0,1fr)]">
            @csrf

            <aside class="min-w-0 space-y-6">
                <section class="hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:block">
                    <h2 class="text-lg font-bold text-slate-900">{{ __('merchant_order.order_type_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('merchant_order.order_type_desc') }}</p>

                    <input type="hidden" name="order_type" :value="orderType">

                    <div class="mt-4 grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1">
                        <button
                            type="button"
                            @click="setOrderType('dine_in')"
                            class="rounded-xl px-4 py-3 text-sm font-semibold transition"
                            :class="isDineIn()
                                ? 'bg-white text-cyan-700 shadow-sm ring-1 ring-cyan-200'
                                : 'text-slate-600 hover:bg-white/70 hover:text-slate-900'"
                        >
                            {{ __('merchant_order.order_type_dine_in') }}
                        </button>
                        <button
                            type="button"
                            @click="setOrderType('takeout')"
                            class="rounded-xl px-4 py-3 text-sm font-semibold transition"
                            :class="isTakeout()
                                ? 'bg-white text-cyan-700 shadow-sm ring-1 ring-cyan-200'
                                : 'text-slate-600 hover:bg-white/70 hover:text-slate-900'"
                        >
                            {{ __('merchant_order.order_type_takeout') }}
                        </button>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ __('merchant_order.table_picker_title') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('merchant_order.table_picker_desc') }}</p>
                        </div>
                        <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700" x-text="selectedTableLabel()"></span>
                    </div>

                    <input type="hidden" name="dining_table_id" :value="isDineIn() ? (selectedTableId || '') : ''">

                    <div x-show="isTakeout()" x-cloak class="mt-4 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-800">
                        {{ __('merchant_order.takeout_no_table_hint') }}
                    </div>

                    <div x-show="isDineIn()" x-cloak class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <template x-for="table in tables" :key="table.id">
                            <button
                                type="button"
                                @click="selectTable(table.id)"
                                class="rounded-2xl border p-4 text-left transition"
                                :class="selectedTableId === table.id
                                    ? 'border-cyan-500 bg-cyan-50 shadow-sm ring-2 ring-cyan-200'
                                    : (table.status === 'inactive'
                                        ? 'border-slate-200 bg-slate-50 text-slate-400'
                                        : 'border-slate-200 bg-white hover:border-cyan-300 hover:bg-cyan-50/40')"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold" :class="table.status === 'inactive' ? 'text-slate-400' : 'text-slate-900'">
                                            {{ __('merchant_order.table_label') }} <span x-text="table.table_no"></span>
                                        </p>
                                        <p class="mt-1 text-xs" :class="table.status === 'inactive' ? 'text-slate-400' : 'text-slate-500'" x-text="table.status_label"></p>
                                    </div>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                        :class="table.open_order ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                                        x-text="table.open_order ? ui.tableOpenOrderBadge : ui.tableNewOrderBadge"
                                    ></span>
                                </div>

                                <template x-if="table.open_order">
                                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                        <div><span x-text="ui.openOrderPrefix"></span><span x-text="table.open_order.order_no"></span></div>
                                        <div class="mt-1" x-text="openOrderSummary(table.open_order)"></div>
                                    </div>
                                </template>
                            </button>
                        </template>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">{{ __('merchant_order.customer_info_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('merchant_order.customer_info_desc') }}</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="customer_name" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('merchant_order.customer_name') }}</label>
                            <input
                                id="customer_name"
                                name="customer_name"
                                type="text"
                                value="{{ $defaultCustomerName }}"
                                placeholder="{{ __('merchant_order.customer_name_placeholder') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                            >
                        </div>

                        <div>
                            <label for="customer_phone" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('merchant_order.customer_phone') }}</label>
                            <input
                                id="customer_phone"
                                name="customer_phone"
                                type="tel"
                                x-model="customerPhone"
                                @input="handleCustomerPhoneInput()"
                                inputmode="numeric"
                                data-phone-digits="{{ $phoneDigits }}"
                                value="{{ $defaultCustomerPhone }}"
                                placeholder="{{ __('merchant_order.customer_phone_placeholder', ['digits' => $phoneDigits]) }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                            >
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">{{ __('merchant_order.coupon_section_title') }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('merchant_order.coupon_section_desc') }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="lookupAvailableCoupons()"
                                    :disabled="couponLookupLoading"
                                    class="inline-flex items-center justify-center rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    x-text="couponLookupLoading ? text('couponLookupLoading') : text('couponLookupButton')"
                                ></button>
                            </div>

                            <input type="hidden" name="coupon_code" :value="selectedCouponCode || ''">
                            <input type="hidden" name="applied_coupon_summary" :value="selectedCouponSummary || ''">
                            <input type="hidden" name="applied_coupon_discount" :value="selectedCouponDiscount || 0">
                            <input type="hidden" name="applied_coupon_name" :value="selectedCouponName || ''">

                            <div x-show="couponLookupError" x-cloak class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" x-text="couponLookupError"></div>

                            <template x-if="couponMember">
                                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800">
                                    <div class="font-semibold" x-text="couponMember.name"></div>
                                    <div class="mt-1 text-xs" x-text="text('couponMemberPoints', { points: formatNumber(couponMember.points_balance || 0) })"></div>
                                </div>
                            </template>

                            <div x-show="selectedCouponCode" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm text-emerald-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold">
                                            <span>{{ __('merchant_order.coupon_applied_label') }}</span>
                                            <span x-text="selectedCouponCode"></span>
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-emerald-700" x-text="selectedCouponSummary"></p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="clearAppliedCoupon()"
                                        class="shrink-0 rounded-lg border border-emerald-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                        x-text="text('couponRemoveButton')"
                                    ></button>
                                </div>
                            </div>

                            <div x-show="availableCoupons.length > 0" x-cloak class="mt-3 space-y-2">
                                <template x-for="coupon in availableCoupons" :key="coupon.code">
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-semibold text-slate-900" x-text="coupon.name || coupon.code"></p>
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600" x-text="coupon.code"></span>
                                                </div>
                                                <p class="mt-2 text-xs leading-5 text-slate-500" x-text="coupon.summary"></p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="applyCoupon(coupon)"
                                                class="shrink-0 rounded-lg border px-3 py-2 text-xs font-semibold transition"
                                                :class="selectedCouponCode === coupon.code
                                                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                                    : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100'"
                                                x-text="selectedCouponCode === coupon.code ? text('couponAppliedState') : text('couponApplyButton')"
                                            ></button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label for="note" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('merchant_order.order_note') }}</label>
                            <textarea
                                id="note"
                                name="note"
                                rows="3"
                                placeholder="{{ __('merchant_order.order_note_placeholder') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                            >{{ $defaultNote }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ __('merchant_order.cart_title') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('merchant_order.cart_desc') }}</p>
                        </div>
                        <button
                            type="button"
                            @click="clearCart()"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                            x-show="cartItems.length > 0"
                        >
                            {{ __('merchant_order.clear_cart') }}
                        </button>
                    </div>

                    <template x-if="isDineIn() && selectedTable">
                        <div class="mt-4 rounded-2xl border px-4 py-3 text-sm"
                             :class="selectedTable.open_order ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
                            <template x-if="selectedTable.open_order">
                                <div>
                                    {{ __('merchant_order.existing_order_notice_before') }}
                                    <span class="font-semibold" x-text="selectedTable.table_no"></span>
                                    {{ __('merchant_order.existing_order_notice_after_table') }}
                                    {{ __('merchant_order.existing_order_notice_append') }}
                                    <span class="font-semibold" x-text="selectedTable.open_order.order_no"></span>
                                </div>
                            </template>
                            <template x-if="!selectedTable.open_order">
                                <div>
                                    {{ __('merchant_order.new_order_notice_before') }}
                                    <span class="font-semibold" x-text="selectedTable.table_no"></span>
                                    {{ __('merchant_order.new_order_notice_after_table') }}
                                </div>
                            </template>
                        </div>
                    </template>

                    <div x-show="isTakeout()" x-cloak class="mt-4 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-800">
                        {{ __('merchant_order.takeout_order_notice') }}
                    </div>

                    <div class="mt-4 space-y-3" x-show="cartItems.length > 0">
                        <template x-for="(item, index) in cartItems" :key="item.uid">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900" x-text="item.productName"></p>
                                        <p class="mt-1 text-xs text-slate-500" x-text="item.categoryName || ''"></p>
                                        <template x-if="item.optionLabel">
                                            <p class="mt-2 text-xs text-cyan-700" x-text="item.optionLabel"></p>
                                        </template>
                                        <template x-if="item.itemNote">
                                            <p class="mt-1 text-xs text-amber-700"><span x-text="ui.itemNoteLabel"></span><span x-text="item.itemNote"></span></p>
                                        </template>
                                    </div>
                                    <button type="button" @click="removeItem(index)" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                        {{ __('merchant_order.remove_item') }}
                                    </button>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="inline-flex items-center rounded-full border border-slate-300 bg-white">
                                        <button type="button" @click="decreaseQty(index)" class="px-3 py-1.5 text-sm font-semibold text-slate-700">-</button>
                                        <span class="min-w-10 px-2 text-center text-sm font-semibold text-slate-900" x-text="item.qty"></span>
                                        <button type="button" @click="increaseQty(index)" class="px-3 py-1.5 text-sm font-semibold text-slate-700">+</button>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-xs text-slate-500">{{ __('merchant_order.unit_price') }} <span x-text="money(item.price)"></span></p>
                                        <p class="text-sm font-semibold text-slate-900" x-text="money(item.subtotal)"></p>
                                    </div>
                                </div>

                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.productId">
                                <input type="hidden" :name="`items[${index}][qty]`" :value="item.qty">
                                <input type="hidden" :name="`items[${index}][option_payload]`" :value="item.optionPayload || ''">
                                <input type="hidden" :name="`items[${index}][item_note]`" :value="item.itemNote || ''">
                            </div>
                        </template>
                    </div>

                    <div x-show="cartItems.length === 0" class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        {{ __('merchant_order.empty_cart') }}
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-900 px-4 py-4 text-white">
                        <div class="flex items-center justify-between gap-3 text-sm text-slate-300">
                            <span class="min-w-0">{{ __('merchant_order.total_items') }}</span>
                            <span class="shrink-0" x-text="totalQty"></span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-3 text-lg font-bold">
                            <span class="min-w-0">{{ __('merchant_order.total_amount') }}</span>
                            <span class="shrink-0" x-text="money(cartTotal)"></span>
                        </div>
                        <div x-show="selectedCouponCode && selectedCouponDiscount > 0" x-cloak class="mt-2 flex items-center justify-between gap-3 text-sm text-emerald-300">
                            <span class="min-w-0">{{ __('merchant_order.coupon_discount_label') }}</span>
                            <span class="shrink-0" x-text="'- ' + money(selectedCouponDiscount)"></span>
                        </div>
                        <div x-show="selectedCouponCode" x-cloak class="mt-2 flex items-center justify-between gap-3 border-t border-slate-700 pt-2 text-sm font-semibold text-white">
                            <span class="min-w-0">{{ __('merchant_order.estimated_payable_amount') }}</span>
                            <span class="shrink-0" x-text="money(estimatedPayable)"></span>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-cyan-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                        :disabled="(isDineIn() && !selectedTableId) || cartItems.length === 0"
                        x-text="isTakeout() ? text('submitTakeoutOrder') : text('submitDineInOrder')"
                    >
                    </button>
                </section>
            </aside>

            <section class="min-w-0 space-y-6">
                <div class="md:hidden sticky top-16 z-30 -mt-1">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur">
                        <div class="flex min-w-0 gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                            <template x-for="category in categories" :key="`mobile-${category.id}`">
                                <button
                                    type="button"
                                    class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-full border px-4 py-2 text-sm font-semibold transition"
                                    @click="scrollToCategory(category.id)"
                                    :class="activeCategoryId === category.id
                                        ? 'border-cyan-500 bg-cyan-50 text-cyan-700'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-cyan-300 hover:text-cyan-700'"
                                >
                                    <span x-text="category.name"></span>
                                    <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px] text-slate-500" x-text="category.product_count"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:block">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ __('merchant_order.catalog_title') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('merchant_order.catalog_desc') }}</p>
                        </div>
                        <div class="admin-pill-nav inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold text-slate-700">
                            <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-700">{{ __('merchant_order.catalog_hint_badge') }}</span>
                            <span>{{ __('merchant_order.catalog_hint_text') }}</span>
                        </div>
                    </div>

                </div>

                <div class="md:grid md:grid-cols-[13rem,minmax(0,1fr)] md:items-start md:gap-6 xl:grid-cols-[15rem,minmax(0,1fr)]">
                    <div class="hidden md:block md:self-stretch">
                        <aside class="md:sticky md:top-24">
                            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">{{ __('merchant_order.catalog_hint_badge') }}</p>
                                <p class="mt-2 text-sm text-slate-500">{{ __('merchant_order.catalog_hint_text') }}</p>

                                <div class="mt-4 space-y-2">
                                    <template x-for="category in categories" :key="`sidebar-${category.id}`">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between gap-3 rounded-2xl border px-3 py-3 text-left text-sm font-semibold transition"
                                            @click="scrollToCategory(category.id)"
                                            :class="activeCategoryId === category.id
                                                ? 'border-cyan-500 bg-cyan-50 text-cyan-700 shadow-sm'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-cyan-300 hover:bg-slate-50 hover:text-slate-900'"
                                        >
                                            <span class="truncate" x-text="category.name"></span>
                                            <span
                                                class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                :class="activeCategoryId === category.id
                                                    ? 'bg-white text-cyan-700'
                                                    : 'bg-slate-100 text-slate-500'"
                                                x-text="category.product_count"
                                            ></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </aside>
                    </div>

                    <div class="space-y-6">
                        <template x-for="category in categories" :key="category.id">
                            <section
                                class="scroll-mt-36 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:scroll-mt-28 xl:scroll-mt-24"
                                :id="`category-${category.id}`"
                                :data-category-id="category.id"
                                data-order-category-section
                            >
                                <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-900" x-text="category.name"></h3>
                                        <p class="mt-1 text-sm text-slate-500">
                                            <span x-text="category.product_count"></span> {{ __('merchant_order.products_count_suffix') }}
                                            <template x-if="category.prep_time_minutes">
                                                <span> / {{ __('merchant_order.prep_time_prefix') }} <span x-text="category.prep_time_minutes"></span> {{ __('merchant_order.minutes_unit') }}</span>
                                            </template>
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="w-fit rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 xl:hidden"
                                        @click="scrollToCategory(category.id)"
                                    >
                                        {{ __('merchant_order.back_to_category') }}
                                    </button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                                    <template x-for="product in category.products" :key="product.id">
                                        <article class="flex h-full flex-col rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="flex items-start gap-4">
                                                <template x-if="product.image_url">
                                                    <img :src="product.image_url" :alt="product.name" class="h-20 w-20 rounded-2xl object-cover ring-1 ring-slate-200">
                                                </template>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <h4 class="text-base font-semibold text-slate-900" x-text="product.name"></h4>
                                                            <p class="mt-1 text-sm font-semibold text-cyan-700" x-text="money(product.price)"></p>
                                                        </div>
                                                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200" x-text="product.category_name"></span>
                                                    </div>

                                                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600" x-text="productDescription(product)"></p>

                                                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
                                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700" x-text="productOptionGroupsLabel(product)"></span>
                                                        <template x-if="product.option_group_count > 0">
                                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700" x-text="productRequiredGroupsLabel(product)"></span>
                                                        </template>
                                                        <template x-if="product.allow_item_note">
                                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">{{ __('merchant_order.allow_item_note') }}</span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-5 flex items-center justify-between gap-3">
                                                <div class="text-xs text-slate-500">{{ __('merchant_order.product_card_hint') }}</div>
                                                <button
                                                    type="button"
                                                    @click="openProduct(product)"
                                                    class="inline-flex items-center justify-center rounded-2xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-500"
                                                >
                                                    {{ __('merchant_order.add_to_order') }}
                                                </button>
                                            </div>
                                        </article>
                                    </template>
                                </div>
                            </section>
                        </template>
                    </div>
                </div>
            </section>
        </form>

        <div
            x-cloak
            class="fixed inset-x-0 bottom-0 z-[80] border-t border-slate-200 bg-white/95 px-3 py-3 shadow-[0_-18px_40px_rgba(15,23,42,0.14)] backdrop-blur xl:hidden"
        >
            <div class="mx-auto grid w-full max-w-4xl grid-cols-[minmax(0,1fr),minmax(8.5rem,auto)] items-stretch gap-2">
                <button
                    type="button"
                    @click="cartDetailsOpen = true"
                    class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-left transition hover:bg-slate-100"
                >
                    <div class="flex min-w-0 items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-slate-500">{{ __('merchant_order.total_items') }}</span>
                        <span class="text-base font-bold text-slate-900 tabular-nums" x-text="totalQty"></span>
                    </div>
                    <div class="mt-1 flex min-w-0 items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-slate-500">{{ __('merchant_order.total_amount') }}</span>
                        <span class="min-w-0 truncate text-base font-bold text-cyan-700 tabular-nums" x-text="money(estimatedPayable)"></span>
                    </div>
                </button>

                <div class="grid min-w-0 grid-rows-2 gap-2">
                    <button
                        type="button"
                        @click="cartDetailsOpen = true"
                        class="inline-flex min-h-0 items-center justify-center whitespace-nowrap rounded-2xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        {{ __('merchant_order.cart_title') }}
                    </button>

                    <button
                        type="submit"
                        form="merchant-order-form"
                        class="inline-flex min-h-0 items-center justify-center whitespace-nowrap rounded-2xl bg-cyan-600 px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                        :disabled="(isDineIn() && !selectedTableId) || cartItems.length === 0"
                        x-text="isTakeout() ? text('submitTakeoutOrder') : text('submitDineInOrder')"
                    ></button>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="cartDetailsOpen"
        x-cloak
        x-transition.opacity
        class="admin-modal-viewport fixed z-[230] overflow-hidden bg-slate-950/60 px-4 py-6 sm:px-6 sm:py-8"
        @keydown.escape.window="cartDetailsOpen = false"
    >
        <div class="mx-auto flex h-full max-w-2xl items-end justify-center sm:items-center">
            <div class="admin-modal-panel flex min-h-0 w-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl" @click.outside="cartDetailsOpen = false">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ __('merchant_order.cart_title') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('merchant_order.cart_desc') }}</p>
                    </div>
                    <button type="button" @click="cartDetailsOpen = false" class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m5 5 10 10M15 5 5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                    <div class="space-y-3" x-show="cartItems.length > 0">
                        <template x-for="(item, index) in cartItems" :key="`details-${item.uid}`">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900" x-text="item.productName"></p>
                                        <p class="mt-1 text-xs text-slate-500" x-text="item.categoryName || ''"></p>
                                        <template x-if="item.optionLabel">
                                            <p class="mt-2 text-xs text-cyan-700" x-text="item.optionLabel"></p>
                                        </template>
                                        <template x-if="item.itemNote">
                                            <p class="mt-1 text-xs text-amber-700"><span x-text="ui.itemNoteLabel"></span><span x-text="item.itemNote"></span></p>
                                        </template>
                                    </div>
                                    <button type="button" @click="removeItem(index)" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                        {{ __('merchant_order.remove_item') }}
                                    </button>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="inline-flex items-center rounded-full border border-slate-300 bg-white">
                                        <button type="button" @click="decreaseQty(index)" class="px-3 py-1.5 text-sm font-semibold text-slate-700">-</button>
                                        <span class="min-w-10 px-2 text-center text-sm font-semibold text-slate-900" x-text="item.qty"></span>
                                        <button type="button" @click="increaseQty(index)" class="px-3 py-1.5 text-sm font-semibold text-slate-700">+</button>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-xs text-slate-500">{{ __('merchant_order.unit_price') }} <span x-text="money(item.price)"></span></p>
                                        <p class="text-sm font-semibold text-slate-900" x-text="money(item.subtotal)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="cartItems.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        {{ __('merchant_order.empty_cart') }}
                    </div>
                </div>

                <div class="shrink-0 border-t border-slate-200 bg-white px-5 py-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-900 px-4 py-4 text-white">
                        <div class="flex items-center justify-between gap-3 text-sm text-slate-300">
                            <span>{{ __('merchant_order.total_items') }}</span>
                            <span x-text="totalQty"></span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-3 text-lg font-bold">
                            <span>{{ __('merchant_order.total_amount') }}</span>
                            <span x-text="money(cartTotal)"></span>
                        </div>
                        <div x-show="selectedCouponCode && selectedCouponDiscount > 0" x-cloak class="mt-2 flex items-center justify-between gap-3 text-sm text-emerald-300">
                            <span>{{ __('merchant_order.coupon_discount_label') }}</span>
                            <span x-text="'- ' + money(selectedCouponDiscount)"></span>
                        </div>
                        <div x-show="selectedCouponCode" x-cloak class="mt-2 flex items-center justify-between gap-3 border-t border-slate-700 pt-2 text-sm font-semibold text-white">
                            <span>{{ __('merchant_order.estimated_payable_amount') }}</span>
                            <span x-text="money(estimatedPayable)"></span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="cartDetailsOpen = false"
                        class="mt-3 inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        {{ __('merchant_order.modal_cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="modalOpen"
        x-cloak
        x-transition.opacity
        class="admin-modal-viewport fixed z-[220] overflow-hidden bg-slate-950/60 px-4 py-6 sm:px-6 sm:py-8"
        @keydown.escape.window="closeModal()"
    >
        <div class="mx-auto flex h-full max-w-4xl items-center justify-center">
            <div class="admin-modal-panel flex min-h-0 w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl" @click.outside="closeModal()">
                <div class="shrink-0 flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700" x-text="modalProduct?.category_name || ''"></p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900" x-text="modalProduct?.name || ''"></h3>
                        <p class="mt-2 text-sm text-slate-500" x-text="productDescription(modalProduct)"></p>
                    </div>
                    <button type="button" @click="closeModal()" class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m5 5 10 10M15 5 5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <div class="grid min-h-0 flex-1 gap-6 overflow-y-auto px-6 py-6 xl:grid-cols-[minmax(0,1fr),280px] xl:overflow-hidden">
                    <div class="space-y-5 xl:min-h-0 xl:overflow-y-auto xl:pr-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-slate-500">{{ __('merchant_order.modal_base_price') }}</span>
                                <span class="text-lg font-bold text-cyan-700" x-text="modalProduct ? money(modalProduct.price) : ''"></span>
                            </div>
                        </div>

                        <template x-if="modalProduct && modalProduct.option_group_count > 0">
                            <div class="space-y-4">
                                <template x-for="group in modalProduct.option_groups" :key="group.id">
                                    <section class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-semibold text-slate-900" x-text="group.name || ui.modalGroupFallback"></h4>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    <span x-text="groupTypeLabel(group)"></span>
                                                    <template x-if="group.required">
                                                        <span> / {{ __('merchant_order.modal_required_tag') }}</span>
                                                    </template>
                                                </p>
                                            </div>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                                  :class="group.required ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600'"
                                                  x-text="group.required ? ui.modalRequiredTag : ui.modalOptionalTag"></span>
                                        </div>

                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <template x-for="choice in group.choices || []" :key="choice.id">
                                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 transition hover:border-cyan-300 hover:bg-cyan-50/40">
                                                    <template x-if="group.type === 'multiple'">
                                                        <input
                                                            type="checkbox"
                                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                                                            :checked="isChecked(group.id, choice.id)"
                                                            @change="toggleMultipleChoice(group, choice.id, $event.target.checked)"
                                                        >
                                                    </template>
                                                    <template x-if="group.type !== 'multiple'">
                                                        <input
                                                            type="radio"
                                                            class="mt-1 h-4 w-4 border-slate-300 text-cyan-600 focus:ring-cyan-500"
                                                            :name="`modal-group-${group.id}`"
                                                            :checked="isSelected(group.id, choice.id)"
                                                            @change="selectSingleChoice(group.id, choice.id)"
                                                        >
                                                    </template>

                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-slate-900" x-text="choice.name"></p>
                                                        <p class="mt-1 text-xs text-slate-500" x-text="choicePriceLabel(choice)"></p>
                                                    </div>
                                                </label>
                                            </template>
                                        </div>
                                    </section>
                                </template>
                            </div>
                        </template>

                        <template x-if="modalProduct && modalProduct.allow_item_note">
                            <div>
                                <label for="modal-item-note" class="mb-1 block text-xs font-semibold text-slate-600">{{ __('merchant_order.modal_item_note_label') }}</label>
                                <textarea
                                    id="modal-item-note"
                                    rows="3"
                                    x-model="modalItemNote"
                                    placeholder="{{ __('merchant_order.modal_item_note_placeholder') }}"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                ></textarea>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-4 self-start rounded-3xl border border-slate-200 bg-slate-50 p-5 xl:sticky xl:top-0">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('merchant_order.modal_summary_title') }}</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900" x-text="money(modalSubtotal())"></p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500">{{ __('merchant_order.modal_quantity_label') }}</p>
                            <div class="mt-2 inline-flex items-center rounded-full border border-slate-300 bg-white">
                                <button type="button" @click="modalQty = Math.max(1, modalQty - 1)" class="px-4 py-2 text-sm font-semibold text-slate-700">-</button>
                                <span class="min-w-12 px-2 text-center text-sm font-semibold text-slate-900" x-text="modalQty"></span>
                                <button type="button" @click="modalQty = modalQty + 1" class="px-4 py-2 text-sm font-semibold text-slate-700">+</button>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                            <div class="flex items-center justify-between gap-3">
                                <span>{{ __('merchant_order.modal_extra_price') }}</span>
                                <span class="font-semibold text-slate-900" x-text="money(selectedExtraPrice())"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <span>{{ __('merchant_order.modal_unit_price') }}</span>
                                <span class="font-semibold text-slate-900" x-text="modalUnitPriceLabel()"></span>
                            </div>
                        </div>

                        <button type="button" @click="addModalItem()" class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-cyan-500">
                            {{ __('merchant_order.modal_add') }}
                        </button>

                        <button type="button" @click="closeModal()" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            {{ __('merchant_order.modal_cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function merchantOrderPage(config) {
        return {
            categories: config.categories || [],
            tables: config.tables || [],
            cartItems: (config.initialCartItems || []).map((item, index) => ({
                uid: `initial-${index}-${Date.now()}`,
                productId: Number(item.productId || item.product_id || 0),
                productName: item.productName || item.product_name || '',
                categoryName: item.categoryName || item.category_name || '',
                basePrice: Number(item.basePrice || item.base_price || 0),
                price: Number(item.price || 0),
                qty: Number(item.qty || 1),
                subtotal: Number(item.subtotal || 0),
                optionLabel: item.optionLabel || item.option_label || null,
                optionPayload: item.optionPayload || item.option_payload || '',
                itemNote: item.itemNote || item.item_note || '',
            })),
            currencySymbol: config.currencySymbol || 'NT$',
            locale: config.locale || undefined,
            ui: config.ui || {},
            tablesRefreshUrl: config.tablesRefreshUrl || null,
            couponLookupUrl: config.couponLookupUrl || null,
            customerPhone: config.defaultCustomerPhone || '',
            orderType: config.defaultOrderType === 'takeout' ? 'takeout' : 'dine_in',
            selectedTableId: Number(config.defaultTableId || 0) || null,
            activeCategoryId: (config.categories || [])[0]?.id || null,
            modalOpen: false,
            cartDetailsOpen: false,
            modalProduct: null,
            modalSelections: {},
            modalQty: 1,
            modalItemNote: '',
            availableCoupons: [],
            couponMember: null,
            couponLookupLoading: false,
            couponLookupError: config.initialCouponError || '',
            couponLookupAttempted: false,
            selectedCouponCode: config.initialCouponCode || '',
            selectedCouponSummary: config.initialCouponSummary || '',
            selectedCouponDiscount: Number(config.initialCouponDiscount || 0),
            selectedCouponName: config.initialCouponName || '',
            _tablesPollTimer: null,
            _workspaceTabHandler: null,
            _boardSyncHandler: null,
            _categoryObserver: null,
            _hashChangeHandler: null,

            get selectedTable() {
                return this.tables.find((table) => Number(table.id) === Number(this.selectedTableId)) || null;
            },

            get cartTotal() {
                return this.cartItems.reduce((sum, item) => sum + Number(item.subtotal || 0), 0);
            },

            get totalQty() {
                return this.cartItems.reduce((sum, item) => sum + Number(item.qty || 0), 0);
            },

            get estimatedPayable() {
                return Math.max(this.cartTotal - Number(this.selectedCouponDiscount || 0), 0);
            },

            init() {
                if (this.tablesRefreshUrl) {
                    this.refreshTables({ preserveSelection: true });
                    clearInterval(this._tablesPollTimer);
                    this._tablesPollTimer = setInterval(() => {
                        if (document.hidden) {
                            return;
                        }

                        this.refreshTables({ preserveSelection: true });
                    }, 15000);
                }

                this._workspaceTabHandler = (event) => {
                    if (event?.detail?.tab === 'orders') {
                        this.refreshTables({ preserveSelection: true });
                        this.$nextTick(() => {
                            this.syncActiveCategoryFromHash();
                            this.initCategoryObserver();
                        });
                    }
                };
                window.addEventListener('merchant-workspace-tab-changed', this._workspaceTabHandler);

                this._boardSyncHandler = () => {
                    this.refreshTables({ preserveSelection: true });
                };
                window.addEventListener('board-orders-updated', this._boardSyncHandler);

                this._hashChangeHandler = () => {
                    this.syncActiveCategoryFromHash();
                };
                window.addEventListener('hashchange', this._hashChangeHandler);

                this.$nextTick(() => {
                    this.syncActiveCategoryFromHash();
                    this.initCategoryObserver();
                });
            },

            async refreshTables({ preserveSelection = true } = {}) {
                if (!this.tablesRefreshUrl) {
                    return;
                }

                try {
                    const selectedTableId = this.selectedTableId;
                    const res = await fetch(this.tablesRefreshUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!res.ok) {
                        return;
                    }

                    const payload = await res.json();
                    const nextTables = Array.isArray(payload?.tables) ? payload.tables : [];
                    this.tables = nextTables;

                    if (preserveSelection && selectedTableId !== null) {
                        const selected = nextTables.find((table) => Number(table.id) === Number(selectedTableId));
                        this.selectedTableId = selected ? Number(selected.id) : null;
                    }
                } catch {}
            },

            text(key, replacements = {}) {
                let template = this.ui[key] || '';

                Object.entries(replacements).forEach(([name, value]) => {
                    template = template.replace(new RegExp(`:${name}`, 'g'), String(value));
                });

                return template;
            },

            isDineIn() {
                return this.orderType !== 'takeout';
            },

            isTakeout() {
                return this.orderType === 'takeout';
            },

            setOrderType(type) {
                const nextType = type === 'takeout' ? 'takeout' : 'dine_in';
                if (nextType === this.orderType) {
                    return;
                }

                this.orderType = nextType;
                this.invalidateCouponSelection();
            },

            selectTable(tableId) {
                const target = this.tables.find((table) => Number(table.id) === Number(tableId));
                if (!target || target.status === 'inactive') {
                    return;
                }

                this.selectedTableId = Number(tableId);
            },

            selectedTableLabel() {
                if (this.isTakeout()) {
                    return this.text('takeoutSelectedLabel');
                }

                if (!this.selectedTable) {
                    return this.text('selectedTableNone');
                }

                return `${this.text('selectedTablePrefix')}${this.selectedTable.table_no}`;
            },

            syncActiveCategoryFromHash() {
                const hash = String(window.location.hash || '');
                const matched = hash.match(/^#category-(.+)$/);
                if (!matched) {
                    return;
                }

                const categoryId = Number(matched[1]);
                if (!Number.isFinite(categoryId)) {
                    return;
                }

                if (this.categories.some((category) => Number(category.id) === categoryId)) {
                    this.activeCategoryId = categoryId;
                }
            },

            scrollToCategory(categoryId) {
                const targetId = Number(categoryId);
                if (!Number.isFinite(targetId)) {
                    return;
                }

                this.activeCategoryId = targetId;
                const target = document.getElementById(`category-${targetId}`);
                if (!target) {
                    return;
                }

                const nextUrl = new URL(window.location.href);
                nextUrl.hash = `category-${targetId}`;
                window.history.replaceState(window.history.state, '', nextUrl.toString());
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },

            initCategoryObserver() {
                if (typeof window === 'undefined' || typeof window.IntersectionObserver !== 'function') {
                    return;
                }

                if (this._categoryObserver) {
                    this._categoryObserver.disconnect();
                }

                const sections = Array.from(document.querySelectorAll('[data-order-category-section]'));
                if (sections.length === 0) {
                    return;
                }

                this._categoryObserver = new IntersectionObserver((entries) => {
                    const visibleEntry = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];

                    if (!visibleEntry) {
                        return;
                    }

                    const nextId = Number(visibleEntry.target.dataset.categoryId || 0);
                    if (Number.isFinite(nextId) && nextId > 0) {
                        this.activeCategoryId = nextId;
                    }
                }, {
                    rootMargin: '-18% 0px -58% 0px',
                    threshold: [0.15, 0.35, 0.6],
                });

                sections.forEach((section) => {
                    this._categoryObserver.observe(section);
                });
            },

            openOrderSummary(order) {
                return this.text('openOrderPrefix') + order.order_no + ' · ' + this.money(order.total) + ` / ${this.formatNumber(order.items_count)}`;
            },

            money(value) {
                return `${this.currencySymbol} ${this.formatNumber(value)}`;
            },

            formatNumber(value) {
                return Number(value || 0).toLocaleString(this.locale || undefined);
            },

            handleCustomerPhoneInput() {
                this.invalidateCouponSelection(true);
            },

            clearAppliedCoupon(clearError = true) {
                this.selectedCouponCode = '';
                this.selectedCouponSummary = '';
                this.selectedCouponDiscount = 0;
                this.selectedCouponName = '';

                if (clearError) {
                    this.couponLookupError = '';
                }
            },

            invalidateCouponSelection(clearMember = false) {
                const hasCouponState = this.selectedCouponCode !== ''
                    || this.availableCoupons.length > 0
                    || this.couponLookupAttempted;

                if (clearMember) {
                    this.couponMember = null;
                }

                if (!hasCouponState) {
                    return;
                }

                this.clearAppliedCoupon(false);
                this.availableCoupons = [];
                this.couponLookupAttempted = false;
                this.couponLookupError = this.text('couponSelectionStale');
            },

            async lookupAvailableCoupons() {
                if (this.couponLookupLoading) {
                    return;
                }

                const phone = String(this.customerPhone || '').trim();
                if (phone === '') {
                    this.couponLookupError = this.text('couponPhoneRequired');
                    this.availableCoupons = [];
                    this.clearAppliedCoupon(false);
                    return;
                }

                if (this.cartItems.length === 0 || this.cartTotal <= 0) {
                    this.couponLookupError = this.text('couponAddItemsFirst');
                    this.availableCoupons = [];
                    this.clearAppliedCoupon(false);
                    return;
                }

                if (!this.couponLookupUrl) {
                    this.couponLookupError = this.text('couponLookupFailed');
                    return;
                }

                this.couponLookupLoading = true;
                this.couponLookupError = '';
                this.couponLookupAttempted = true;

                try {
                    const endpoint = new URL(this.couponLookupUrl, window.location.origin);
                    endpoint.searchParams.set('order_type', this.orderType);
                    endpoint.searchParams.set('customer_phone', phone);
                    endpoint.searchParams.set('subtotal', String(this.cartTotal));

                    const response = await fetch(endpoint.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const errorMessage = payload?.errors?.customer_phone?.[0]
                            || payload?.errors?.subtotal?.[0]
                            || payload?.message
                            || payload?.error
                            || this.text('couponLookupFailed');

                        this.availableCoupons = [];
                        this.couponMember = null;
                        this.clearAppliedCoupon(false);
                        this.couponLookupError = errorMessage;
                        return;
                    }

                    this.couponMember = payload?.member || null;
                    this.availableCoupons = Array.isArray(payload?.coupons) ? payload.coupons : [];

                    if (this.availableCoupons.length === 0) {
                        this.clearAppliedCoupon(false);
                        this.couponLookupError = this.text('couponNoAvailable');
                        return;
                    }

                    if (this.selectedCouponCode !== '' && !this.availableCoupons.some((coupon) => coupon.code === this.selectedCouponCode)) {
                        this.clearAppliedCoupon(false);
                    }
                } catch {
                    this.availableCoupons = [];
                    this.couponMember = null;
                    this.clearAppliedCoupon(false);
                    this.couponLookupError = this.text('couponLookupFailed');
                } finally {
                    this.couponLookupLoading = false;
                }
            },

            applyCoupon(coupon) {
                this.selectedCouponCode = String(coupon?.code || '').trim();
                this.selectedCouponSummary = String(coupon?.summary || '').trim();
                this.selectedCouponDiscount = Number(coupon?.discount || 0);
                this.selectedCouponName = String(coupon?.name || '').trim();
                this.couponLookupError = '';
            },

            productDescription(product) {
                if (!product) {
                    return this.text('noDescription');
                }

                return product.description || this.text('noDescription');
            },

            productOptionGroupsLabel(product) {
                const count = Number(product?.option_group_count || 0);

                return count > 0
                    ? this.text('optionGroupsLabel', { count })
                    : this.text('noOptionsLabel');
            },

            productRequiredGroupsLabel(product) {
                const count = Number(product?.required_group_count || 0);

                return count > 0
                    ? this.text('requiredGroupsLabel', { count })
                    : this.text('noRequiredGroupsLabel');
            },

            groupTypeLabel(group) {
                if ((group?.type || 'single') === 'multiple') {
                    return this.text('modalMultipleChoice', { count: Number(group?.max_select || 99) });
                }

                return this.text('modalSingleChoice');
            },

            choicePriceLabel(choice) {
                const price = Number(choice?.price || 0);

                return price > 0
                    ? `${this.text('modalExtraPricePrefix')} ${this.money(price)}`
                    : this.text('modalFreeChoice');
            },

            openProduct(product) {
                this.modalProduct = product;
                this.modalOpen = true;
                this.lockPageScroll();
                this.modalQty = 1;
                this.modalItemNote = '';
                this.modalSelections = {};
                this.activeCategoryId = product.category_id || this.activeCategoryId;
            },

            closeModal() {
                this.modalOpen = false;
                this.unlockPageScroll();
                this.modalProduct = null;
                this.modalSelections = {};
                this.modalQty = 1;
                this.modalItemNote = '';
            },

            lockPageScroll() {
                document.body.classList.add('overflow-y-hidden');
            },

            unlockPageScroll() {
                document.body.classList.remove('overflow-y-hidden');
            },

            isChecked(groupId, choiceId) {
                const selected = this.modalSelections[groupId];
                return Array.isArray(selected) ? selected.includes(choiceId) : false;
            },

            isSelected(groupId, choiceId) {
                return this.modalSelections[groupId] === choiceId;
            },

            selectSingleChoice(groupId, choiceId) {
                this.modalSelections[groupId] = choiceId;
            },

            toggleMultipleChoice(group, choiceId, checked) {
                const current = Array.isArray(this.modalSelections[group.id]) ? [...this.modalSelections[group.id]] : [];

                if (checked) {
                    if (!current.includes(choiceId)) {
                        current.push(choiceId);
                    }

                    const maxSelect = Number(group.max_select || 99);
                    if (current.length > maxSelect) {
                        current.splice(0, current.length - maxSelect);
                    }
                } else {
                    this.modalSelections[group.id] = current.filter((value) => value !== choiceId);
                    return;
                }

                this.modalSelections[group.id] = current;
            },

            selectedExtraPrice() {
                if (!this.modalProduct) {
                    return 0;
                }

                return (this.modalProduct.option_groups || []).reduce((sum, group) => {
                    const choices = Array.isArray(group.choices) ? group.choices : [];
                    const selected = this.modalSelections[group.id];

                    if (Array.isArray(selected)) {
                        return sum + selected.reduce((groupSum, choiceId) => {
                            const choice = choices.find((item) => item.id === choiceId);
                            return groupSum + Number(choice?.price || 0);
                        }, 0);
                    }

                    const choice = choices.find((item) => item.id === selected);
                    return sum + Number(choice?.price || 0);
                }, 0);
            },

            modalSubtotal() {
                if (!this.modalProduct) {
                    return 0;
                }

                return (Number(this.modalProduct.price || 0) + this.selectedExtraPrice()) * Number(this.modalQty || 1);
            },

            modalUnitPriceLabel() {
                if (!this.modalProduct) {
                    return this.money(0);
                }

                return this.money(Number(this.modalProduct.price || 0) + this.selectedExtraPrice());
            },

            sanitizeSelectionPayload(payload) {
                const result = {};

                Object.entries(payload || {}).forEach(([groupId, value]) => {
                    if (Array.isArray(value)) {
                        const filtered = value.filter(Boolean);
                        if (filtered.length > 0) {
                            result[groupId] = filtered;
                        }
                        return;
                    }

                    if (value) {
                        result[groupId] = value;
                    }
                });

                return result;
            },

            buildOptionLabel() {
                if (!this.modalProduct) {
                    return null;
                }

                const parts = [];

                (this.modalProduct.option_groups || []).forEach((group) => {
                    const choices = Array.isArray(group.choices) ? group.choices : [];
                    const selected = this.modalSelections[group.id];
                    let selectedChoices = [];

                    if (Array.isArray(selected)) {
                        selectedChoices = choices.filter((choice) => selected.includes(choice.id));
                    } else if (selected) {
                        selectedChoices = choices.filter((choice) => choice.id === selected);
                    }

                    if (selectedChoices.length === 0) {
                        return;
                    }

                    const choiceLabel = selectedChoices.map((choice) => {
                        const price = Number(choice.price || 0);
                        return price > 0 ? `${choice.name} (+${price})` : choice.name;
                    }).join(', ');

                    parts.push(`${group.name}: ${choiceLabel}`);
                });

                return parts.length > 0 ? parts.join(' / ') : null;
            },

            validateModalSelections() {
                if (!this.modalProduct) {
                    return false;
                }

                for (const group of (this.modalProduct.option_groups || [])) {
                    if (!group.required) {
                        continue;
                    }

                    const selected = this.modalSelections[group.id];
                    const isEmpty = Array.isArray(selected) ? selected.length === 0 : !selected;

                    if (isEmpty) {
                        window.alert(this.text('validationRequiredGroupAlert', {
                            group: group.name || this.text('modalRequiredTag'),
                        }));
                        return false;
                    }
                }

                return true;
            },

            addModalItem() {
                if (!this.modalProduct || !this.validateModalSelections()) {
                    return;
                }

                const payload = this.sanitizeSelectionPayload(this.modalSelections);
                const optionPayload = Object.keys(payload).length > 0 ? JSON.stringify(payload) : '';
                const optionLabel = this.buildOptionLabel();
                const unitPrice = Number(this.modalProduct.price || 0) + this.selectedExtraPrice();
                const qty = Math.max(Number(this.modalQty || 1), 1);
                const itemNote = (this.modalItemNote || '').trim();

                this.cartItems.push({
                    uid: `item-${Date.now()}-${Math.round(Math.random() * 100000)}`,
                    productId: Number(this.modalProduct.id),
                    productName: this.modalProduct.name,
                    categoryName: this.modalProduct.category_name || '',
                    basePrice: Number(this.modalProduct.price || 0),
                    price: unitPrice,
                    qty,
                    subtotal: unitPrice * qty,
                    optionLabel,
                    optionPayload,
                    itemNote,
                });

                this.invalidateCouponSelection();
                this.closeModal();
            },

            increaseQty(index) {
                const item = this.cartItems[index];
                if (!item) {
                    return;
                }

                item.qty += 1;
                item.subtotal = item.price * item.qty;
                this.invalidateCouponSelection();
            },

            decreaseQty(index) {
                const item = this.cartItems[index];
                if (!item) {
                    return;
                }

                item.qty = Math.max(1, item.qty - 1);
                item.subtotal = item.price * item.qty;
                this.invalidateCouponSelection();
            },

            removeItem(index) {
                this.cartItems.splice(index, 1);
                this.invalidateCouponSelection();
            },

            clearCart() {
                this.cartItems = [];
                this.invalidateCouponSelection();
            },
        };
    }
</script>
@endsection
