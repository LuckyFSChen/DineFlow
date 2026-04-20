<?php

namespace Tests\Feature;

use App\Models\Member;
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

    public function test_guest_cannot_access_points_card_page(): void
    {
        $response = $this->get(route('customer.points.index'));

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

    public function test_order_history_shows_only_authenticated_customer_store_point_balances(): void
    {
        $customer = User::create([
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $storeA = Store::create([
            'name' => 'Point Store A',
            'slug' => 'point-store-a',
            'is_active' => true,
        ]);

        $storeB = Store::create([
            'name' => 'Point Store B',
            'slug' => 'point-store-b',
            'is_active' => true,
        ]);

        $otherStore = Store::create([
            'name' => 'Other Customer Store',
            'slug' => 'other-customer-store',
            'is_active' => true,
        ]);

        Member::create([
            'store_id' => $storeA->id,
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'points_balance' => 35,
            'total_spent' => 1200,
            'total_orders' => 4,
            'last_order_at' => now()->subDay(),
        ]);

        Member::create([
            'store_id' => $storeB->id,
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0000000000',
            'points_balance' => 12,
            'total_spent' => 500,
            'total_orders' => 2,
            'last_order_at' => now()->subDays(2),
        ]);

        Member::create([
            'store_id' => $otherStore->id,
            'name' => 'Other Customer',
            'email' => 'other@example.com',
            'phone' => '0999888777',
            'points_balance' => 999,
            'total_spent' => 9999,
            'total_orders' => 9,
            'last_order_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get(route('customer.order.history'));

        $response->assertOk();
        $response->assertSee('Point Store A');
        $response->assertSee('Point Store B');
        $response->assertSee('35');
        $response->assertSee('12');
        $response->assertDontSee('Other Customer Store');
        $response->assertDontSee('999');
    }

    public function test_points_card_page_shows_only_authenticated_customer_store_point_balances(): void
    {
        $customer = User::create([
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $storeA = Store::create([
            'name' => 'Card Store A',
            'slug' => 'card-store-a',
            'is_active' => true,
        ]);

        $storeB = Store::create([
            'name' => 'Card Store B',
            'slug' => 'card-store-b',
            'is_active' => true,
        ]);

        $otherStore = Store::create([
            'name' => 'Other Card Store',
            'slug' => 'other-card-store',
            'is_active' => true,
        ]);

        Member::create([
            'store_id' => $storeA->id,
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'points_balance' => 88,
            'total_spent' => 2400,
            'total_orders' => 6,
            'last_order_at' => now()->subHours(6),
        ]);

        Member::create([
            'store_id' => $storeB->id,
            'name' => 'Customer One',
            'email' => 'someone-else@example.com',
            'phone' => '0911222333',
            'points_balance' => 21,
            'total_spent' => 900,
            'total_orders' => 3,
            'last_order_at' => now()->subDays(3),
        ]);

        Member::create([
            'store_id' => $otherStore->id,
            'name' => 'Other Customer',
            'email' => 'other@example.com',
            'phone' => '0999888777',
            'points_balance' => 555,
            'total_spent' => 5555,
            'total_orders' => 5,
            'last_order_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get(route('customer.points.index'));

        $response->assertOk();
        $response->assertSee('Card Store A');
        $response->assertSee('Card Store B');
        $response->assertSee('88');
        $response->assertSee('21');
        $response->assertDontSee('Other Card Store');
        $response->assertDontSee('555');
    }

    public function test_points_card_page_hides_inactive_stores(): void
    {
        $customer = User::create([
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $activeStore = Store::create([
            'name' => 'Visible Card Store',
            'slug' => 'visible-card-store',
            'is_active' => true,
        ]);

        $inactiveStore = Store::create([
            'name' => 'Hidden Card Store',
            'slug' => 'hidden-card-store',
            'is_active' => false,
        ]);

        Member::create([
            'store_id' => $activeStore->id,
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'points_balance' => 30,
            'total_spent' => 800,
            'total_orders' => 2,
            'last_order_at' => now()->subDay(),
        ]);

        Member::create([
            'store_id' => $inactiveStore->id,
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'points_balance' => 99,
            'total_spent' => 1600,
            'total_orders' => 5,
            'last_order_at' => now()->subHours(3),
        ]);

        $response = $this->actingAs($customer)->get(route('customer.points.index'));

        $response->assertOk();
        $response->assertSee('Visible Card Store');
        $response->assertDontSee('Hidden Card Store');
        $response->assertDontSee('99');
    }
}
