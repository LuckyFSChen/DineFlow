@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">{{ __('merchant.ecpay_result_title') }}</h1>

            @if($isPaid)
                <p class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ __('merchant.ecpay_result_paid') }}
                </p>
            @else
                <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    {{ __('merchant.ecpay_result_unpaid') }}
                </p>
            @endif

            <p class="mt-4 text-xs text-slate-500">{{ __('merchant.ecpay_result_hint') }}</p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('merchant.subscription.index') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                    {{ __('merchant.back_to_subscription') }}
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    {{ __('merchant.go_login_if_needed') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
