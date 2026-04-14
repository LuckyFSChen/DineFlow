@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">商家訂閱方案</h1>
            <p class="mt-2 text-slate-600">啟用方案後，才能建立與管理店家內容。</p>
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
            <h2 class="text-lg font-semibold text-slate-900">目前訂閱狀態</h2>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <p>方案：{{ $user->subscriptionPlan?->name ?? '未啟用' }}</p>
                <p>到期日：{{ $user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d H:i') : '未啟用' }}</p>
                <p>狀態：{{ $user->hasActiveSubscription() ? '有效' : '未啟用 / 已到期' }}</p>
                <p>
                    店家使用量：{{ $user->stores()->count() }}
                    @if($user->subscriptionPlan?->max_stores === null)
                        / 不限
                    @elseif($user->subscriptionPlan)
                        / 上限 {{ $user->subscriptionPlan->max_stores }}
                    @endif
                </p>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            @foreach($plans as $plan)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900">{{ $plan->name }}</h3>
                    <p class="mt-2 text-3xl font-extrabold text-brand-primary">NT$ {{ number_format($plan->price_twd) }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $plan->duration_days }} 天</p>
                    <p class="mt-1 text-sm text-slate-500">店家數：{{ $plan->max_stores === null ? '不限' : '最多 ' . $plan->max_stores . ' 間' }}</p>

                    <ul class="mt-4 space-y-2 text-sm text-slate-700">
                        @foreach(($plan->features ?? []) as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('merchant.subscription.subscribe') }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                            使用 Stripe 啟用 / 續訂
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <p class="mt-6 text-xs text-slate-500">測試模式可使用 Stripe 測試卡號，例如 4242 4242 4242 4242。</p>
    </div>
</div>
@endsection
