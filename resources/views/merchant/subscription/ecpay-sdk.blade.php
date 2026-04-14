@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">{{ __('merchant.ecpay_sdk_title') }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ __('merchant.ecpay_sdk_desc') }}</p>

            <div id="sdk-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
            <div id="sdk-info" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">{{ __('merchant.ecpay_sdk_init_loading') }}</div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
                <div id="ECPayPayment" class="min-h-[320px]"></div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button id="confirm-pay-btn" type="button" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                    {{ __('merchant.ecpay_sdk_confirm') }}
                </button>
                <a href="{{ route('merchant.subscription.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    {{ __('merchant.ecpay_sdk_back') }}
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
    const i18n = {
        errorLoad: @json(__('merchant.sdk_error_load')),
        errorInitUnavailable: @json(__('merchant.sdk_error_init_unavailable')),
        errorApplePay: @json(__('merchant.sdk_error_applepay', ['message' => '__message__'])),
        errorInit: @json(__('merchant.sdk_error_init', ['message' => '__message__'])),
        errorCreatePayment: @json(__('merchant.sdk_error_create_payment', ['message' => '__message__'])),
        errorPaytoken: @json(__('merchant.sdk_error_paytoken', ['message' => '__message__'])),
        errorNoPaytoken: @json(__('merchant.sdk_error_no_paytoken')),
        errorCreateTrade: @json(__('merchant.sdk_error_create_trade')),
        infoInitDone: @json(__('merchant.sdk_info_init_done')),
        infoReady: @json(__('merchant.sdk_info_ready')),
        infoGetToken: @json(__('merchant.sdk_info_get_token')),
        infoSyncing: @json(__('merchant.sdk_info_syncing')),
        infoCreatingTrade: @json(__('merchant.sdk_info_creating_trade')),
        infoRetry: @json(__('merchant.sdk_info_retry')),
        infoRetryCheck: @json(__('merchant.sdk_info_retry_check')),
        infoTokenInvalid: @json(__('merchant.sdk_info_token_invalid')),
        infoTradeDone: @json(__('merchant.sdk_info_trade_done')),
    };
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

    const withMessage = (template, message) => template.replace('__message__', message || '');

    if (!window.ECPay) {
        showError(i18n.errorLoad);
        setInfo(i18n.errorInitUnavailable);
        return;
    }

    const language = window.ECPay.Language?.zhTW || 'zhTW';

    window.getApplePayResultData = function(resultData, errMsg) {
        if (errMsg) {
            showError(withMessage(i18n.errorApplePay, errMsg));
            return;
        }

        if (resultData) {
            setInfo(i18n.infoSyncing);
        }
    };

    window.ECPay.initialize(sdkServerType, 1, function(initErr) {
        if (initErr) {
            showError(withMessage(i18n.errorInit, initErr));
            setInfo(i18n.errorInitUnavailable);
            return;
        }

        setInfo(i18n.infoInitDone);

        window.ECPay.createPayment(token, language, function(createErr) {
            if (createErr) {
                showError(withMessage(i18n.errorCreatePayment, createErr));
                setInfo(i18n.infoRetry);
                return;
            }

            hideError();
            setInfo(i18n.infoReady);
        }, 'V2');
    });

    confirmBtn?.addEventListener('click', function() {
        hideError();
        setInfo(i18n.infoGetToken);
        confirmBtn.disabled = true;

        window.ECPay.getPayToken(function(paymentInfo, errMsg) {
            if (errMsg) {
                showError(withMessage(i18n.errorPaytoken, errMsg));
                setInfo(i18n.infoRetryCheck);
                confirmBtn.disabled = false;
                return;
            }

            const payToken = paymentInfo?.PayToken || '';
            const paymentType = paymentInfo?.PaymentType || '';

            if (!payToken) {
                showError(i18n.errorNoPaytoken);
                setInfo(i18n.infoTokenInvalid);
                confirmBtn.disabled = false;
                return;
            }

            setInfo(i18n.infoCreatingTrade);

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
                    throw new Error(data.message || i18n.errorCreateTrade);
                }

                setInfo(data.message || i18n.infoTradeDone);

                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }

                window.location.href = @json(route('merchant.subscription.success'));
            })
            .catch((error) => {
                showError(error.message || i18n.errorCreateTrade);
                setInfo(i18n.infoRetry);
                confirmBtn.disabled = false;
            });
        });
    });
})();
</script>
@endsection
