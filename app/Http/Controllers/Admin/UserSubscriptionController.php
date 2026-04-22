<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionChangeLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
            ->orderBy('price_twd')
            ->get()
            ->sortBy(function (SubscriptionPlan $plan): array {
                $category = trim((string) ($plan->category ?: strtok($plan->slug, '-')));

                return [
                    strtolower($category),
                    strtolower((string) $plan->slug),
                ];
            })
            ->values();

        $assignablePlans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price_twd')
            ->get();

        return view('admin.subscriptions.index', compact('activeTab', 'merchants', 'plans', 'assignablePlans', 'logs'));
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

            return redirect()
                ->route('super-admin.subscriptions.index', ['tab' => 'manage'])
                ->with('success', '已將 '.$user->email.' 的訂閱設為到期。');
        }

        $startsAt = $user->hasActiveSubscription()
            ? $user->subscription_ends_at->copy()
            : now();

        $user->update([
            'subscription_plan_id' => $plan->id,
            'subscription_ends_at' => $startsAt->addDays($plan->duration_days),
        ]);

        return redirect()
            ->route('super-admin.subscriptions.index', ['tab' => 'manage'])
            ->with('success', '已更新 '.$user->email.' 的訂閱方案。');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $supportsCategory = Schema::hasColumn('subscription_plans', 'category');
        $supportsDiscount = Schema::hasColumn('subscription_plans', 'discount_twd');
        $supportsDescription = Schema::hasColumn('subscription_plans', 'description');

        $validated = $request->validate([
            'category' => [$supportsCategory ? 'required' : 'nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'price_twd' => ['required', 'integer', 'min:1'],
            'discount_twd' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $updateData = [
            'name' => trim((string) $validated['name']),
            'price_twd' => (int) $validated['price_twd'],
        ];

        if ($supportsCategory) {
            $updateData['category'] = trim((string) ($validated['category'] ?? ''));
        }

        if ($supportsDiscount) {
            $updateData['discount_twd'] = (int) ($validated['discount_twd'] ?? 0);
        }

        if ($supportsDescription) {
            $updateData['description'] = filled($validated['description'] ?? null)
                ? trim((string) $validated['description'])
                : null;
        }

        $plan->update($updateData);

        return redirect()
            ->route('super-admin.subscriptions.index', ['tab' => 'manage'])
            ->with('success', '已更新方案：'.$plan->name.'。');
    }
}
