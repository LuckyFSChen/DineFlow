<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_subscription_plan(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('super-admin.subscriptions.plans.store'), [
                'plan_form_mode' => 'create',
                'category' => 'growth',
                'name' => 'Growth Quarterly',
                'slug' => 'manually-forced-slug',
                'price_twd' => 2699,
                'discount_twd' => 300,
                'duration_days' => 90,
                'max_stores' => 3,
                'description' => 'For growing teams.',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'manage']));

        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'growth-quarterly',
            'name' => 'Growth Quarterly',
            'duration_days' => 90,
            'max_stores' => 3,
            'is_active' => 1,
        ]);
    }

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
                'plan_form_mode' => 'update',
                'editing_plan_id' => $plan->id,
                'category' => 'pro',
                'name' => 'Pro Annual',
                'slug' => 'manually-forced-slug',
                'price_twd' => 7999,
                'discount_twd' => 500,
                'duration_days' => 365,
                'max_stores' => 10,
                'description' => 'For large operators.',
                'is_active' => '0',
            ]);

        $response->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'manage']));

        $plan->refresh();

        $this->assertSame('pro', $plan->category);
        $this->assertSame('Pro Annual', $plan->name);
        $this->assertSame('pro-annual', $plan->slug);
        $this->assertSame(7999, $plan->price_twd);
        $this->assertSame(500, $plan->discount_twd);
        $this->assertSame(365, $plan->duration_days);
        $this->assertSame(10, $plan->max_stores);
        $this->assertSame('For large operators.', $plan->description);
        $this->assertFalse($plan->is_active);
    }

    public function test_admin_keeps_existing_slug_when_plan_identity_is_unchanged(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Legacy Growth Plan',
            'slug' => 'legacy-growth-key',
            'category' => 'growth',
            'price_twd' => 1999,
            'discount_twd' => 0,
            'duration_days' => 30,
            'max_stores' => 2,
            'description' => null,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('super-admin.subscriptions.plans.update', $plan), [
                'plan_form_mode' => 'update',
                'editing_plan_id' => $plan->id,
                'category' => 'growth',
                'name' => 'Legacy Growth Plan',
                'slug' => 'manually-forced-slug',
                'price_twd' => 2599,
                'discount_twd' => 200,
                'duration_days' => 30,
                'max_stores' => 4,
                'description' => 'Updated commercial terms.',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'manage']));

        $plan->refresh();

        $this->assertSame('legacy-growth-key', $plan->slug);
        $this->assertSame(2599, $plan->price_twd);
        $this->assertSame(200, $plan->discount_twd);
        $this->assertSame(4, $plan->max_stores);
        $this->assertSame('Updated commercial terms.', $plan->description);
    }

    public function test_admin_can_delete_unused_subscription_plan(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Seasonal Plan',
            'slug' => 'seasonal-plan',
            'category' => 'basic',
            'price_twd' => 399,
            'discount_twd' => 0,
            'duration_days' => 14,
            'max_stores' => 1,
            'description' => null,
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('super-admin.subscriptions.plans.destroy', $plan));

        $response->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'manage']));
        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    }

    public function test_admin_cannot_delete_plan_that_is_still_assigned(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Assigned Plan',
            'slug' => 'assigned-plan',
            'category' => 'growth',
            'price_twd' => 1299,
            'discount_twd' => 0,
            'duration_days' => 30,
            'max_stores' => 2,
            'description' => null,
            'is_active' => true,
        ]);

        User::factory()->create([
            'role' => 'merchant',
            'subscription_plan_id' => $plan->id,
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('super-admin.subscriptions.plans.destroy', $plan));

        $response->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'manage']));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }
}
