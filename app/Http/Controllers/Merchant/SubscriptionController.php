<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\EcpayService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price_twd')
            ->get();

        return view('merchant.subscription.index', [
            'plans' => $plans,
            'user' => $request->user(),
        ]);
    }

    public function subscribe(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $user = $request->user();
        if (! $user || ! $user->isMerchant()) {
            abort(403, '僅商家帳號可訂閱方案。');
        }

        $plan = SubscriptionPlan::query()
            ->where('id', $validated['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $merchantId = (string) config('services.ecpay.merchant_id');
        $hashKey = (string) config('services.ecpay.hash_key');
        $hashIv = (string) config('services.ecpay.hash_iv');
        $checkoutAction = (string) config('services.ecpay.checkout_action');

        if ($merchantId === '' || $hashKey === '' || $hashIv === '' || $checkoutAction === '') {
            return redirect()
                ->route('merchant.subscription.index')
                ->with('error', '綠界金流尚未設定，請先設定 ECPAY 參數。');
        }

        $merchantTradeNo = $this->generateMerchantTradeNo();
        $itemName = 'DineFlow ' . $plan->name . ' 訂閱方案';

        $payload = [
            'MerchantID' => $merchantId,
            'MerchantTradeNo' => $merchantTradeNo,
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) $plan->price_twd,
            'TradeDesc' => 'DineFlow Merchant Subscription',
            'ItemName' => $itemName,
            'ReturnURL' => route('ecpay.subscription.notify'),
            'OrderResultURL' => route('ecpay.subscription.result'),
            'ChoosePayment' => 'ALL',
            'ClientBackURL' => route('merchant.subscription.success'),
            'EncryptType' => 1,
            'CustomField1' => (string) $user->id,
            'CustomField2' => (string) $plan->id,
        ];

        $payload['CheckMacValue'] = EcpayService::generateCheckMacValue($payload, $hashKey, $hashIv);

        SubscriptionPayment::query()->updateOrCreate(
            ['ecpay_merchant_trade_no' => $merchantTradeNo],
            [
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'amount_twd' => (int) $plan->price_twd,
                'currency' => 'twd',
                'status' => 'pending',
                'paid_at' => null,
                'ecpay_trade_no' => null,
                'ecpay_payment_type' => null,
                'payload' => ['request' => $payload],
            ]
        );

        return view('merchant.subscription.ecpay-redirect', [
            'checkoutAction' => $checkoutAction,
            'payload' => $payload,
        ]);
    }

    public function success(Request $request): RedirectResponse
    {
        return redirect()
            ->route('merchant.subscription.index')
            ->with('success', '已返回 DineFlow，付款結果同步中，請稍候重新整理查看狀態。');
    }

    public function notify(Request $request): Response
    {
        $hashKey = (string) config('services.ecpay.hash_key');
        $hashIv = (string) config('services.ecpay.hash_iv');

        if ($hashKey === '' || $hashIv === '') {
            return response('0|ECPAY_NOT_CONFIGURED', 500);
        }

        $normalized = $this->normalizeEcpayCallbackPayload($request);
        if ($normalized['is_check_mac_required'] && ! EcpayService::verifyCheckMacValue($normalized['payload'], $hashKey, $hashIv)) {
            return response('0|CHECKMAC_INVALID', 400);
        }

        $this->applyPaymentResult($normalized['payload']);

        return response('1|OK', 200);
    }

    private function generateMerchantTradeNo(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'DF' . now()->format('ymdHis') . strtoupper(Str::random(6));

            if (! SubscriptionPayment::query()->where('ecpay_merchant_trade_no', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'DF' . now()->format('ymdHis') . strtoupper(Str::random(6));
    }

    public function result(Request $request): View
    {
        $normalized = $this->normalizeEcpayCallbackPayload($request);
        $isPaid = $this->applyPaymentResult($normalized['payload']);

        return view('merchant.subscription.result', [
            'isPaid' => $isPaid,
        ]);
    }

    private function normalizeEcpayCallbackPayload(Request $request): array
    {
        $payload = $request->all();

        if (isset($payload['ResultData']) && is_string($payload['ResultData'])) {
            $decodedResultData = json_decode($payload['ResultData'], true);
            if (is_array($decodedResultData)) {
                $payload = $decodedResultData;
            }
        }

        if (isset($payload['Data']) && is_string($payload['Data'])) {
            $decodedData = json_decode($payload['Data'], true);
            if (is_array($decodedData)) {
                $payload = array_merge($payload, $decodedData);
            }
        }

        return [
            'payload' => $payload,
            'is_check_mac_required' => isset($payload['CheckMacValue']),
        ];
    }

    private function applyPaymentResult(array $data): bool
    {
        $merchantTradeNo = (string) (Arr::get($data, 'MerchantTradeNo') ?: Arr::get($data, 'OrderInfo.MerchantTradeNo', ''));
        if ($merchantTradeNo === '') {
            return false;
        }

        $existingPayment = SubscriptionPayment::query()
            ->where('ecpay_merchant_trade_no', $merchantTradeNo)
            ->first();

        $customField = Arr::get($data, 'CustomField');
        $customFieldData = is_string($customField) ? json_decode($customField, true) : [];
        if (! is_array($customFieldData)) {
            $customFieldData = [];
        }

        $userId = (int) (Arr::get($data, 'CustomField1') ?: Arr::get($customFieldData, 'user_id') ?: $existingPayment?->user_id ?: 0);
        $planId = (int) (Arr::get($data, 'CustomField2') ?: Arr::get($customFieldData, 'plan_id') ?: $existingPayment?->subscription_plan_id ?: 0);

        $user = User::query()->find($userId);
        $plan = SubscriptionPlan::query()->find($planId);
        if (! $user || ! $plan) {
            return false;
        }

        $tradeStatus = (string) Arr::get($data, 'OrderInfo.TradeStatus', '');
        $rtnCode = (string) Arr::get($data, 'RtnCode', '');
        $isPaid = $rtnCode === '1' || $tradeStatus === '1';

        $paidAt = null;
        if ($isPaid) {
            $base = $user->subscription_ends_at && $user->subscription_ends_at->isFuture()
                ? Carbon::parse($user->subscription_ends_at)
                : now();

            $user->update([
                'subscription_plan_id' => $plan->id,
                'subscription_ends_at' => $base->copy()->addDays((int) $plan->duration_days),
            ]);

            $paidAt = now();
        }

        $amount = Arr::get($data, 'TradeAmt', Arr::get($data, 'OrderInfo.TradeAmt', 0));
        $tradeNo = (string) (Arr::get($data, 'TradeNo') ?: Arr::get($data, 'OrderInfo.TradeNo', ''));
        $paymentType = (string) (Arr::get($data, 'PaymentType') ?: Arr::get($data, 'OrderInfo.PaymentType', ''));

        SubscriptionPayment::query()->updateOrCreate(
            ['ecpay_merchant_trade_no' => $merchantTradeNo],
            [
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'amount_twd' => max((int) $amount, 0),
                'currency' => 'twd',
                'status' => $isPaid ? 'paid' : 'failed',
                'paid_at' => $paidAt,
                'ecpay_trade_no' => $tradeNo,
                'ecpay_payment_type' => $paymentType,
                'payload' => $data,
            ]
        );

        return $isPaid;
    }
}
