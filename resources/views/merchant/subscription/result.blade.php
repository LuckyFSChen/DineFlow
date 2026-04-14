@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">綠界付款結果</h1>

            @if($isPaid)
                <p class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    付款成功，訂閱已更新。
                </p>
            @else
                <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    付款未完成或尚待確認，請稍後至訂閱頁查看狀態。
                </p>
            @endif

            <p class="mt-4 text-xs text-slate-500">付款完成後的回傳屬於跨站 POST，瀏覽器可能暫時不附帶登入 cookie，請點下方按鈕回商家頁確認最新狀態。</p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('merchant.subscription.index') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                    回到訂閱頁
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    若被登出，前往登入
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
