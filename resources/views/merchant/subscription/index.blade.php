@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <x-backend-header
            :title="__('merchant.subscription_title')"
            :subtitle="__('merchant.subscription_desc')"
        />

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @php
            $tierLabels = [
                'basic' => __('merchant.plan_tier_basic'),
                'growth' => __('merchant.plan_tier_growth'),
                'pro' => __('merchant.plan_tier_pro'),
            ];
            $cycleLabels = [
                'monthly' => __('merchant.plan_cycle_monthly'),
                'quarterly' => __('merchant.plan_cycle_quarterly'),
                'yearly' => __('merchant.plan_cycle_yearly'),
            ];
        @endphp

        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('merchant.current_status') }}</h2>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <p>{{ __('merchant.plan') }}: {{ $user->subscriptionPlan ? ($tierLabels[strtok($user->subscriptionPlan->slug, '-')] ?? ucfirst(strtok($user->subscriptionPlan->slug, '-'))) : __('merchant.not_activated') }}</p>
                <p>{{ __('merchant.expires_at') }}: {{ $user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d H:i') : __('merchant.not_activated') }}</p>
                <p>{{ __('merchant.status') }}: {{ $user->hasActiveSubscription() ? __('merchant.active') : __('merchant.inactive') }}</p>
                <p>試用狀態: {{ $user->trial_used_at ? '已使用' : '未使用' }}</p>
                @if($user->trial_started_at && $user->trial_ends_at)
                    <p>試用期間: {{ $user->trial_started_at->format('Y-m-d H:i') }} ~ {{ $user->trial_ends_at->format('Y-m-d H:i') }}</p>
                @endif
                <p>{{ __('merchant.pricing_currency') }}: {{ strtoupper($currencyProfile['currency_code'] ?? 'twd') }}</p>
                <p>
                    {{ __('merchant.store_usage') }}: {{ $user->stores()->where('is_active', true)->count() }}
                    @if($user->subscriptionPlan?->max_stores === null)
                        / {{ __('merchant.unlimited') }}
                    @elseif($user->subscriptionPlan)
                        / {{ __('merchant.limit', ['count' => $user->subscriptionPlan->max_stores]) }}
                    @endif
                </p>
            </div>

            @if($user->canStartTrial())
                <form method="POST" action="{{ route('merchant.subscription.trial') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500">
                        啟用 30 天免費試用
                    </button>
                    <p class="mt-2 text-xs text-slate-500">每個商家帳號僅可使用一次試用資格。</p>
                </form>
            @endif
        </div>

        <div class="space-y-8">
            @foreach($plansByTier as $tier => $tierPlans)
                <section class="space-y-4">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $tierLabels[$tier] ?? ucfirst($tier) }}</h2>
                    <div class="grid gap-5 md:grid-cols-3">
                        @foreach($tierPlans as $plan)
                            @php($pricing = $planPricing[$plan->id] ?? ['original_price_twd' => $plan->price_twd, 'payable_amount_twd' => $plan->price_twd, 'upgrade_credit_twd' => 0, 'is_upgrade_proration_applied' => false, 'is_purchase_allowed' => true, 'blocked_reason' => null, 'show_reset_time_warning' => false])
                            @php([$planTier, $planCycle] = array_pad(explode('-', $plan->slug, 2), 2, ''))
                            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 class="text-xl font-bold text-slate-900">{{ $cycleLabels[$planCycle] ?? ucfirst($planCycle) }}</h3>
                                @if(($pricing['payable_amount_twd'] ?? $plan->price_twd) < ($pricing['original_price_twd'] ?? $plan->price_twd))
                                    <p class="mt-2 text-sm font-semibold text-slate-400 line-through">{{ __('merchant.original_price') }} {{ $pricing['display_symbol'] ?? 'NT$' }} {{ number_format($pricing['display_original_amount'] ?? $pricing['original_price_twd']) }}</p>
                                    <p class="mt-1 text-3xl font-extrabold text-rose-600">{{ $pricing['display_symbol'] ?? 'NT$' }} {{ number_format($pricing['display_payable_amount'] ?? $pricing['payable_amount_twd']) }}</p>
                                    <p class="mt-1 text-xs font-medium text-emerald-600">{{ __('merchant.upgrade_credit') }} {{ $pricing['display_symbol'] ?? 'NT$' }} {{ number_format($pricing['display_upgrade_credit'] ?? ($pricing['upgrade_credit_twd'] ?? 0)) }}</p>
                                @else
                                    <p class="mt-2 text-3xl font-extrabold text-brand-primary">{{ $pricing['display_symbol'] ?? 'NT$' }} {{ number_format($pricing['display_original_amount'] ?? $plan->price_twd) }}</p>
                                @endif
                                <p class="mt-1 text-sm text-slate-500">{{ __('merchant.days', ['days' => $plan->duration_days]) }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ __('merchant.store_count_label', ['count' => $plan->max_stores === null ? __('merchant.unlimited') : __('merchant.store_count_max', ['count' => $plan->max_stores])]) }}</p>

                                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                                    @foreach(($plan->features ?? []) as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>

                                <form method="POST" action="{{ route('merchant.subscription.subscribe') }}" class="mt-6">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <button type="submit" @disabled(!($pricing['is_purchase_allowed'] ?? true)) class="inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white {{ ($pricing['is_purchase_allowed'] ?? true) ? 'bg-brand-primary hover:bg-brand-accent hover:text-brand-dark' : 'cursor-not-allowed bg-slate-300 text-slate-100' }}">
                                        {{ __('merchant.ecpay_activate') }}
                                    </button>
                                    @if(($currencyProfile['currency_code'] ?? 'twd') !== 'twd')
                                        <p class="mt-2 text-xs text-slate-500">{{ __('merchant.settlement_twd_hint') }}</p>
                                    @endif
                                    @if(!($pricing['is_purchase_allowed'] ?? true) && !empty($pricing['blocked_reason']))
                                        <p class="mt-2 text-xs text-rose-600">{{ $pricing['blocked_reason'] }}</p>
                                    @endif
                                </form>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <p class="mt-6 text-xs text-slate-500">{{ __('merchant.payment_redirect_hint') }}</p>
    </div>
</div>
@endsection
