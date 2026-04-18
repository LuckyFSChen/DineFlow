@extends('layouts.app')

@section('content')
@php
    $planTierLabels = [
        'basic' => __('merchant.plan_tier_basic'),
        'growth' => __('merchant.plan_tier_growth'),
        'pro' => __('merchant.plan_tier_pro'),
    ];
    $planCycleLabels = [
        'monthly' => __('merchant.plan_cycle_monthly'),
        'quarterly' => __('merchant.plan_cycle_quarterly'),
        'yearly' => __('merchant.plan_cycle_yearly'),
    ];
@endphp
<div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-backend-header
            :title="__('admin.subscription_management_title')"
            :subtitle="__('admin.subscription_management_desc')"
        />

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <a
                href="{{ route('super-admin.subscriptions.index', ['tab' => 'manage']) }}"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $activeTab === 'manage' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900' }}"
            >
                {{ __('admin.subscription_tab_manage') }}
            </a>
            <a
                href="{{ route('super-admin.subscriptions.index', ['tab' => 'logs']) }}"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $activeTab === 'logs' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900' }}"
            >
                {{ __('admin.subscription_tab_logs') }}
            </a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if($activeTab === 'manage')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.merchant_account') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.current_plan') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.expiry_date') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.status') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.manage') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($merchants as $merchant)
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $merchant->name }}</div>
                                        <div class="text-slate-500">{{ $merchant->email }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">
                                        @if($merchant->subscriptionPlan)
                                            @php([$tier, $cycle] = array_pad(explode('-', $merchant->subscriptionPlan->slug, 2), 2, ''))
                                            {{ trim(($planTierLabels[$tier] ?? ucfirst($tier)) . ' ' . ($planCycleLabels[$cycle] ?? '')) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ $merchant->subscription_ends_at ? $merchant->subscription_ends_at->format('Y-m-d') : '-' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $merchant->hasActiveSubscription() ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $merchant->hasActiveSubscription() ? __('admin.subscription_active') : __('admin.subscription_inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <form method="POST" action="{{ route('super-admin.subscriptions.update', $merchant) }}" class="flex flex-wrap items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="plan_id" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                                @foreach($plans as $plan)
                                                    @php([$planTier, $planCycle] = array_pad(explode('-', $plan->slug, 2), 2, ''))
                                                    <option value="{{ $plan->id }}" @selected($merchant->subscription_plan_id === $plan->id)>
                                                        {{ __('admin.subscription_plan_option', [
                                                            'name' => trim(($planTierLabels[$planTier] ?? $plan->name) . ' ' . ($planCycleLabels[$planCycle] ?? '')),
                                                            'days' => $plan->duration_days,
                                                            'stores' => $plan->max_stores === null
                                                                ? __('admin.subscription_unlimited_stores')
                                                                : __('admin.subscription_max_stores', ['max' => $plan->max_stores]),
                                                        ]) }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <select name="action" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                                <option value="activate">{{ __('admin.subscription_action_activate') }}</option>
                                                <option value="expire">{{ __('admin.subscription_action_expire') }}</option>
                                            </select>

                                            <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800">{{ __('admin.update') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('admin.no_merchant_accounts') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $merchants->appends(['tab' => 'manage'])->links() }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.subscription_log_changed_at') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.merchant_account') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.subscription_log_stores') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.subscription_log_admin') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.subscription_log_before') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.subscription_log_after') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        {{ $log->created_at?->format('Y-m-d H:i') ?? '-' }}
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ __('admin.subscription_log_action_' . $log->action) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $log->merchantUser?->name ?? '-' }}</div>
                                        <div class="text-slate-500">{{ $log->merchantUser?->email ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-700">{{ $log->store_names_snapshot ?: '-' }}</td>
                                    <td class="px-4 py-4 align-top text-slate-700">
                                        <div>{{ $log->adminUser?->name ?? '-' }}</div>
                                        <div class="text-slate-500">{{ $log->adminUser?->email ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-700">
                                        <div>{{ $log->old_plan_name ?? ($log->oldPlan?->name ?? '-') }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ __('admin.status') }}: {{ $log->old_status === 'active' ? __('admin.subscription_active') : __('admin.subscription_inactive') }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ __('admin.expiry_date') }}: {{ $log->old_subscription_ends_at?->format('Y-m-d H:i') ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-700">
                                        <div>{{ $log->new_plan_name ?? ($log->newPlan?->name ?? '-') }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ __('admin.status') }}: {{ $log->new_status === 'active' ? __('admin.subscription_active') : __('admin.subscription_inactive') }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ __('admin.expiry_date') }}: {{ $log->new_subscription_ends_at?->format('Y-m-d H:i') ?? '-' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('admin.subscription_log_empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $logs->appends(['tab' => 'logs'])->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
