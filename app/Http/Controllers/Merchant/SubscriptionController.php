<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $startsAt = $user->subscription_ends_at && $user->subscription_ends_at->isFuture()
            ? $user->subscription_ends_at->copy()
            : now();

        $user->update([
            'subscription_plan_id' => $plan->id,
            'subscription_ends_at' => $startsAt->addDays($plan->duration_days),
        ]);

        return redirect()
            ->route('merchant.subscription.index')
            ->with('success', '已啟用 ' . $plan->name . '，可使用商家後台。');
    }
}
