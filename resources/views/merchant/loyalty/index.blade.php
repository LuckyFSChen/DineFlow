@extends('layouts.app')

@section('content')
@php
    $currencyCode = strtolower((string) ($selectedStore->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
@endphp
<div class="min-h-screen bg-slate-50 py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">{{ __('loyalty.title') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('loyalty.desc') }}</p>

            @if(session('status'))
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <div class="font-semibold">{{ __('loyalty.error_title') }}</div>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" class="grid gap-3 md:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('merchant.store') }}</label>
                    <select name="store_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected((int) $selectedStore->id === (int) $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('merchant.range') }}</label>
                    <input
                        type="text"
                        data-flatpickr-range
                        data-range-start-name="start_date"
                        data-range-end-name="end_date"
                        value="{{ $startDate && $endDate ? $startDate . ' ~ ' . $endDate : '' }}"
                        placeholder="{{ __('merchant.start_date') }} ~ {{ __('merchant.end_date') }}"
                        autocomplete="off"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium"
                    >
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.member_search') }}</label>
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="{{ __('loyalty.member_search_placeholder') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('merchant.apply_filter') }}</button>
                </div>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('loyalty.total_members') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalMembers) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('loyalty.new_members') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($newMembers) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('loyalty.repeat_members') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($repeatMembers) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('loyalty.avg_spent') }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $currencySymbol }} {{ number_format($avgSpentPerMember) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('loyalty.points_issued') }}</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($pointsIssued) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('loyalty.points_redeemed') }}</p>
                <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format($pointsRedeemed) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('loyalty.coupon_orders', ['count' => number_format($couponOrders)]) }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('loyalty.settings_title') }}</h2>
                <form method="POST" action="{{ route('merchant.loyalty.settings.update') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="keyword" value="{{ $keyword }}">

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="loyalty_enabled" value="1" @checked($selectedStore->loyalty_enabled) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('loyalty.enable_points') }}
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.points_per_amount') }}</label>
                            <input type="number" min="1" max="100000" name="points_per_amount" value="{{ old('points_per_amount', $selectedStore->points_per_amount ?? 100) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.points_reward') }}</label>
                            <input type="number" min="1" max="1000" name="points_reward" value="{{ old('points_reward', $selectedStore->points_reward ?? 1) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('loyalty.save_settings') }}</button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('loyalty.create_coupon_title') }}</h2>
                <form method="POST"
                      action="{{ route('merchant.loyalty.coupons.store') }}"
                      class="mt-4 grid gap-3 sm:grid-cols-2"
                      x-data="couponCreateForm(@js(old('discount_type', 'fixed')))"
                      x-init="onDiscountTypeChange()">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="{{ __('loyalty.name_placeholder') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.code') }}</label>
                        <input type="text" name="code" value="{{ old('code') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase" placeholder="{{ __('loyalty.code_placeholder') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.discount_type') }}</label>
                        <select name="discount_type"
                                x-model="discountType"
                                @change="onDiscountTypeChange()"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="fixed">{{ __('loyalty.fixed') }}</option>
                            <option value="percent">{{ __('loyalty.percent') }}</option>
                            <option value="points_reward">{{ __('loyalty.points_reward_type') }}</option>
                        </select>
                    </div>
                    <div x-show="discountType !== 'points_reward'" x-cloak>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.discount_value') }}</label>
                        <div class="relative">
                            <input type="number"
                                   name="discount_value"
                                   x-ref="discountValue"
                                   :min="discountType === 'percent' ? 1 : 0"
                                   :max="discountType === 'percent' ? 100 : null"
                                   value="{{ old('discount_value') }}"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 pr-8 text-sm">
                            <span x-show="discountType === 'percent'" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.reward_per_amount') }}</label>
                        <input type="number" min="0" name="reward_per_amount" value="{{ old('reward_per_amount') }}" x-ref="rewardPerAmount" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.reward_points') }}</label>
                        <input type="number" min="0" name="reward_points" value="{{ old('reward_points') }}" x-ref="rewardPoints" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.min_order_amount') }}</label>
                        <input type="number" min="0" name="min_order_amount" value="{{ old('min_order_amount', 0) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.points_cost') }}</label>
                        <input type="number" min="0" name="points_cost" value="{{ old('points_cost', 0) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.order_type_availability') }}</label>
                                <div class="flex flex-wrap gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <input type="hidden" name="allow_dine_in" value="0">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="allow_dine_in" value="1" @checked(old('allow_dine_in', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ __('loyalty.allow_dine_in') }}
                                    </label>
                                    <input type="hidden" name="allow_takeout" value="0">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="allow_takeout" value="1" @checked(old('allow_takeout', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ __('loyalty.allow_takeout') }}
                                    </label>
                                </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.usage_limit') }}</label>
                        <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit') }}" placeholder="{{ __('loyalty.usage_limit_placeholder') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-slate-500">{{ __('loyalty.usage_limit_hint') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.active_period') }}</label>
                        <input
                            type="text"
                            data-flatpickr-datetime-range
                            data-range-start-name="starts_at"
                            data-range-end-name="ends_at"
                            value="{{ old('starts_at') && old('ends_at') ? old('starts_at') . ' ~ ' . old('ends_at') : '' }}"
                            placeholder="{{ __('loyalty.datetime_placeholder') }}"
                            autocomplete="off"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        >
                        <input type="hidden" name="starts_at" value="{{ old('starts_at') }}">
                        <input type="hidden" name="ends_at" value="{{ old('ends_at') }}">
                    </div>
                    <label class="sm:col-span-2 inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('loyalty.activate_after_create') }}
                    </label>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('loyalty.create_coupon') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('loyalty.top_members') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm" data-datatable data-dt-paging="false" data-dt-info="false">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.name') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('loyalty.total_spent') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('loyalty.order_count') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('loyalty.current_points') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topMembers as $member)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $member->displayName() }}</td>
                                <td class="px-3 py-2 text-right">{{ $currencySymbol }} {{ number_format((int) $member->total_spent) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->total_orders) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->points_balance) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-500">{{ __('loyalty.no_members') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('loyalty.member_details') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm" data-datatable data-dt-paging="false" data-dt-info="false" data-dt-searching="false">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.name') }}</th>
                            <th class="px-3 py-2 text-left">Email</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.phone') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.favorite_items') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.recent_orders') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('loyalty.current_points') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('loyalty.total_spent') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('loyalty.order_count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $member->name ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $member->email ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $member->phone ?: '-' }}</td>
                                <td class="px-3 py-2 align-top">
                                    @php
                                        $favoriteItems = $favoriteItemsByMember->get((int) $member->id, collect());
                                    @endphp
                                    @if($favoriteItems->isNotEmpty())
                                        <div class="space-y-1 text-xs text-slate-700">
                                            @foreach($favoriteItems as $item)
                                                <div>{{ $item->product_name }} <span class="text-slate-500">x{{ number_format((int) $item->total_qty) }}</span></div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">{{ __('loyalty.no_favorite_items') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-top">
                                    @php
                                        $recentOrders = $recentOrdersByMember->get((int) $member->id, collect());
                                    @endphp
                                    @if($recentOrders->isNotEmpty())
                                        <div class="space-y-2 text-xs text-slate-700">
                                            @foreach($recentOrders as $order)
                                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1">
                                                    <div class="font-semibold text-slate-800">#{{ $order->order_no }}</div>
                                                    <div class="text-slate-500">{{ optional($order->created_at)->format('Y-m-d H:i') }}｜{{ \App\Models\Order::customerStatusLabel($order->status, $order->payment_status) }}</div>
                                                    <div class="text-slate-600">{{ $currencySymbol }} {{ number_format((int) $order->total) }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">{{ __('loyalty.no_recent_orders') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->points_balance) }}</td>
                                <td class="px-3 py-2 text-right">{{ $currencySymbol }} {{ number_format((int) $member->total_spent) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->total_orders) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-slate-500">{{ __('loyalty.no_matching_members') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $members->links() }}
            </div>
        </div>

           <div class="admin-modal-host rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
               x-data="couponManager()"
               data-update-url-template="{{ route('merchant.loyalty.coupons.update', ['coupon' => '__COUPON__']) }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('loyalty.coupon_list') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm" data-datatable="off">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.name') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.code') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.discount_content') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.threshold_exchange') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.available_for_column') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.usage_count') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.start_time') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('loyalty.end_time') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('merchant.status') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('loyalty.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $coupon->name }}</td>
                                <td class="px-3 py-2 font-semibold">{{ $coupon->code }}</td>
                                <td class="px-3 py-2">
                                    @if($coupon->isPercentType())
                                        {{ $coupon->discount_value }}%
                                    @elseif($coupon->isPointsRewardType())
                                        {{ __('loyalty.points_reward_type') }}
                                    @else
                                        {{ $currencySymbol }} {{ number_format((int) $coupon->discount_value) }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ __('loyalty.min_spend', ['currency' => $currencySymbol, 'amount' => number_format((int) $coupon->min_order_amount)]) }}<br>
                                    {{ __('loyalty.redeem_points', ['points' => number_format((int) $coupon->points_cost)]) }}
                                    @if($coupon->isPointsRewardType())
                                        <br>{{ __('loyalty.reward_rule', ['currency' => $currencySymbol, 'amount' => number_format((int) $coupon->reward_per_amount), 'points' => number_format((int) $coupon->reward_points)]) }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($coupon->allowsDineIn() && $coupon->allowsTakeout())
                                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                            {{ __('loyalty.available_for_both') }}
                                        </span>
                                    @elseif($coupon->allowsDineIn())
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            {{ __('loyalty.available_for_dine_in_only') }}
                                        </span>
                                    @elseif($coupon->allowsTakeout())
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            {{ __('loyalty.available_for_takeout_only') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $coupon->usage_limit !== null
                                        ? __('loyalty.usage_limited', ['used' => number_format((int) $coupon->used_count), 'limit' => number_format((int) $coupon->usage_limit)])
                                        : __('loyalty.usage_unlimited', ['used' => number_format((int) $coupon->used_count)]) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ optional($coupon->starts_at)->format('Y-m-d H:i') ?: '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ optional($coupon->ends_at)->format('Y-m-d H:i') ?: '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $coupon->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $coupon->is_active ? __('loyalty.enabled') : __('loyalty.disabled') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="inline-flex gap-2">
                                        <button type="button"
                                                data-coupon-id="{{ (int) $coupon->id }}"
                                                data-coupon-name="{{ $coupon->name }}"
                                                data-coupon-code="{{ $coupon->code }}"
                                                data-coupon-discount-type="{{ $coupon->normalizedDiscountType() }}"
                                                data-coupon-discount-value="{{ (int) $coupon->discount_value }}"
                                                data-coupon-reward-per-amount="{{ (int) $coupon->reward_per_amount }}"
                                                data-coupon-reward-points="{{ (int) $coupon->reward_points }}"
                                                data-coupon-min-order-amount="{{ (int) $coupon->min_order_amount }}"
                                                data-coupon-points-cost="{{ (int) $coupon->points_cost }}"
                                                data-coupon-usage-limit="{{ $coupon->usage_limit ?? '' }}"
                                                data-coupon-starts-at="{{ optional($coupon->starts_at)->format('Y-m-d\TH:i') }}"
                                                data-coupon-ends-at="{{ optional($coupon->ends_at)->format('Y-m-d\TH:i') }}"
                                                data-coupon-allow-dine-in="{{ $coupon->allowsDineIn() ? '1' : '0' }}"
                                                data-coupon-allow-takeout="{{ $coupon->allowsTakeout() ? '1' : '0' }}"
                                                data-coupon-is-active="{{ $coupon->is_active ? '1' : '0' }}"
                                                @click="openEditModalFromButton($el)"
                                                class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                            {{ __('loyalty.edit') }}
                                        </button>
                                        <form method="POST" action="{{ route('merchant.loyalty.coupons.toggle', $coupon) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                                {{ $coupon->is_active ? __('loyalty.disabled') : __('loyalty.enabled') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('merchant.loyalty.coupons.destroy', $coupon) }}" onsubmit="return confirm('{{ __('loyalty.delete_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                {{ __('loyalty.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-3 py-6 text-center text-slate-500">{{ __('loyalty.no_coupons') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $coupons->links() }}
            </div>

            <template x-teleport="body">
                <div x-cloak
                     x-show="editModalOpen"
                     @keydown.escape.window="closeEditModal()"
                     class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-900/50 px-4 py-6"
                     style="display: none;">
                    <div @click.outside="if (!$event.target.closest('.flatpickr-calendar')) closeEditModal()" class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-2xl">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('loyalty.edit_coupon_title') }}</h3>
                            <button type="button" @click="closeEditModal()" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">{{ __('loyalty.close') }}</button>
                        </div>

                        <form method="POST" :action="updateAction" class="grid gap-3 sm:grid-cols-2">
                            @csrf
                            @method('PUT')

                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.name') }}</label>
                                <input type="text" name="name" required x-model="form.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.code') }}</label>
                                <input type="text" name="code" required x-model="form.code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.discount_type') }}</label>
                                <select name="discount_type" x-model="form.discount_type" @change="normalizeFieldsByDiscountType()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                    <option value="fixed">{{ __('loyalty.fixed') }}</option>
                                    <option value="percent">{{ __('loyalty.percent') }}</option>
                                    <option value="points_reward">{{ __('loyalty.points_reward_type') }}</option>
                                </select>
                            </div>
                            <div x-show="form.discount_type !== 'points_reward'" x-cloak>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.discount_value') }}</label>
                                <div class="relative">
                                    <input type="number"
                                           name="discount_value"
                                           x-model="form.discount_value"
                                           x-ref="editDiscountValue"
                                           :min="form.discount_type === 'percent' ? 1 : 0"
                                           :max="form.discount_type === 'percent' ? 100 : null"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2 pr-8 text-sm">
                                    <span x-show="form.discount_type === 'percent'" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.reward_per_amount') }}</label>
                                <input type="number" min="0" name="reward_per_amount" x-model="form.reward_per_amount" x-ref="editRewardPerAmount" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.reward_points') }}</label>
                                <input type="number" min="0" name="reward_points" x-model="form.reward_points" x-ref="editRewardPoints" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.min_order_amount') }}</label>
                                <input type="number" min="0" name="min_order_amount" x-model="form.min_order_amount" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.points_cost') }}</label>
                                <input type="number" min="0" name="points_cost" x-model="form.points_cost" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.order_type_availability') }}</label>
                                <div class="flex flex-wrap gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <input type="hidden" name="allow_dine_in" value="0">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="allow_dine_in" value="1" x-model="form.allow_dine_in" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ __('loyalty.allow_dine_in') }}
                                    </label>
                                    <input type="hidden" name="allow_takeout" value="0">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="allow_takeout" value="1" x-model="form.allow_takeout" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ __('loyalty.allow_takeout') }}
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.usage_limit') }}</label>
                                <input type="number" min="1" name="usage_limit" x-model="form.usage_limit" placeholder="{{ __('loyalty.usage_limit_placeholder') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <p class="mt-1 text-xs text-slate-500">{{ __('loyalty.usage_limit_hint') }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('loyalty.active_period') }}</label>
                                <input
                                    type="text"
                                    data-flatpickr-datetime-range
                                    data-range-start-name="starts_at"
                                    data-range-end-name="ends_at"
                                    x-ref="editActiveAtRange"
                                    autocomplete="off"
                                    placeholder="{{ __('loyalty.datetime_placeholder') }}"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                                >
                                <input type="hidden" name="starts_at" x-model="form.starts_at">
                                <input type="hidden" name="ends_at" x-model="form.ends_at">
                            </div>
                            <label class="sm:col-span-2 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                {{ __('loyalty.coupon_active') }}
                            </label>
                            <div class="sm:col-span-2 flex items-center justify-end gap-2">
                                <button type="button" @click="closeEditModal()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('loyalty.cancel') }}</button>
                                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('loyalty.save_changes') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
<script>
    function couponCreateForm(initialType = 'fixed') {
        return {
            discountType: initialType || 'fixed',
            onDiscountTypeChange() {
                const isPointsReward = this.discountType === 'points_reward';
                this.toggleFieldGroup(this.$refs.discountValue, !isPointsReward);
                this.toggleFieldGroup(this.$refs.rewardPerAmount, isPointsReward);
                this.toggleFieldGroup(this.$refs.rewardPoints, isPointsReward);

                if (isPointsReward && this.$refs.discountValue) {
                    this.$refs.discountValue.value = '0';
                }
                if (!isPointsReward) {
                    if (this.$refs.rewardPerAmount) {
                        this.$refs.rewardPerAmount.value = '0';
                    }
                    if (this.$refs.rewardPoints) {
                        this.$refs.rewardPoints.value = '0';
                    }
                }
            },
            toggleFieldGroup(field, shouldShow) {
                const group = field?.closest('div');
                if (!group) {
                    return;
                }

                group.style.display = shouldShow ? '' : 'none';
            },
        };
    }

    function couponManager() {
        return {
            editModalOpen: false,
            updateAction: '',
            form: {
                id: null,
                name: '',
                code: '',
                discount_type: 'fixed',
                discount_value: 1,
                reward_per_amount: 0,
                reward_points: 0,
                min_order_amount: 0,
                points_cost: 0,
                allow_dine_in: true,
                allow_takeout: true,
                usage_limit: '',
                starts_at: '',
                ends_at: '',
                is_active: true,
            },
            openEditModalFromButton(button) {
                if (!(button instanceof HTMLElement)) {
                    return;
                }

                this.openEditModal({
                    id: Number(button.dataset.couponId || 0),
                    name: button.dataset.couponName || '',
                    code: button.dataset.couponCode || '',
                    discount_type: button.dataset.couponDiscountType || 'fixed',
                    discount_value: Number(button.dataset.couponDiscountValue || 0),
                    reward_per_amount: Number(button.dataset.couponRewardPerAmount || 0),
                    reward_points: Number(button.dataset.couponRewardPoints || 0),
                    min_order_amount: Number(button.dataset.couponMinOrderAmount || 0),
                    points_cost: Number(button.dataset.couponPointsCost || 0),
                    allow_dine_in: button.dataset.couponAllowDineIn === '1',
                    allow_takeout: button.dataset.couponAllowTakeout === '1',
                    usage_limit: button.dataset.couponUsageLimit === '' ? null : Number(button.dataset.couponUsageLimit || 0),
                    starts_at: button.dataset.couponStartsAt || '',
                    ends_at: button.dataset.couponEndsAt || '',
                    is_active: button.dataset.couponIsActive === '1',
                });
            },
            openEditModal(coupon) {
                const template = this.$root.dataset.updateUrlTemplate || '';
                this.updateAction = template.replace('__COUPON__', String(coupon.id));
                this.form = {
                    id: coupon.id,
                    name: coupon.name || '',
                    code: coupon.code || '',
                    discount_type: coupon.discount_type || 'fixed',
                    discount_value: Number(coupon.discount_value || 1),
                    reward_per_amount: Number(coupon.reward_per_amount || 0),
                    reward_points: Number(coupon.reward_points || 0),
                    min_order_amount: Number(coupon.min_order_amount || 0),
                    points_cost: Number(coupon.points_cost || 0),
                    allow_dine_in: coupon.allow_dine_in !== false,
                    allow_takeout: coupon.allow_takeout !== false,
                    usage_limit: coupon.usage_limit === null ? '' : Number(coupon.usage_limit || 0),
                    starts_at: coupon.starts_at || '',
                    ends_at: coupon.ends_at || '',
                    is_active: Boolean(coupon.is_active),
                };
                this.normalizeFieldsByDiscountType();
                this.editModalOpen = true;
                this.$nextTick(() => {
                    this.syncEditActiveAtRange();
                });
            },
            syncEditActiveAtRange() {
                const rangeInput = this.$refs.editActiveAtRange;
                if (!(rangeInput instanceof HTMLInputElement)) {
                    return;
                }

                const picker = rangeInput._flatpickr;
                if (!picker) {
                    return;
                }

                const normalize = (value) => String(value || '').replace('T', ' ');
                const selected = [];
                if (this.form.starts_at) {
                    selected.push(normalize(this.form.starts_at));
                }
                if (this.form.ends_at && this.form.ends_at !== this.form.starts_at) {
                    selected.push(normalize(this.form.ends_at));
                }

                if (selected.length === 0) {
                    picker.clear();
                    return;
                }

                picker.setDate(selected, true);
            },
            normalizeFieldsByDiscountType() {
                const isPointsReward = this.form.discount_type === 'points_reward';
                this.toggleFieldGroup(this.$refs.editDiscountValue, !isPointsReward);
                this.toggleFieldGroup(this.$refs.editRewardPerAmount, isPointsReward);
                this.toggleFieldGroup(this.$refs.editRewardPoints, isPointsReward);

                if (isPointsReward) {
                    this.form.discount_value = 0;
                    return;
                }

                this.form.reward_per_amount = 0;
                this.form.reward_points = 0;
            },
            toggleFieldGroup(field, shouldShow) {
                const group = field?.closest('div');
                if (!group) {
                    return;
                }

                group.style.display = shouldShow ? '' : 'none';
            },
            closeEditModal() {
                this.editModalOpen = false;
            },
        };
    }
</script>
@endsection



