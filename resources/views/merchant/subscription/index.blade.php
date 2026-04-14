@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('merchant.subscription_title') }}</h1>
            <p class="mt-2 text-slate-600">{{ __('merchant.subscription_desc') }}</p>
        </div>

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

        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('merchant.current_status') }}</h2>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <p>{{ __('merchant.plan') }}：{{ $user->subscriptionPlan?->name ?? __('merchant.not_activated') }}</p>
                <p>{{ __('merchant.expires_at') }}：{{ $user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d H:i') : __('merchant.not_activated') }}</p>
                <p>{{ __('merchant.status') }}：{{ $user->hasActiveSubscription() ? __('merchant.active') : __('merchant.inactive') }}</p>
                <p>
                    {{ __('merchant.store_usage') }}：{{ $user->stores()->count() }}
                    @if($user->subscriptionPlan?->max_stores === null)
                        / {{ __('merchant.unlimited') }}
                    @elseif($user->subscriptionPlan)
                        / {{ __('merchant.limit', ['count' => $user->subscriptionPlan->max_stores]) }}
                    @endif
                </p>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            @foreach($plans as $plan)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900">{{ $plan->name }}</h3>
                    <p class="mt-2 text-3xl font-extrabold text-brand-primary">NT$ {{ number_format($plan->price_twd) }}</p>
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
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                            {{ __('merchant.ecpay_activate') }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <p class="mt-6 text-xs text-slate-500">{{ __('merchant.payment_redirect_hint') }}</p>
    </div>
</div>
@endsection
