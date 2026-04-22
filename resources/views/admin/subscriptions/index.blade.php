@extends('layouts.app')

@section('content')
@php
    $formatPlanCategory = function (?string $category) {
        $value = trim((string) $category);

        if ($value === '') {
            return '-';
        }

        return match (strtolower($value)) {
            'basic' => __('merchant.plan_tier_basic'),
            'growth' => __('merchant.plan_tier_growth'),
            'pro' => __('merchant.plan_tier_pro'),
            default => $value,
        };
    };
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

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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

        @if($activeTab === 'manage')
            <div class="space-y-6">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">訂閱方案設定</h2>
                        <p class="mt-1 text-sm text-slate-500">管理每個方案的類別、標題、價格、折價與敘述。</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">類別</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">標題</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">價格</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">折價</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">敘述</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.status') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.update') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($plans as $plan)
                                    <tr>
                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="text"
                                                name="category"
                                                form="plan-update-{{ $plan->id }}"
                                                value="{{ old('category', $plan->category ?: (string) strtok($plan->slug, '-')) }}"
                                                class="w-full min-w-32 rounded-lg border border-slate-300 px-3 py-2"
                                            >
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="text"
                                                name="name"
                                                form="plan-update-{{ $plan->id }}"
                                                value="{{ old('name', $plan->name) }}"
                                                class="w-full min-w-40 rounded-lg border border-slate-300 px-3 py-2"
                                            >
                                            <div class="mt-2 text-xs text-slate-500">
                                                {{ $plan->slug }} / {{ __('admin.subscription_plan_option', [
                                                    'name' => $plan->name,
                                                    'days' => $plan->duration_days,
                                                    'stores' => $plan->max_stores === null
                                                        ? __('admin.subscription_unlimited_stores')
                                                        : __('admin.subscription_max_stores', ['max' => $plan->max_stores]),
                                                ]) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="number"
                                                min="1"
                                                step="1"
                                                name="price_twd"
                                                form="plan-update-{{ $plan->id }}"
                                                value="{{ old('price_twd', $plan->price_twd) }}"
                                                class="w-full min-w-28 rounded-lg border border-slate-300 px-3 py-2"
                                            >
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="number"
                                                min="0"
                                                step="1"
                                                name="discount_twd"
                                                form="plan-update-{{ $plan->id }}"
                                                value="{{ old('discount_twd', $plan->discount_twd ?? 0) }}"
                                                class="w-full min-w-28 rounded-lg border border-slate-300 px-3 py-2"
                                            >
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <textarea
                                                name="description"
                                                form="plan-update-{{ $plan->id }}"
                                                rows="3"
                                                class="w-full min-w-56 rounded-lg border border-slate-300 px-3 py-2"
                                            >{{ old('description', $plan->description) }}</textarea>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $plan->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                                {{ $plan->is_active ? __('admin.active') : __('admin.inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <form id="plan-update-{{ $plan->id }}" method="POST" action="{{ route('super-admin.subscriptions.plans.update', $plan) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                                    {{ __('admin.update') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">目前沒有訂閱方案。</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">商家訂閱指派</h2>
                        <p class="mt-1 text-sm text-slate-500">將啟用中的方案指派給商家，或手動設為已到期。</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm" data-datatable data-dt-paging="false" data-dt-info="false">
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
                                                <div class="font-medium text-slate-900">{{ $merchant->subscriptionPlan->name }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $formatPlanCategory($merchant->subscriptionPlan->category) }}</div>
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
                                                    @foreach($assignablePlans as $plan)
                                                        <option value="{{ $plan->id }}" @selected($merchant->subscription_plan_id === $plan->id)>
                                                            {{ __('admin.subscription_plan_option', [
                                                                'name' => trim($formatPlanCategory($plan->category) . ' ' . $plan->name),
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
                </section>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm" data-datatable data-dt-paging="false" data-dt-info="false">
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
            </div>
        @endif
    </div>
</div>
@endsection
