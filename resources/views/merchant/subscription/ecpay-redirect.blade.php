@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">綠界站內付款</h1>
            <p class="mt-2 text-sm text-slate-600">付款頁會嘗試嵌入在下方區塊。若瀏覽器或綠界設定阻擋嵌入，請使用「外開付款頁」。</p>

            <form id="ecpay-checkout-form" method="POST" action="{{ $checkoutAction }}" target="ecpay-payment-frame" class="mt-6 space-y-3">
                @foreach($payload as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                        重新載入付款頁
                    </button>
                    <button type="button" id="ecpay-open-external" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        外開付款頁
                    </button>
                </div>
            </form>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                <iframe
                    id="ecpay-payment-frame"
                    name="ecpay-payment-frame"
                    title="ECPay Payment"
                    class="h-[78vh] w-full bg-white"
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            </div>

            <p class="mt-3 text-xs text-slate-500">若下方空白或顯示拒絕嵌入，請改用「外開付款頁」。付款結果仍會由 ReturnURL 同步回系統。</p>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('ecpay-checkout-form');
    const openExternalBtn = document.getElementById('ecpay-open-external');
    let hasSubmitted = false;

    const submitOnce = (target) => {
        if (!form || hasSubmitted) {
            return false;
        }

        hasSubmitted = true;
        const originalTarget = form.target;
        form.target = target;
        form.submit();
        form.target = originalTarget;
        return true;
    };

    openExternalBtn?.addEventListener('click', () => {
        if (hasSubmitted) {
            alert('付款訂單已送出，請在目前頁面完成付款。若畫面未載入，請返回方案頁重新發起新訂單。');
            return;
        }

        submitOnce('_blank');
    });

    submitOnce('ecpay-payment-frame');
</script>
@endsection
