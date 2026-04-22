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

    $editingPlanId = (int) old('editing_plan_id', 0);
    $editingMerchantId = (int) old('editing_merchant_id', 0);
    $creatingPlan = old('plan_form_mode') === 'create';
    $supportsCategory = (bool) ($planFieldSupport['category'] ?? false);
    $supportsDiscount = (bool) ($planFieldSupport['discount'] ?? false);
    $supportsDescription = (bool) ($planFieldSupport['description'] ?? false);
    $latestLogAt = $logSummary['latest_changed_at'] ?? null;

    $planStatusClasses = fn (bool $isActive) => $isActive
        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200'
        : 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200';

    $merchantStatusClasses = fn (bool $isActive) => $isActive
        ? 'bg-cyan-50 text-cyan-700 ring-1 ring-inset ring-cyan-200'
        : 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200';

    $actionBadgeClasses = fn (?string $action) => $action === 'expire'
        ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200'
        : 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200';

    $formatMoney = fn (?int $amount) => 'NT$ ' . number_format((int) $amount);

    $createPlanValues = [
        'category' => $creatingPlan ? old('category', 'basic') : 'basic',
        'name' => $creatingPlan ? old('name', '') : '',
        'price_twd' => $creatingPlan ? old('price_twd', 999) : 999,
        'discount_twd' => $creatingPlan ? old('discount_twd', 0) : 0,
        'duration_days' => $creatingPlan ? old('duration_days', 30) : 30,
        'max_stores' => $creatingPlan ? old('max_stores', '') : '',
        'is_active' => (string) ($creatingPlan ? old('is_active', '1') : '1'),
        'description' => $creatingPlan ? old('description', '') : '',
    ];
@endphp
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <section class="admin-hero mb-8 rounded-3xl px-5 py-6 md:px-7">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">
                        {{ __('admin.subscription_tab_manage') }}
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-white">{{ __('admin.subscription_management_title') }}</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-200 md:text-base">{{ __('admin.subscription_management_desc') }}</p>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-100">
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1">
                            {{ __('admin.subscription_summary_total_logs') }}: {{ $logSummary['total_count'] ?? $logs->total() }}
                        </span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1">
                            {{ __('admin.subscription_summary_latest_change') }}: {{ $latestLogAt?->format('Y-m-d H:i') ?? '-' }}
                        </span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1">
                            {{ __('admin.subscription_summary_assignable_plans') }}: {{ $assignablePlans->count() }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:min-w-[30rem]">
                    <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.subscription_summary_total_plans') }}</p>
                        <p class="value mt-2 text-slate-900">{{ $planSummary['total_count'] ?? $plans->count() }}</p>
                    </div>
                    <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.subscription_summary_active_plans') }}</p>
                        <p class="value mt-2 text-emerald-700">{{ $planSummary['active_count'] ?? 0 }}</p>
                    </div>
                    <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.subscription_summary_active_merchants') }}</p>
                        <p class="value mt-2 text-cyan-700">{{ $merchantSummary['active_count'] ?? 0 }}</p>
                    </div>
                    <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.subscription_summary_expiring_soon') }}</p>
                        <p class="value mt-2 text-amber-700">{{ $merchantSummary['expiring_soon_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </section>

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
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="admin-pill-nav inline-flex w-full flex-wrap items-center gap-2 rounded-2xl p-2 text-sm font-semibold text-slate-700 xl:w-auto">
                <a
                    href="{{ route('super-admin.subscriptions.index', ['tab' => 'manage']) }}"
                    class="rounded-full px-4 py-2 transition {{ $activeTab === 'manage' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                >
                    {{ __('admin.subscription_tab_manage') }}
                </a>
                <a
                    href="{{ route('super-admin.subscriptions.index', ['tab' => 'logs']) }}"
                    class="rounded-full px-4 py-2 transition {{ $activeTab === 'logs' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                >
                    {{ __('admin.subscription_tab_logs') }}
                </a>
                <a
                    href="{{ route('super-admin.subscriptions.index', ['tab' => 'features']) }}"
                    class="rounded-full px-4 py-2 transition {{ $activeTab === 'features' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                >
                    {{ __('admin.subscription_tab_features') }}
                </a>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm shadow-sm">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-slate-600">
                    <span>
                        <span class="font-semibold text-slate-900">{{ __('admin.subscription_summary_total_logs') }}:</span>
                        {{ $logSummary['total_count'] ?? $logs->total() }}
                    </span>
                    <span>
                        <span class="font-semibold text-slate-900">{{ __('admin.subscription_summary_latest_change') }}:</span>
                        {{ $latestLogAt?->format('Y-m-d H:i') ?? '-' }}
                    </span>
                </div>
            </div>
        </div>

        @if($activeTab === 'manage')
            <div class="space-y-6">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ __('admin.subscription_tab_manage') }}</p>
                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ __('admin.subscription_plan_editor_title') }}</h2>
                                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __('admin.subscription_plan_editor_desc') }}</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_plan_discount') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $planSummary['discounted_count'] ?? 0 }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_summary_assignable_plans') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $assignablePlans->count() }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_plan_assigned_merchants') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $planSummary['assigned_count'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 p-5 xl:grid-cols-[23rem_minmax(0,1fr)]">
                        <aside>
                            <form
                                method="POST"
                                action="{{ route('super-admin.subscriptions.plans.store') }}"
                                class="rounded-3xl border border-cyan-200/70 bg-slate-50/90 p-5 shadow-sm"
                            >
                                @csrf
                                <input type="hidden" name="plan_form_mode" value="create">

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">{{ __('admin.subscription_plan_create_title') }}</p>
                                    <h3 class="mt-2 text-xl font-bold text-slate-900">{{ __('admin.subscription_plan_create_title') }}</h3>
                                    <p class="mt-2 text-sm text-slate-600">{{ __('admin.subscription_plan_create_desc') }}</p>
                                </div>

                                <div class="mt-5 space-y-4">
                                    @if($supportsCategory)
                                        <label class="block">
                                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_category') }}</span>
                                            <input
                                                type="text"
                                                name="category"
                                                value="{{ $createPlanValues['category'] }}"
                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                            >
                                        </label>
                                    @endif

                                    <label class="block">
                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_title') }}</span>
                                        <input
                                            type="text"
                                            name="name"
                                            value="{{ $createPlanValues['name'] }}"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                        >
                                    </label>

                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_plan_slug') }}</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ __('admin.subscription_plan_slug_auto_value') }}</p>
                                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('admin.subscription_plan_slug_help') }}</p>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_price') }}</span>
                                            <input
                                                type="number"
                                                min="1"
                                                step="1"
                                                name="price_twd"
                                                value="{{ $createPlanValues['price_twd'] }}"
                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                            >
                                        </label>

                                        @if($supportsDiscount)
                                            <label class="block">
                                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_discount') }}</span>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    name="discount_twd"
                                                    value="{{ $createPlanValues['discount_twd'] }}"
                                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                >
                                            </label>
                                        @endif
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_duration') }}</span>
                                            <input
                                                type="number"
                                                min="1"
                                                step="1"
                                                name="duration_days"
                                                value="{{ $createPlanValues['duration_days'] }}"
                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                            >
                                        </label>

                                        <label class="block">
                                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_store_limit') }}</span>
                                            <input
                                                type="number"
                                                min="1"
                                                step="1"
                                                name="max_stores"
                                                value="{{ $createPlanValues['max_stores'] }}"
                                                placeholder="{{ __('admin.subscription_plan_store_limit_unlimited') }}"
                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                            >
                                            <span class="mt-1.5 block text-xs text-slate-500">{{ __('admin.subscription_plan_store_limit_hint') }}</span>
                                        </label>
                                    </div>

                                    <label class="block">
                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.status') }}</span>
                                        <select
                                            name="is_active"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                        >
                                            <option value="1" @selected($createPlanValues['is_active'] === '1')>{{ __('admin.active') }}</option>
                                            <option value="0" @selected($createPlanValues['is_active'] === '0')>{{ __('admin.inactive') }}</option>
                                        </select>
                                    </label>

                                    @if($supportsDescription)
                                        <label class="block">
                                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_description') }}</span>
                                            <textarea
                                                name="description"
                                                rows="4"
                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                            >{{ $createPlanValues['description'] }}</textarea>
                                        </label>
                                    @endif
                                </div>

                                <div class="mt-5 border-t border-slate-200 pt-4">
                                    <button
                                        type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                                    >
                                        {{ __('admin.subscription_plan_create_button') }}
                                    </button>
                                </div>
                            </form>
                        </aside>

                        <div>
                            @if($plans->isEmpty())
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-slate-500">
                                    {{ __('admin.subscription_plan_empty') }}
                                </div>
                            @else
                                <div class="grid gap-5 2xl:grid-cols-2">
                                    @foreach($plans as $plan)
                                        @php
                                            $isEditingPlan = $editingPlanId === (int) $plan->id;
                                            $resolvedCategory = $plan->category ?: (string) strtok((string) $plan->slug, '-');
                                            $categoryValue = $isEditingPlan ? old('category', $resolvedCategory) : $resolvedCategory;
                                            $nameValue = $isEditingPlan ? old('name', $plan->name) : $plan->name;
                                            $priceValue = $isEditingPlan ? old('price_twd', $plan->price_twd) : $plan->price_twd;
                                            $discountValue = $isEditingPlan ? old('discount_twd', $plan->discount_twd ?? 0) : ($plan->discount_twd ?? 0);
                                            $durationValue = $isEditingPlan ? old('duration_days', $plan->duration_days) : $plan->duration_days;
                                            $maxStoresValue = $isEditingPlan ? old('max_stores', $plan->max_stores ?? '') : ($plan->max_stores ?? '');
                                            $isActiveValue = (string) ($isEditingPlan ? old('is_active', $plan->is_active ? '1' : '0') : ($plan->is_active ? '1' : '0'));
                                            $descriptionValue = $isEditingPlan ? old('description', $plan->description) : $plan->description;
                                            $originalPrice = (int) $plan->price_twd + (int) ($plan->discount_twd ?? 0);
                                            $assignedMerchantsCount = (int) ($plan->merchant_users_count ?? 0);
                                            $canDeletePlan = $assignedMerchantsCount === 0;
                                        @endphp

                                        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                            <form
                                                id="plan-update-{{ $plan->id }}"
                                                method="POST"
                                                action="{{ route('super-admin.subscriptions.plans.update', $plan) }}"
                                                class="space-y-5"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="plan_form_mode" value="update">
                                                <input type="hidden" name="editing_plan_id" value="{{ $plan->id }}">

                                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="inline-flex rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 ring-1 ring-inset ring-cyan-200">
                                                                {{ $formatPlanCategory($resolvedCategory) }}
                                                            </span>
                                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $planStatusClasses($plan->is_active) }}">
                                                                {{ $plan->is_active ? __('admin.active') : __('admin.inactive') }}
                                                            </span>
                                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                {{ __('admin.subscription_plan_assigned_merchants') }}: {{ $assignedMerchantsCount }}
                                                            </span>
                                                        </div>

                                                        <h3 class="mt-3 text-xl font-bold text-slate-900">{{ $plan->name }}</h3>
                                                        <p class="mt-1 text-xs text-slate-500">
                                                            {{ __('admin.subscription_plan_slug') }}:
                                                            <span class="font-medium text-slate-700">{{ $plan->slug }}</span>
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-3 text-right shadow-sm sm:min-w-48">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_plan_price') }}</p>
                                                        <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $formatMoney($plan->price_twd) }}</p>
                                                        @if($supportsDiscount && (int) ($plan->discount_twd ?? 0) > 0)
                                                            <p class="mt-1 text-xs font-medium text-rose-600">
                                                                {{ __('admin.subscription_plan_discount') }} {{ $formatMoney($plan->discount_twd ?? 0) }}
                                                            </p>
                                                            <p class="mt-1 text-xs text-slate-500">{{ $formatMoney($originalPrice) }}</p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    @if($supportsCategory)
                                                        <label class="block">
                                                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_category') }}</span>
                                                            <input
                                                                type="text"
                                                                name="category"
                                                                value="{{ $categoryValue }}"
                                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                            >
                                                        </label>
                                                    @endif

                                                    <label class="block">
                                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_title') }}</span>
                                                        <input
                                                            type="text"
                                                            name="name"
                                                            value="{{ $nameValue }}"
                                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                        >
                                                    </label>

                                                    <label class="block">
                                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_slug') }}</span>
                                                        <input
                                                            type="text"
                                                            value="{{ $plan->slug }}"
                                                            readonly
                                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none"
                                                        >
                                                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">{{ __('admin.subscription_plan_slug_help') }}</span>
                                                    </label>

                                                    <label class="block">
                                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_price') }}</span>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            step="1"
                                                            name="price_twd"
                                                            value="{{ $priceValue }}"
                                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                        >
                                                    </label>

                                                    @if($supportsDiscount)
                                                        <label class="block">
                                                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_discount') }}</span>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                step="1"
                                                                name="discount_twd"
                                                                value="{{ $discountValue }}"
                                                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                            >
                                                        </label>
                                                    @endif

                                                    <label class="block">
                                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_duration') }}</span>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            step="1"
                                                            name="duration_days"
                                                            value="{{ $durationValue }}"
                                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                        >
                                                    </label>

                                                    <label class="block">
                                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_store_limit') }}</span>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            step="1"
                                                            name="max_stores"
                                                            value="{{ $maxStoresValue }}"
                                                            placeholder="{{ __('admin.subscription_plan_store_limit_unlimited') }}"
                                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                        >
                                                    </label>

                                                    <label class="block">
                                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.status') }}</span>
                                                        <select
                                                            name="is_active"
                                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                        >
                                                            <option value="1" @selected($isActiveValue === '1')>{{ __('admin.active') }}</option>
                                                            <option value="0" @selected($isActiveValue === '0')>{{ __('admin.inactive') }}</option>
                                                        </select>
                                                    </label>
                                                </div>

                                                @if($supportsDescription)
                                                    <label class="block">
                                                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.subscription_plan_description') }}</span>
                                                        <textarea
                                                            name="description"
                                                            rows="4"
                                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                        >{{ $descriptionValue }}</textarea>
                                                    </label>
                                                @endif

                                                <div class="grid gap-3 sm:grid-cols-3">
                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_plan_duration') }}</p>
                                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ __('admin.subscription_plan_days_value', ['days' => $plan->duration_days]) }}</p>
                                                    </div>
                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_log_stores') }}</p>
                                                        <p class="mt-2 text-sm font-semibold text-slate-900">
                                                            {{ $plan->max_stores === null ? __('admin.subscription_unlimited_stores') : __('admin.subscription_max_stores', ['max' => $plan->max_stores]) }}
                                                        </p>
                                                    </div>
                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_plan_overview') }}</p>
                                                        <p class="mt-2 text-sm font-semibold text-slate-900">
                                                            {{ __('admin.subscription_plan_option', [
                                                                'name' => $plan->name,
                                                                'days' => $plan->duration_days,
                                                                'stores' => $plan->max_stores === null
                                                                    ? __('admin.subscription_unlimited_stores')
                                                                    : __('admin.subscription_max_stores', ['max' => $plan->max_stores]),
                                                            ]) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </form>

                                            <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-xs text-slate-500">
                                                        {{ $canDeletePlan ? __('admin.subscription_plan_public_note') : __('admin.subscription_plan_delete_blocked_note', ['count' => $assignedMerchantsCount]) }}
                                                    </p>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if($canDeletePlan)
                                                        <form
                                                            method="POST"
                                                            action="{{ route('super-admin.subscriptions.plans.destroy', $plan) }}"
                                                            onsubmit="return confirm('{{ __('admin.subscription_plan_delete_confirm', ['name' => $plan->name]) }}')"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="inline-flex items-center justify-center rounded-2xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                                                            >
                                                                {{ __('admin.subscription_plan_delete') }}
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button
                                                            type="button"
                                                            disabled
                                                            class="inline-flex cursor-not-allowed items-center justify-center rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-400"
                                                        >
                                                            {{ __('admin.subscription_plan_delete') }}
                                                        </button>
                                                    @endif

                                                    <button
                                                        type="submit"
                                                        form="plan-update-{{ $plan->id }}"
                                                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                                                    >
                                                        {{ __('admin.update') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ __('admin.manage') }}</p>
                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ __('admin.subscription_assignment_title') }}</h2>
                                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __('admin.subscription_assignment_desc') }}</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.merchant_account') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $merchantSummary['total_count'] ?? 0 }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_active') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $merchantSummary['active_count'] ?? 0 }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_inactive') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $merchantSummary['inactive_count'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_220px]">
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.search') }}</span>
                                <input
                                    type="search"
                                    data-merchant-search
                                    placeholder="{{ __('admin.subscription_assignment_search_placeholder') }}"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                >
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.status') }}</span>
                                <select
                                    data-merchant-status
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                >
                                    <option value="all">{{ __('admin.subscription_status_all') }}</option>
                                    <option value="active">{{ __('admin.subscription_status_active_only') }}</option>
                                    <option value="inactive">{{ __('admin.subscription_status_inactive_only') }}</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4 p-5" data-merchant-list>
                        @forelse($merchants as $merchant)
                            @php
                                $hasActiveSubscription = $merchant->hasActiveSubscription();
                                $isEditingMerchant = $editingMerchantId === (int) $merchant->id;
                                $selectedPlanId = (int) ($isEditingMerchant ? old('plan_id', $merchant->subscription_plan_id) : $merchant->subscription_plan_id);
                                $selectedAction = (string) ($isEditingMerchant ? old('action', 'activate') : 'activate');
                                $merchantPlanCategory = $merchant->subscriptionPlan ? $formatPlanCategory($merchant->subscriptionPlan->category) : null;
                                $merchantSearchIndex = mb_strtolower(implode(' ', array_filter([
                                    (string) $merchant->name,
                                    (string) $merchant->email,
                                    (string) ($merchant->subscriptionPlan?->name ?? ''),
                                    (string) ($merchant->subscriptionPlan?->slug ?? ''),
                                    (string) ($merchantPlanCategory ?? ''),
                                ])));
                                $expiringSoon = $merchant->subscription_ends_at !== null
                                    && $merchant->subscription_ends_at->greaterThanOrEqualTo(now()->startOfDay())
                                    && $merchant->subscription_ends_at->lessThanOrEqualTo(now()->copy()->addDays(7)->endOfDay());
                            @endphp

                            <article
                                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                data-merchant-card
                                data-status="{{ $hasActiveSubscription ? 'active' : 'inactive' }}"
                                data-search="{{ $merchantSearchIndex }}"
                            >
                                <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                                    <div class="space-y-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-xl font-bold text-slate-900">{{ $merchant->name }}</h3>
                                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $merchantStatusClasses($hasActiveSubscription) }}">
                                                        {{ $hasActiveSubscription ? __('admin.subscription_active') : __('admin.subscription_inactive') }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-sm text-slate-600">{{ $merchant->email }}</p>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600">
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_log_stores') }}</p>
                                                <p class="mt-2 font-semibold text-slate-900">{{ (int) ($merchant->active_stores_count ?? 0) }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ (int) ($merchant->stores_count ?? 0) }}</p>
                                            </div>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-3">
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.current_plan') }}</p>
                                                @if($merchant->subscriptionPlan)
                                                    <p class="mt-2 font-semibold text-slate-900">{{ $merchant->subscriptionPlan->name }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $merchantPlanCategory }}</p>
                                                @else
                                                    <p class="mt-2 font-semibold text-slate-900">-</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.subscription_never_assigned') }}</p>
                                                @endif
                                            </div>

                                            <div class="rounded-2xl border px-4 py-3 {{ $expiringSoon ? 'border-amber-200 bg-amber-50/80' : 'border-slate-200 bg-slate-50/80' }}">
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] {{ $expiringSoon ? 'text-amber-700' : 'text-slate-500' }}">{{ __('admin.expiry_date') }}</p>
                                                <p class="mt-2 font-semibold {{ $expiringSoon ? 'text-amber-900' : 'text-slate-900' }}">
                                                    {{ $merchant->subscription_ends_at?->format('Y-m-d') ?? '-' }}
                                                </p>
                                                <p class="mt-1 text-xs {{ $expiringSoon ? 'text-amber-700' : 'text-slate-500' }}">
                                                    {{ $hasActiveSubscription ? __('admin.subscription_active') : __('admin.subscription_inactive') }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.status') }}</p>
                                                <p class="mt-2 font-semibold text-slate-900">{{ $hasActiveSubscription ? __('admin.subscription_active') : __('admin.subscription_inactive') }}</p>
                                                @if($merchant->subscriptionPlan)
                                                    <p class="mt-1 text-xs text-slate-500">{{ $merchant->subscriptionPlan->slug }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('super-admin.subscriptions.update', $merchant) }}"
                                        class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="editing_merchant_id" value="{{ $merchant->id }}">

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <label class="block">
                                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.current_plan') }}</span>
                                                <select
                                                    name="plan_id"
                                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                >
                                                    @foreach($assignablePlans as $plan)
                                                        <option value="{{ $plan->id }}" @selected($selectedPlanId === $plan->id)>
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
                                            </label>

                                            <label class="block">
                                                <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.actions') }}</span>
                                                <select
                                                    name="action"
                                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                >
                                                    <option value="activate" @selected($selectedAction === 'activate')>{{ __('admin.subscription_action_activate') }}</option>
                                                    <option value="expire" @selected($selectedAction === 'expire')>{{ __('admin.subscription_action_expire') }}</option>
                                                </select>
                                            </label>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                            <p class="font-semibold text-slate-900">{{ __('admin.subscription_assignment_action_label') }}</p>
                                            <p class="mt-1">{{ __('admin.subscription_assignment_hint') }}</p>
                                        </div>

                                        <div class="mt-4 flex justify-end">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                                            >
                                                {{ __('admin.update') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-slate-500">
                                {{ __('admin.no_merchant_accounts') }}
                            </div>
                        @endforelse
                    </div>

                    <div class="hidden px-5 pb-5" data-merchant-empty>
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-slate-500">
                            {{ __('admin.subscription_filtered_empty') }}
                        </div>
                    </div>

                    <div class="border-t border-slate-200 px-6 py-4">
                        {{ $merchants->appends(['tab' => 'manage'])->links() }}
                    </div>
                </section>
            </div>
        @elseif($activeTab === 'features')
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ __('admin.subscription_tab_features') }}</p>
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ __('admin.nav_feature_title') }}</h2>
                            <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __('admin.nav_feature_desc') }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.nav_feature_total') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $navFeatureSummary['total_count'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-emerald-50/80 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">{{ __('admin.nav_feature_enabled_count') }}</p>
                                <p class="mt-2 text-sm font-semibold text-emerald-900">{{ $navFeatureSummary['enabled_count'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-rose-50/80 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-700">{{ __('admin.nav_feature_disabled_count') }}</p>
                                <p class="mt-2 text-sm font-semibold text-rose-900">{{ $navFeatureSummary['disabled_count'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('super-admin.subscriptions.features.update') }}" class="space-y-6 p-5">
                    @csrf
                    @method('PATCH')

                    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/80 px-4 py-3 text-sm text-cyan-900">
                        {{ __('admin.nav_feature_hint') }}
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        @foreach($navFeatureDefinitions as $featureKey => $featureDefinition)
                            @php
                                $isEnabled = (bool) ($navFeatureStates[$featureKey] ?? false);
                                $featureConfig = $navFeatureConfigurations[$featureKey] ?? [];
                                $featurePlacement = $featureConfig['placement'] ?? \App\Support\NavFeature::PLACEMENT_DROPDOWN;
                                $featureOrder = (int) ($featureConfig['order'] ?? 999);
                            @endphp
                            <article class="rounded-3xl border {{ $isEnabled ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-slate-50/70' }} p-5 shadow-sm">
                                <input type="hidden" name="features[{{ $featureKey }}]" value="0">

                                <label class="flex items-start gap-4">
                                    <input
                                        type="checkbox"
                                        name="features[{{ $featureKey }}]"
                                        value="1"
                                        @checked($isEnabled)
                                        class="mt-1 h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                                    >

                                    <span class="block min-w-0">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-lg font-semibold text-slate-900">{{ __($featureDefinition['label_key']) }}</span>
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $isEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                                {{ $isEnabled ? __('admin.active') : __('admin.inactive') }}
                                            </span>
                                        </span>
                                        <span class="mt-2 block text-sm leading-6 text-slate-600">{{ __($featureDefinition['description_key']) }}</span>
                                    </span>
                                </label>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <label class="block">
                                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('admin.nav_feature_placement_label') }}</span>
                                        <select
                                            name="placements[{{ $featureKey }}]"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                        >
                                            <option value="{{ \App\Support\NavFeature::PLACEMENT_LINKS }}" @selected($featurePlacement === \App\Support\NavFeature::PLACEMENT_LINKS)>
                                                {{ __('admin.nav_feature_placement_links') }}
                                            </option>
                                            <option value="{{ \App\Support\NavFeature::PLACEMENT_DROPDOWN }}" @selected($featurePlacement === \App\Support\NavFeature::PLACEMENT_DROPDOWN)>
                                                {{ __('admin.nav_feature_placement_dropdown') }}
                                            </option>
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('admin.nav_feature_order_label') }}</span>
                                        <input
                                            type="number"
                                            min="1"
                                            max="999"
                                            step="1"
                                            name="orders[{{ $featureKey }}]"
                                            value="{{ old('orders.' . $featureKey, $featureOrder) }}"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                        >
                                    </label>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="flex justify-end border-t border-slate-200 pt-4">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            {{ __('admin.nav_feature_save') }}
                        </button>
                    </div>
                </form>
            </section>
        @else
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ __('admin.subscription_tab_logs') }}</p>
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ __('admin.subscription_logs_title') }}</h2>
                            <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __('admin.subscription_logs_desc') }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_summary_total_logs') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $logSummary['total_count'] ?? $logs->total() }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_summary_latest_change') }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $latestLogAt?->format('Y-m-d H:i') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.search') }}</span>
                            <input
                                type="search"
                                data-log-search
                                placeholder="{{ __('admin.subscription_log_search_placeholder') }}"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            >
                        </label>
                    </div>
                </div>

                <div class="space-y-4 p-5" data-log-list>
                    @forelse($logs as $log)
                        @php
                            $logSearchIndex = mb_strtolower(implode(' ', array_filter([
                                (string) ($log->merchantUser?->name ?? ''),
                                (string) ($log->merchantUser?->email ?? ''),
                                (string) ($log->adminUser?->name ?? ''),
                                (string) ($log->adminUser?->email ?? ''),
                                (string) ($log->old_plan_name ?? $log->oldPlan?->name ?? ''),
                                (string) ($log->new_plan_name ?? $log->newPlan?->name ?? ''),
                                (string) ($log->store_names_snapshot ?? ''),
                                (string) $log->action,
                            ])));
                        @endphp

                        <article
                            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            data-log-card
                            data-search="{{ $logSearchIndex }}"
                        >
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $actionBadgeClasses($log->action) }}">
                                            {{ __('admin.subscription_log_action_' . $log->action) }}
                                        </span>
                                        <span class="text-sm text-slate-500">{{ $log->created_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                    </div>

                                    <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $log->merchantUser?->name ?? '-' }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ $log->merchantUser?->email ?? '-' }}</p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_log_admin') }}</p>
                                    <p class="mt-2 font-semibold text-slate-900">{{ $log->adminUser?->name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $log->adminUser?->email ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 xl:grid-cols-[0.9fr_1fr_1fr]">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_log_stores') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $log->store_names_snapshot ?: '-' }}</p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_log_before_state') }}</p>
                                    <p class="mt-3 font-semibold text-slate-900">{{ $log->old_plan_name ?? ($log->oldPlan?->name ?? '-') }}</p>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ __('admin.status') }}:
                                        {{ $log->old_status === 'active' ? __('admin.subscription_active') : __('admin.subscription_inactive') }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ __('admin.expiry_date') }}:
                                        {{ $log->old_subscription_ends_at?->format('Y-m-d H:i') ?? '-' }}
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.subscription_log_after_state') }}</p>
                                    <p class="mt-3 font-semibold text-slate-900">{{ $log->new_plan_name ?? ($log->newPlan?->name ?? '-') }}</p>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ __('admin.status') }}:
                                        {{ $log->new_status === 'active' ? __('admin.subscription_active') : __('admin.subscription_inactive') }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ __('admin.expiry_date') }}:
                                        {{ $log->new_subscription_ends_at?->format('Y-m-d H:i') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-slate-500">
                            {{ __('admin.subscription_log_empty') }}
                        </div>
                    @endforelse
                </div>

                <div class="hidden px-5 pb-5" data-log-empty>
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-slate-500">
                        {{ __('admin.subscription_filtered_empty') }}
                    </div>
                </div>

                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $logs->appends(['tab' => 'logs'])->links() }}
                </div>
            </section>
        @endif
    </div>
</div>

<script>
(() => {
    const bindCardFilter = ({
        searchSelector,
        statusSelector = null,
        cardSelector,
        emptySelector,
    }) => {
        const searchInput = document.querySelector(searchSelector);
        const statusInput = statusSelector ? document.querySelector(statusSelector) : null;
        const emptyState = document.querySelector(emptySelector);
        const cards = Array.from(document.querySelectorAll(cardSelector));

        if (!cards.length) {
            return;
        }

        const apply = () => {
            const searchTerm = String(searchInput?.value || '').trim().toLowerCase();
            const statusValue = String(statusInput?.value || 'all');
            let visibleCount = 0;

            cards.forEach((card) => {
                const haystack = String(card.getAttribute('data-search') || '').toLowerCase();
                const status = String(card.getAttribute('data-status') || '');
                const matchesSearch = searchTerm === '' || haystack.includes(searchTerm);
                const matchesStatus = statusValue === 'all' || status === statusValue;
                const isVisible = matchesSearch && matchesStatus;

                card.classList.toggle('hidden', !isVisible);
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            emptyState?.classList.toggle('hidden', visibleCount !== 0);
        };

        searchInput?.addEventListener('input', apply);
        statusInput?.addEventListener('change', apply);
        apply();
    };

    bindCardFilter({
        searchSelector: '[data-merchant-search]',
        statusSelector: '[data-merchant-status]',
        cardSelector: '[data-merchant-card]',
        emptySelector: '[data-merchant-empty]',
    });

    bindCardFilter({
        searchSelector: '[data-log-search]',
        cardSelector: '[data-log-card]',
        emptySelector: '[data-log-empty]',
    });
})();
</script>
@endsection
