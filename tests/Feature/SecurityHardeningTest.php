<?php

namespace Tests\Feature;

use App\Http\Controllers\Customer\DineInOrderController;
use App\Models\Order;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
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

    public function test_order_history_requires_email_and_phone_together(): void
    {
        Session::start();

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
            'customer_email' => 'guest@example.com',
            'customer_phone' => '0912345678',
            'subtotal' => 100,
            'total' => 100,
        ]);

        $request = Request::create(route('customer.order.history', ['store' => $store]), 'GET', [
            'customer_email' => 'guest@example.com',
        ]);
        $request->setLaravelSession(Session::driver());

        $response = app(DineInOrderController::class)->history($request, $store);

        $this->assertInstanceOf(View::class, $response);
        $this->assertTrue($response->getData()['requiresBothIdentifiers']);
        $this->assertCount(0, $response->getData()['orders']);
    }
}
