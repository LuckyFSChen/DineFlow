<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_subscription_plan_metadata(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Basic Monthly',
            'slug' => 'basic-monthly',
            'category' => 'basic',
            'price_twd' => 999,
            'discount_twd' => 0,
            'duration_days' => 30,
            'max_stores' => 1,
            'description' => null,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('super-admin.subscriptions.plans.update', $plan), [
                'category' => '人氣方案',
                'name' => '超值月繳',
                'price_twd' => 899,
                'discount_twd' => 100,
                'description' => '適合剛起步的商家。',
            ]);

        $response->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'manage']));

        $plan->refresh();

        $this->assertSame('人氣方案', $plan->category);
        $this->assertSame('超值月繳', $plan->name);
        $this->assertSame(899, $plan->price_twd);
        $this->assertSame(100, $plan->discount_twd);
        $this->assertSame('適合剛起步的商家。', $plan->description);
    }
}
