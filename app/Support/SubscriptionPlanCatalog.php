<?php

namespace App\Support;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Collection;

class SubscriptionPlanCatalog
{
    public function activePlans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->get();
    }

    public function activePlansByTier(): Collection
    {
        return $this->groupByTier($this->activePlans());
    }

    public function groupByTier(Collection $plans): Collection
    {
        $categoryOrder = ['basic' => 1, 'growth' => 2, 'pro' => 3];

        return $plans
            ->sortBy(function (SubscriptionPlan $plan) use ($categoryOrder): array {
                $categoryKey = strtolower(trim((string) ($plan->category ?: strtok($plan->slug, '-'))));

                return [
                    $categoryOrder[$categoryKey] ?? 99,
                    $categoryKey,
                    (int) $plan->duration_days,
                    (int) $plan->price_twd,
                    strtolower((string) $plan->slug),
                ];
            })
            ->groupBy(function (SubscriptionPlan $plan): string {
                $category = trim((string) $plan->category);

                return $category !== '' ? $category : (string) strtok($plan->slug, '-');
            })
            ->map(fn (Collection $tierPlans) => $tierPlans->values());
    }
}
