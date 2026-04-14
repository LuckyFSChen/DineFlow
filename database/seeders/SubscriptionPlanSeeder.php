<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Monthly',
                'slug' => 'basic-monthly',
                'price_twd' => 999,
                'duration_days' => 30,
                'max_stores' => 1,
                'features' => [
                    '1 店家管理',
                    '菜單與品項管理',
                    '基本報表',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Growth Monthly',
                'slug' => 'growth-monthly',
                'price_twd' => 1999,
                'duration_days' => 30,
                'max_stores' => 3,
                'features' => [
                    '最多 3 店家',
                    '進階報表',
                    '優先客服',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro Yearly',
                'slug' => 'pro-yearly',
                'price_twd' => 19999,
                'duration_days' => 365,
                'max_stores' => null,
                'features' => [
                    '不限店家數',
                    '全功能後台',
                    '專屬客服',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
