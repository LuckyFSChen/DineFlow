<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Stripe;

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

    public function subscribe(Request $request): RedirectResponse
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

        $stripeKey = (string) config('services.stripe.secret');
        if ($stripeKey === '') {
            return redirect()
                ->route('merchant.subscription.index')
                ->with('error', 'Stripe 金流尚未設定，請先設定 STRIPE_SECRET。');
        }

        [$interval, $intervalCount] = $this->stripeRecurringCycle((int) $plan->duration_days);

        Stripe::setApiKey($stripeKey);

        $lineItem = [
            'quantity' => 1,
        ];

        if (! empty($plan->stripe_price_id)) {
            $lineItem['price'] = $plan->stripe_price_id;
        } else {
            $lineItem['price_data'] = [
                'currency' => 'twd',
                'unit_amount' => (int) $plan->price_twd,
                'product_data' => [
                    'name' => 'DineFlow - ' . $plan->name,
                    'description' => '商家訂閱方案',
                ],
                'recurring' => [
                    'interval' => $interval,
                    'interval_count' => $intervalCount,
                ],
            ];
        }

        $session = Session::create([
            'mode' => 'subscription',
            'success_url' => route('merchant.subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('merchant.subscription.index'),
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
            ],
            'line_items' => [$lineItem],
        ]);

        return redirect()->away($session->url);
    }

    public function success(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return redirect()
                ->route('merchant.subscription.index')
                ->with('error', '找不到 Stripe 結帳資訊。');
        }

        return redirect()
            ->route('merchant.subscription.index')
            ->with('success', '付款完成，系統正在同步你的訂閱狀態。');
    }

    private function stripeRecurringCycle(int $durationDays): array
    {
        if ($durationDays >= 365) {
            return ['year', max((int) round($durationDays / 365), 1)];
        }

        return ['month', max((int) round($durationDays / 30), 1)];
    }
}
