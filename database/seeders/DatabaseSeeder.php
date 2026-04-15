<?php

namespace Database\Seeders;

require_once __DIR__ . '/MultiStoreDemoSeeder.php';

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SubscriptionPlanSeeder::class,
        ]);

        $growthPlan = SubscriptionPlan::query()->where('slug', 'growth-monthly')->first();

        User::updateOrCreate(
            ['email' => 'admin@dineflow.local'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'subscription_ends_at' => now()->addYears(10),
                'subscription_plan_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'merchant@dineflow.local'],
            [
                'name' => 'Merchant Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'merchant',
                'subscription_ends_at' => now()->addMonth(),
                'subscription_plan_id' => $growthPlan?->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@dineflow.local'],
            [
                'name' => 'Customer Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'customer',
                'subscription_ends_at' => null,
                'subscription_plan_id' => null,
            ]
        );

        $this->call([
            MultiStoreDemoSeeder::class,
            FinancialReportDemoSeeder::class,
        ]);
    }
}
