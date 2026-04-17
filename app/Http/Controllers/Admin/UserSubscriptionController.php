<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionChangeLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = in_array($request->query('tab'), ['manage', 'logs'], true)
            ? $request->query('tab')
            : 'manage';

        $merchants = User::query()
            ->where('role', 'merchant')
            ->with('subscriptionPlan')
            ->orderBy('id')
            ->paginate(15, ['*'], 'merchants_page')
            ->withQueryString();

        $logs = SubscriptionChangeLog::query()
            ->with(['adminUser', 'merchantUser', 'oldPlan', 'newPlan'])
            ->latest()
            ->paginate(20, ['*'], 'logs_page')
            ->withQueryString();

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price_twd')
            ->get();

        return view('admin.subscriptions.index', compact('activeTab', 'merchants', 'plans', 'logs'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->isMerchant()) {
            abort(422, __('admin.error_only_merchant_account_updatable'));
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'action' => ['required', 'in:activate,expire'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('id', $validated['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ($validated['action'] === 'expire') {
            $user->update([
                'subscription_plan_id' => $plan->id,
                'subscription_ends_at' => now()->subSecond(),
            ]);

            return redirect()->route('super-admin.subscriptions.index')
                ->with('success', '已設定為到期：' . $user->email);
        }

        $startsAt = $user->hasActiveSubscription()
            ? $user->subscription_ends_at->copy()
            : now();

        $user->update([
            'subscription_plan_id' => $plan->id,
            'subscription_ends_at' => $startsAt->addDays($plan->duration_days),
        ]);

        return redirect()->route('super-admin.subscriptions.index')
            ->with('success', '已更新訂閱：' . $user->email);
    }
}
