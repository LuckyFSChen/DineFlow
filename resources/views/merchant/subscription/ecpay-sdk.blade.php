@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">綠界站內付 2.0</h1>
            <p class="mt-2 text-sm text-slate-600">請在下方完成付款流程，按「確認付款」後系統會呼叫 getPayToken 並建立交易。</p>

            <div id="sdk-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
            <div id="sdk-info" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">初始化付款元件中，請稍候...</div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
                <div id="ecpay-payment-container" class="min-h-[320px]"></div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button id="confirm-pay-btn" type="button" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                    確認付款
                </button>
                <a href="{{ route('merchant.subscription.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    返回訂閱頁
                </a>
            </div>
        </div>
    </div>
</div>

<script src="{{ $sdkUrl }}"></script>
<script>
(() => {
    const token = @json($token);
    const merchantTradeNo = @json($merchantTradeNo);
    const sdkServerType = @json($sdkServerType);
    const infoBox = document.getElementById('sdk-info');
    const errorBox = document.getElementById('sdk-error');
    const confirmBtn = document.getElementById('confirm-pay-btn');

    const setInfo = (message) => {
        if (infoBox) {
            infoBox.textContent = message;
        }
    };

    const showError = (message) => {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    const hideError = () => {
        errorBox?.classList.add('hidden');
        if (errorBox) {
            errorBox.textContent = '';
        }
    };

    if (!window.ECPay) {
        showError('ECPay SDK 載入失敗，請重新整理後再試。');
        setInfo('無法初始化付款元件。');
        return;
    }

    const language = window.ECPay.Language?.zhTW || 'zhTW';

    window.getApplePayResultData = function(resultData, errMsg) {
        if (errMsg) {
            showError('Apple Pay 回傳錯誤：' + errMsg);
            return;
        }

        if (resultData) {
            setInfo('已接收 Apple Pay 回傳，系統同步中...');
        }
    };

    window.ECPay.initialize(sdkServerType, 1, function(initErr) {
        if (initErr) {
            showError('初始化失敗：' + initErr);
            setInfo('初始化失敗。');
            return;
        }

        setInfo('初始化完成，載入付款畫面中...');

        window.ECPay.createPayment(token, language, function(createErr) {
            if (createErr) {
                showError('載入付款畫面失敗：' + createErr);
                setInfo('請稍後重試。');
                return;
            }

            hideError();
            setInfo('付款元件已就緒，請填寫付款資料後點「確認付款」。');
        }, 'V2');
    });

    confirmBtn?.addEventListener('click', function() {
        hideError();
        setInfo('取得付款代碼中...');
        confirmBtn.disabled = true;

        window.ECPay.getPayToken(function(paymentInfo, errMsg) {
            if (errMsg) {
                showError('取得付款代碼失敗：' + errMsg);
                setInfo('請確認付款資料後再試一次。');
                confirmBtn.disabled = false;
                return;
            }

            const payToken = paymentInfo?.PayToken || '';
            const paymentType = paymentInfo?.PaymentType || '';

            if (!payToken) {
                showError('未取得 PayToken，請重試。');
                setInfo('付款代碼異常。');
                confirmBtn.disabled = false;
                return;
            }

            setInfo('建立交易中，請稍候...');

            fetch(@json(route('merchant.subscription.trade')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({
                    merchant_trade_no: merchantTradeNo,
                    pay_token: payToken,
                    payment_type: paymentType,
                }),
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || '建立交易失敗');
                }

                setInfo(data.message || '交易已建立，等待付款結果回傳。');

                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }

                window.location.href = @json(route('merchant.subscription.success'));
            })
            .catch((error) => {
                showError(error.message || '建立交易失敗');
                setInfo('請再試一次。');
                confirmBtn.disabled = false;
            });
        });
    });
})();
</script>
@endsection
