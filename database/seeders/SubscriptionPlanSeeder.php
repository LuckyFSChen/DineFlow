<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    private function toCharmPrice(float $rawPrice): int
    {
        $price = max((int) round($rawPrice), 99);

        if ($price < 1000) {
            return (int) (floor($price / 10) * 10 + 9);
        }

        return max(99, ((int) floor($price / 100) * 100) - 1);
    }

    public function run(): void
    {
        $tiers = [
            [
                'key' => 'basic',
                'label' => 'Basic',
                'monthly_price' => 999,
                'max_stores' => 1,
                'features' => [
                    '1 店家管理',
                    '菜單與品項管理',
                    '基本報表',
                ],
            ],
            [
                'key' => 'growth',
                'label' => 'Growth',
                'monthly_price' => 1999,
                'max_stores' => 3,
                'features' => [
                    '最多 3 店家',
                    '進階報表',
                    '優先客服',
                ],
            ],
            [
                'key' => 'pro',
                'label' => 'Pro',
                'monthly_price' => 2999,
                'max_stores' => 5,
                'features' => [
                    '最多 5 店家',
                    '全功能後台',
                    '專屬客服',
                ],
            ],
        ];

        $cycles = [
            [
                'key' => 'monthly',
                'label' => 'Monthly',
                'duration_days' => 30,
                'months' => 1,
                'discount_rate' => 1.0,
            ],
            [
                'key' => 'quarterly',
                'label' => 'Quarterly',
                'duration_days' => 90,
                'months' => 3,
                'discount_rate' => 0.95,
            ],
            [
                'key' => 'yearly',
                'label' => 'Yearly',
                'duration_days' => 365,
                'months' => 12,
                'discount_rate' => 0.85,
            ],
        ];

        $plans = [];
        foreach ($tiers as $tier) {
            foreach ($cycles as $cycle) {
                $basePrice = $tier['monthly_price'] * $cycle['months'] * $cycle['discount_rate'];
                $price = $cycle['key'] === 'monthly'
                    ? (int) $tier['monthly_price']
                    : $this->toCharmPrice($basePrice);

                $plans[] = [
                    'name' => $tier['label'] . ' ' . $cycle['label'],
                    'slug' => $tier['key'] . '-' . $cycle['key'],
                    'price_twd' => $price,
                    'duration_days' => $cycle['duration_days'],
                    'max_stores' => $tier['max_stores'],
                    'features' => $tier['features'],
                    'is_active' => true,
                ];
            }
        }

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        SubscriptionPlan::query()
            ->whereNotIn('slug', array_column($plans, 'slug'))
            ->update(['is_active' => false]);
    }
}
