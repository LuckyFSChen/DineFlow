<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_notify_requires_valid_checkmac(): void
    {
        config()->set('services.ecpay.hash_key', '5294y06JbISpM5x9');
        config()->set('services.ecpay.hash_iv', 'v77hoKGq4kWxNNIS');

        $merchant = User::create([
            'name' => 'Merchant Owner',
            'email' => 'merchant@example.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
            'subscription_ends_at' => null,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Growth Monthly',
            'slug' => 'growth-monthly',
            'price_twd' => 999,
            'duration_days' => 30,
            'max_stores' => 3,
            'is_active' => true,
        ]);

        SubscriptionPayment::create([
            'user_id' => $merchant->id,
            'subscription_plan_id' => $plan->id,
            'ecpay_merchant_trade_no' => 'DFTEST123456',
            'amount_twd' => 999,
            'currency' => 'twd',
            'status' => 'pending',
        ]);

        $response = $this->post(route('ecpay.subscription.notify'), [
            'MerchantTradeNo' => 'DFTEST123456',
            'RtnCode' => '1',
            'CustomField1' => (string) $merchant->id,
            'CustomField2' => (string) $plan->id,
        ]);

        $response->assertStatus(400);
        $response->assertSeeText('0|CHECKMAC_INVALID');
        $this->assertNull($merchant->fresh()->subscription_ends_at);
        $this->assertSame('pending', SubscriptionPayment::query()->first()->status);
    }

    public function test_store_is_unavailable_when_owner_subscription_has_expired(): void
    {
        $merchant = User::create([
            'name' => 'Expired Merchant',
            'email' => 'expired@example.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
            'subscription_ends_at' => now()->subDay(),
        ]);

        $store = Store::create([
            'user_id' => $merchant->id,
            'name' => 'Expired Merchant Store',
            'slug' => 'expired-merchant-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $this->assertFalse($store->fresh()->isOrderingAvailable());
    }

    public function test_guest_cannot_access_order_history(): void
    {
        $response = $this->get(route('customer.order.history'));

        $response->assertRedirect(route('login'));
    }

    public function test_order_history_only_shows_authenticated_customer_orders(): void
    {
        $customer = User::create([
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $otherCustomer = User::create([
            'name' => 'Customer Two',
            'email' => 'customer.two@example.com',
            'phone' => '0999888777',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $store = Store::create([
            'name' => 'Lookup Safe Store',
            'slug' => 'lookup-safe-store',
            'is_active' => true,
        ]);

        Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'SAFE-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_email' => 'customer.one@example.com',
            'customer_phone' => '0222222222',
            'subtotal' => 100,
            'total' => 100,
        ]);

        Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'SAFE-002',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_email' => 'someone@example.com',
            'customer_phone' => '0911222333',
            'subtotal' => 100,
            'total' => 100,
        ]);

        Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'SAFE-003',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_email' => $otherCustomer->email,
            'customer_phone' => $otherCustomer->phone,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($customer)->get(route('customer.order.history'));

        $response->assertOk();
        $response->assertSee('SAFE-001');
        $response->assertSee('SAFE-002');
        $response->assertDontSee('SAFE-003');
    }
}
