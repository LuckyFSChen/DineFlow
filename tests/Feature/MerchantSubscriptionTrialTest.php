<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MerchantSubscriptionTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_start_a_seven_day_trial(): void
    {
        $merchant = User::create([
            'name' => 'Trial Merchant',
            'email' => 'trial-merchant@example.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Basic Monthly',
            'slug' => 'basic-monthly',
            'price_twd' => 999,
            'duration_days' => 30,
            'max_stores' => 1,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($merchant)
            ->post(route('merchant.subscription.trial'));

        $response->assertRedirect(route('merchant.subscription.index'));

        $merchant->refresh();

        $this->assertSame($plan->id, $merchant->subscription_plan_id);
        $this->assertNotNull($merchant->trial_started_at);
        $this->assertNotNull($merchant->trial_ends_at);
        $this->assertNotNull($merchant->trial_used_at);
        $this->assertTrue($merchant->hasActiveSubscription());
        $this->assertSame(7, $merchant->trial_started_at->diffInDays($merchant->trial_ends_at));
        $this->assertSame(7, now()->diffInDays($merchant->subscription_ends_at));
    }
}
