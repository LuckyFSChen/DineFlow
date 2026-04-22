<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\EcpayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    private const FX_FROM_TWD = [
        'twd' => 1.0,
        'cny' => 0.22,
        'vnd' => 780.0,
    ];

    private const CURRENCY_SYMBOLS = [
        'twd' => 'NT$',
        'cny' => 'CNY',
        'vnd' => 'VND',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $currencyProfile = $this->resolveCurrencyProfile($user);

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->get();

        $planPricing = $plans
            ->mapWithKeys(function (SubscriptionPlan $plan) use ($user, $currencyProfile): array {
                return [$plan->id => $this->buildPricingPreview($user, $plan, $currencyProfile)];
            })
            ->all();

        $categoryOrder = ['basic' => 1, 'growth' => 2, 'pro' => 3];

        $plansByTier = $plans
            ->sortBy(function (SubscriptionPlan $plan) use ($categoryOrder): array {
                $categoryKey = strtolower(trim((string) ($plan->category ?: strtok($plan->slug, '-'))));

                return [
                    $categoryOrder[$categoryKey] ?? 99,
                    $categoryKey,
                    $plan->duration_days,
                    $plan->price_twd,
                ];
            })
            ->groupBy(function (SubscriptionPlan $plan): string {
                $category = trim((string) $plan->category);

                return $category !== '' ? $category : (string) strtok($plan->slug, '-');
            });

        return view('merchant.subscription.index', [
            'plansByTier' => $plansByTier,
            'planPricing' => $planPricing,
            'user' => $user,
            'currencyProfile' => $currencyProfile,
        ]);
    }

    public function subscribe(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $user = $request->user();
        if (! $user || ! $user->isMerchant()) {
            abort(403, __('merchant.error_only_merchant_can_subscribe'));
        }

        $plan = SubscriptionPlan::query()
            ->where('id', $validated['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $currencyProfile = $this->resolveCurrencyProfile($user);
        $pricing = $this->buildPricingPreview($user, $plan, $currencyProfile);
        if (! $pricing['is_purchase_allowed']) {
            return redirect()
                ->route('merchant.subscription.index')
                ->with('error', (string) $pricing['blocked_reason']);
        }

        $upgradeCredit = (int) $pricing['upgrade_credit_twd'];
        $payableAmount = (int) $pricing['payable_amount_twd'];
        $isUpgradeProrationApplied = (bool) $pricing['is_upgrade_proration_applied'];

        $merchantId = (string) config('services.ecpay.merchant_id');
        $hashKey = (string) config('services.ecpay.hash_key');
        $hashIv = (string) config('services.ecpay.hash_iv');
        $checkoutAction = (string) config('services.ecpay.checkout_action');

        if ($merchantId === '' || $hashKey === '' || $hashIv === '' || $checkoutAction === '') {
            return redirect()
                ->route('merchant.subscription.index')
                ->with('error', __('merchant.error_ecpay_not_configured'));
        }

        $merchantTradeNo = $this->generateMerchantTradeNo();
        $itemName = 'DineFlow ' . $plan->name . ' Subscription';

        $payload = [
            'MerchantID' => $merchantId,
            'MerchantTradeNo' => $merchantTradeNo,
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => $payableAmount,
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
                'amount_twd' => $payableAmount,
                'currency' => 'twd',
                'status' => 'pending',
                'paid_at' => null,
                'ecpay_trade_no' => null,
                'ecpay_payment_type' => null,
                'payload' => [
                    'request' => $payload,
                    'pricing' => [
                        'original_price_twd' => (int) $pricing['original_price_twd'],
                        'upgrade_credit_twd' => $upgradeCredit,
                        'payable_amount_twd' => $payableAmount,
                        'is_upgrade_proration_applied' => $isUpgradeProrationApplied,
                        'display_currency' => $currencyProfile['currency_code'],
                        'display_original_amount' => (int) $pricing['display_original_amount'],
                        'display_upgrade_credit' => (int) $pricing['display_upgrade_credit'],
                        'display_payable_amount' => (int) $pricing['display_payable_amount'],
                    ],
                ],
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
            ->with('success', '已返回 DineFlow 訂閱頁面，付款結果會在完成後自動同步。');
    }

    public function startTrial(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isMerchant()) {
            abort(403, __('merchant.error_only_merchant_can_subscribe'));
        }

        if (! $user->canStartTrial()) {
            return redirect()
                ->route('merchant.subscription.index')
                ->with('error', '目前無法啟用試用。');
        }

        $trialPlan = SubscriptionPlan::query()
            ->where('slug', 'basic-monthly')
            ->where('is_active', true)
            ->first();

        if (! $trialPlan) {
            return redirect()
                ->route('merchant.subscription.index')
                ->with('error', '試用方案尚未設定完成。');
        }

        $startsAt = now();

        $user->update([
            'subscription_plan_id' => $trialPlan->id,
            'subscription_ends_at' => $startsAt->copy()->addDays(7),
            'trial_started_at' => $startsAt,
            'trial_ends_at' => $startsAt->copy()->addDays(7),
            'trial_used_at' => $startsAt,
        ]);

        return redirect()
            ->route('merchant.subscription.index')
            ->with('success', '已啟用 7 天免費試用。');
    }

    public function notify(Request $request): Response
    {
        $hashKey = (string) config('services.ecpay.hash_key');
        $hashIv = (string) config('services.ecpay.hash_iv');

        if ($hashKey === '' || $hashIv === '') {
            return response('0|ECPAY_NOT_CONFIGURED', 500);
        }

        $normalized = $this->normalizeEcpayCallbackPayload($request);
        if (
            ! $normalized['is_check_mac_required']
            || ! EcpayService::verifyCheckMacValue($normalized['payload'], $hashKey, $hashIv)
        ) {
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
        $hashKey = (string) config('services.ecpay.hash_key');
        $hashIv = (string) config('services.ecpay.hash_iv');

        $isPaid = false;
        if (
            $hashKey !== ''
            && $hashIv !== ''
            && $normalized['is_check_mac_required']
            && EcpayService::verifyCheckMacValue($normalized['payload'], $hashKey, $hashIv)
        ) {
            $isPaid = $this->applyPaymentResult($normalized['payload']);
        }

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

        if (! $existingPayment) {
            return false;
        }

        $customField = Arr::get($data, 'CustomField');
        $customFieldData = is_string($customField) ? json_decode($customField, true) : [];
        if (! is_array($customFieldData)) {
            $customFieldData = [];
        }

        $callbackUserId = (int) (Arr::get($data, 'CustomField1') ?: Arr::get($customFieldData, 'user_id') ?: 0);
        $callbackPlanId = (int) (Arr::get($data, 'CustomField2') ?: Arr::get($customFieldData, 'plan_id') ?: 0);

        if ($callbackUserId !== 0 && $callbackUserId !== (int) $existingPayment->user_id) {
            return false;
        }

        if ($callbackPlanId !== 0 && $callbackPlanId !== (int) $existingPayment->subscription_plan_id) {
            return false;
        }

        $userId = (int) $existingPayment->user_id;
        $planId = (int) $existingPayment->subscription_plan_id;

        $user = User::query()->find($userId);
        $plan = SubscriptionPlan::query()->find($planId);
        if (! $user || ! $plan) {
            return false;
        }

        $tradeStatus = (string) Arr::get($data, 'OrderInfo.TradeStatus', '');
        $rtnCode = (string) Arr::get($data, 'RtnCode', '');
        $isPaid = $rtnCode === '1' || $tradeStatus === '1';

        $isUpgradeProrationApplied = (bool) Arr::get($existingPayment?->payload ?? [], 'pricing.is_upgrade_proration_applied', false);

        $paidAt = null;
        if ($isPaid) {
            $base = $isUpgradeProrationApplied
                ? now()
                : (
                    $user->hasActiveSubscription()
                        ? Carbon::parse($user->subscription_ends_at)
                        : now()
                );

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

    private function tierRankFromSlug(string $slug): int
    {
        $tier = strtolower((string) strtok($slug, '-'));

        return match ($tier) {
            'basic' => 1,
            'growth' => 2,
            'pro' => 3,
            default => 0,
        };
    }

    private function buildPricingPreview(?User $user, SubscriptionPlan $targetPlan, array $currencyProfile): array
    {
        $planPrice = (int) $targetPlan->price_twd;
        $planDiscount = max((int) ($targetPlan->discount_twd ?? 0), 0);
        $originalPrice = $planPrice + $planDiscount;

        $preview = [
            'original_price_twd' => $originalPrice,
            'upgrade_credit_twd' => 0,
            'payable_amount_twd' => $planPrice,
            'is_upgrade_proration_applied' => false,
            'is_purchase_allowed' => true,
            'blocked_reason' => null,
            'show_reset_time_warning' => false,
            'display_currency' => $currencyProfile['currency_code'],
            'display_symbol' => $currencyProfile['symbol'],
            'display_original_amount' => $this->convertFromTwd($originalPrice, $currencyProfile['currency_code']),
            'display_upgrade_credit' => 0,
            'display_payable_amount' => $this->convertFromTwd($planPrice, $currencyProfile['currency_code']),
        ];

        if (! $user) {
            return $preview;
        }

        $currentPlan = $user->subscriptionPlan;
        $hasActiveSubscription = $user->hasActiveSubscription();
        if (! $hasActiveSubscription || ! $currentPlan) {
            return $preview;
        }

        $currentTierRank = $this->tierRankFromSlug((string) $currentPlan->slug);
        $targetTierRank = $this->tierRankFromSlug((string) $targetPlan->slug);

        if ($targetTierRank < $currentTierRank) {
            $preview['is_purchase_allowed'] = false;
            $preview['blocked_reason'] = __('merchant.error_plan_downgrade_blocked');

            return $preview;
        }

        if ($targetTierRank > $currentTierRank && (int) $targetPlan->duration_days < (int) $currentPlan->duration_days) {
            $preview['is_purchase_allowed'] = false;
            $preview['blocked_reason'] = __('merchant.error_plan_upgrade_cycle_blocked');

            return $preview;
        }

        if ($targetTierRank > $currentTierRank) {
            $remainingSeconds = max(0, now()->diffInSeconds($user->subscription_ends_at, false));
            $remainingDays = (int) ceil($remainingSeconds / 86400);
            $dailyRate = (float) $currentPlan->price_twd / max((int) $currentPlan->duration_days, 1);
            $upgradeCredit = (int) floor($remainingDays * $dailyRate);
            $payableAmount = max(1, $planPrice - $upgradeCredit);

            $preview['upgrade_credit_twd'] = $upgradeCredit;
            $preview['payable_amount_twd'] = $payableAmount;
            $preview['is_upgrade_proration_applied'] = $upgradeCredit > 0;
            $preview['show_reset_time_warning'] = (string) $currentPlan->slug === 'basic-yearly'
                && in_array((string) strtok($targetPlan->slug, '-'), ['growth', 'pro'], true);
        }

        $preview['display_original_amount'] = $this->convertFromTwd($preview['original_price_twd'], $currencyProfile['currency_code']);
        $preview['display_upgrade_credit'] = $this->convertFromTwd($preview['upgrade_credit_twd'], $currencyProfile['currency_code']);
        $preview['display_payable_amount'] = $this->convertFromTwd($preview['payable_amount_twd'], $currencyProfile['currency_code']);

        return $preview;
    }

    private function resolveCurrencyProfile(?User $user): array
    {
        $currencyCode = $user?->subscriptionCurrencyCode() ?? 'twd';

        if (! isset(self::FX_FROM_TWD[$currencyCode])) {
            $currencyCode = 'twd';
        }

        return [
            'currency_code' => $currencyCode,
            'symbol' => self::CURRENCY_SYMBOLS[$currencyCode] ?? 'NT$',
        ];
    }

    private function convertFromTwd(int $amountTwd, string $targetCurrency): int
    {
        $rate = self::FX_FROM_TWD[$targetCurrency] ?? 1.0;

        return max(0, (int) round($amountTwd * $rate));
    }
}
