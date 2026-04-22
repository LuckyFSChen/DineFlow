<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NavFeature;
use App\Models\SubscriptionChangeLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = in_array($request->query('tab'), ['manage', 'logs', 'features'], true)
            ? $request->query('tab')
            : 'manage';

        $today = now()->startOfDay();
        $merchantBaseQuery = User::query()->where('role', 'merchant');

        $merchants = (clone $merchantBaseQuery)
            ->with('subscriptionPlan')
            ->withCount([
                'stores',
                'stores as active_stores_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderByRaw('CASE WHEN subscription_ends_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('subscription_ends_at')
            ->orderBy('id')
            ->paginate(15, ['*'], 'merchants_page')
            ->withQueryString();

        $logs = SubscriptionChangeLog::query()
            ->with(['adminUser', 'merchantUser', 'oldPlan', 'newPlan'])
            ->latest()
            ->paginate(20, ['*'], 'logs_page')
            ->withQueryString();

        $plans = SubscriptionPlan::query()
            ->withCount('merchantUsers')
            ->orderBy('price_twd')
            ->get()
            ->sortBy(function (SubscriptionPlan $plan): array {
                $category = trim((string) ($plan->category ?: strtok($plan->slug, '-')));

                return [
                    $plan->is_active ? 0 : 1,
                    strtolower($category),
                    (int) $plan->price_twd,
                    strtolower((string) $plan->slug),
                ];
            })
            ->values();

        $assignablePlans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price_twd')
            ->get();

        $planFieldSupport = [
            'category' => Schema::hasColumn('subscription_plans', 'category'),
            'discount' => Schema::hasColumn('subscription_plans', 'discount_twd'),
            'description' => Schema::hasColumn('subscription_plans', 'description'),
        ];

        $planSummary = [
            'total_count' => $plans->count(),
            'active_count' => $plans->where('is_active', true)->count(),
            'discounted_count' => $plans->filter(fn (SubscriptionPlan $plan) => (int) ($plan->discount_twd ?? 0) > 0)->count(),
            'assigned_count' => $plans->sum(fn (SubscriptionPlan $plan) => (int) ($plan->merchant_users_count ?? 0)),
        ];

        $merchantSummary = [
            'total_count' => (clone $merchantBaseQuery)->count(),
            'active_count' => (clone $merchantBaseQuery)
                ->whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>=', $today)
                ->count(),
            'inactive_count' => (clone $merchantBaseQuery)
                ->where(function ($query) use ($today) {
                    $query->whereNull('subscription_ends_at')
                        ->orWhere('subscription_ends_at', '<', $today);
                })
                ->count(),
            'expiring_soon_count' => (clone $merchantBaseQuery)
                ->whereNotNull('subscription_ends_at')
                ->whereBetween('subscription_ends_at', [$today, now()->copy()->addDays(7)->endOfDay()])
                ->count(),
        ];

        $logSummary = [
            'total_count' => $logs->total(),
            'latest_changed_at' => $logs->first()?->created_at,
        ];

        $navFeatureDefinitions = NavFeature::definitions();
        $navFeatureStates = NavFeature::all();
        $navFeatureConfigurations = NavFeature::configurations();
        $navFeatureSummary = [
            'total_count' => count($navFeatureDefinitions),
            'enabled_count' => collect($navFeatureStates)->filter()->count(),
            'disabled_count' => collect($navFeatureStates)->reject()->count(),
        ];

        return view('admin.subscriptions.index', compact(
            'activeTab',
            'assignablePlans',
            'logSummary',
            'logs',
            'merchantSummary',
            'merchants',
            'navFeatureDefinitions',
            'navFeatureStates',
            'navFeatureConfigurations',
            'navFeatureSummary',
            'planFieldSupport',
            'planSummary',
            'plans',
        ));
    }

    public function updateNavFeatures(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (array_keys(NavFeature::definitions()) as $featureKey) {
            $rules['features.' . $featureKey] = ['nullable', 'boolean'];
            $rules['placements.' . $featureKey] = ['required', 'in:' . implode(',', NavFeature::placements())];
            $rules['orders.' . $featureKey] = ['required', 'integer', 'min:1', 'max:999'];
        }

        $validated = $request->validate($rules);
        $submittedFeatures = (array) ($validated['features'] ?? []);
        $submittedPlacements = (array) ($validated['placements'] ?? []);
        $submittedOrders = (array) ($validated['orders'] ?? []);

        NavFeature::update($submittedFeatures, $submittedPlacements, $submittedOrders);

        return redirect()
            ->route('super-admin.subscriptions.index', ['tab' => 'features'])
            ->with('success', __('admin.nav_features_updated'));
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $supportsCategory = Schema::hasColumn('subscription_plans', 'category');
        $supportsDiscount = Schema::hasColumn('subscription_plans', 'discount_twd');
        $supportsDescription = Schema::hasColumn('subscription_plans', 'description');

        $validated = $request->validate(
            $this->planValidationRules($supportsCategory, $supportsDiscount, $supportsDescription)
        );

        $plan = SubscriptionPlan::query()->create(
            $this->buildPlanPayload($validated, $supportsCategory, $supportsDiscount, $supportsDescription)
        );

        return redirect()
            ->route('super-admin.subscriptions.index', ['tab' => 'manage'])
            ->with('success', __('admin.subscription_plan_created', ['name' => $plan->name]));
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
                ->with('success', __('admin.subscription_assignment_expired', ['email' => $user->email]));
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
            ->with('success', __('admin.subscription_assignment_updated', ['email' => $user->email]));
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $supportsCategory = Schema::hasColumn('subscription_plans', 'category');
        $supportsDiscount = Schema::hasColumn('subscription_plans', 'discount_twd');
        $supportsDescription = Schema::hasColumn('subscription_plans', 'description');

        $validated = $request->validate(
            $this->planValidationRules($supportsCategory, $supportsDiscount, $supportsDescription, $plan)
        );

        $plan->update(
            $this->buildPlanPayload($validated, $supportsCategory, $supportsDiscount, $supportsDescription, $plan)
        );

        return redirect()
            ->route('super-admin.subscriptions.index', ['tab' => 'manage'])
            ->with('success', __('admin.subscription_plan_updated', ['name' => $plan->name]));
    }

    public function destroyPlan(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->merchantUsers()->exists()) {
            return redirect()
                ->route('super-admin.subscriptions.index', ['tab' => 'manage'])
                ->with('error', __('admin.subscription_plan_delete_blocked', ['name' => $plan->name]));
        }

        $deletedName = $plan->name;
        $plan->delete();

        return redirect()
            ->route('super-admin.subscriptions.index', ['tab' => 'manage'])
            ->with('success', __('admin.subscription_plan_deleted', ['name' => $deletedName]));
    }

    private function planValidationRules(
        bool $supportsCategory,
        bool $supportsDiscount,
        bool $supportsDescription,
        ?SubscriptionPlan $plan = null
    ): array {
        return [
            'plan_form_mode' => ['nullable', 'string', 'in:create,update'],
            'editing_plan_id' => ['nullable', 'integer'],
            'category' => [$supportsCategory ? 'required' : 'nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'price_twd' => ['required', 'integer', 'min:1'],
            'discount_twd' => [$supportsDiscount ? 'nullable' : 'prohibited', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'max_stores' => ['nullable', 'integer', 'min:1', 'max:999'],
            'description' => [$supportsDescription ? 'nullable' : 'prohibited', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function buildPlanPayload(
        array $validated,
        bool $supportsCategory,
        bool $supportsDiscount,
        bool $supportsDescription,
        ?SubscriptionPlan $plan = null
    ): array {
        $payload = [
            'name' => trim((string) $validated['name']),
            'slug' => $this->resolvePlanSlug($validated, $plan),
            'price_twd' => (int) $validated['price_twd'],
            'duration_days' => (int) $validated['duration_days'],
            'max_stores' => filled($validated['max_stores'] ?? null)
                ? (int) $validated['max_stores']
                : null,
            'is_active' => (bool) $validated['is_active'],
        ];

        if ($supportsCategory) {
            $payload['category'] = trim((string) ($validated['category'] ?? ''));
        }

        if ($supportsDiscount) {
            $payload['discount_twd'] = (int) ($validated['discount_twd'] ?? 0);
        }

        if ($supportsDescription) {
            $payload['description'] = filled($validated['description'] ?? null)
                ? trim((string) $validated['description'])
                : null;
        }

        return $payload;
    }

    private function resolvePlanSlug(array $validated, ?SubscriptionPlan $plan = null): string
    {
        if ($plan instanceof SubscriptionPlan && $this->planIdentityIsUnchanged($validated, $plan)) {
            $existingSlug = trim((string) $plan->slug);

            if ($existingSlug !== '') {
                return $existingSlug;
            }
        }

        $categorySlug = Str::slug(trim((string) ($validated['category'] ?? '')));
        $nameSlug = Str::slug(trim((string) ($validated['name'] ?? '')));
        $baseSlug = $this->buildGeneratedPlanSlug($categorySlug, $nameSlug);

        if ($baseSlug === '' && $plan instanceof SubscriptionPlan) {
            $baseSlug = trim((string) $plan->slug);
        }

        if ($baseSlug === '') {
            $baseSlug = 'plan';
        }

        return $this->ensureUniquePlanSlug($baseSlug, $plan?->id);
    }

    private function buildGeneratedPlanSlug(string $categorySlug, string $nameSlug): string
    {
        if ($nameSlug !== '' && $categorySlug !== '') {
            if ($nameSlug === $categorySlug || str_starts_with($nameSlug, $categorySlug.'-')) {
                return $nameSlug;
            }

            return $categorySlug.'-'.$nameSlug;
        }

        return $nameSlug !== '' ? $nameSlug : $categorySlug;
    }

    private function planIdentityIsUnchanged(array $validated, SubscriptionPlan $plan): bool
    {
        $incomingName = trim((string) ($validated['name'] ?? ''));
        $currentName = trim((string) $plan->name);

        if ($incomingName !== $currentName) {
            return false;
        }

        $incomingCategory = strtolower(trim((string) ($validated['category'] ?? '')));
        $currentCategory = strtolower(trim((string) ($plan->category ?? '')));

        return $incomingCategory === $currentCategory;
    }

    private function ensureUniquePlanSlug(string $baseSlug, ?int $ignorePlanId = null): string
    {
        $slug = Str::limit($baseSlug, 140, '');
        $suffix = 2;

        while (
            SubscriptionPlan::query()
                ->when($ignorePlanId !== null, fn ($query) => $query->where('id', '!=', $ignorePlanId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $suffixText = '-'.$suffix;
            $slug = Str::limit($baseSlug, max(1, 140 - strlen($suffixText)), '').$suffixText;
            $suffix++;
        }

        return $slug;
    }
}
