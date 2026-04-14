@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">導向綠界付款中</h1>
            <p class="mt-2 text-sm text-slate-600">系統正在將你導向綠界付款頁面，請勿關閉視窗。</p>

            <form id="ecpay-checkout-form" method="POST" action="{{ $checkoutAction }}" class="mt-6">
                @foreach($payload as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                    若未自動跳轉，請點我前往付款
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('ecpay-checkout-form')?.submit();
</script>
@endsection
