<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class StoreOrderingHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_reports_ordering_availability_during_business_hours(): void
    {
        $store = Store::create([
            'name' => 'Breakfast House',
            'slug' => 'breakfast-house',
            'is_active' => true,
            'opening_time' => '09:00',
            'closing_time' => '18:00',
        ]);

        Date::setTestNow('2026-04-13 10:00:00');
        $this->assertTrue($store->fresh()->isOrderingAvailable());

        Date::setTestNow('2026-04-13 19:00:00');
        $this->assertFalse($store->fresh()->isOrderingAvailable());

        Date::setTestNow();
    }

    public function test_takeout_cannot_add_items_outside_business_hours(): void
    {
        Date::setTestNow('2026-04-13 21:00:00');

        $store = Store::create([
            'name' => 'Late Cafe',
            'slug' => 'late-cafe',
            'is_active' => true,
            'opening_time' => '09:00',
            'closing_time' => '18:00',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Drinks',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Black Tea',
            'price' => 40,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $response = $this->post(route('customer.takeout.cart.items.store', ['store' => $store]), [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response->assertRedirect(route('customer.takeout.menu', ['store' => $store]));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
        $this->assertFalse(session()->has('takeout_cart_token_' . $store->id));

        Date::setTestNow();
    }

    public function test_store_is_unavailable_during_weekday_break_hours(): void
    {
        $store = Store::create([
            'name' => 'Lunch Break Cafe',
            'slug' => 'lunch-break-cafe',
            'is_active' => true,
            'opening_time' => '09:00',
            'closing_time' => '18:00',
            'weekly_break_hours' => [
                'mon' => [
                    'start' => '12:00',
                    'end' => '13:00',
                ],
            ],
        ]);

        Date::setTestNow('2026-04-13 11:30:00');
        $this->assertTrue($store->fresh()->isOrderingAvailable());

        Date::setTestNow('2026-04-13 12:30:00');
        $this->assertFalse($store->fresh()->isOrderingAvailable());

        Date::setTestNow('2026-04-13 13:30:00');
        $this->assertTrue($store->fresh()->isOrderingAvailable());

        Date::setTestNow();
    }

    public function test_dinein_checkout_does_not_require_customer_info(): void
    {
        $store = Store::create([
            'name' => 'Dine In Free Form Store',
            'slug' => 'dine-in-free-form-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'A1',
            'qr_token' => 'token-a1',
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Meals',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Fried Rice',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $this->post(route('customer.dinein.cart.items.store', [
            'store' => $store->slug,
            'table' => $table->qr_token,
        ]), [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertStatus(302);

        $response = $this->post(route('customer.dinein.cart.checkout', [
            'store' => $store->slug,
            'table' => $table->qr_token,
        ]), [
            'note' => 'No contact details needed',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('dine_in', $order->order_type);
        $this->assertNull($order->customer_name);
        $this->assertNull($order->customer_email);
        $this->assertNull($order->customer_phone);
    }

    public function test_dinein_checkout_appends_to_completed_unpaid_order_for_same_table(): void
    {
        $store = Store::create([
            'name' => 'Postpay Merge Store',
            'slug' => 'postpay-merge-store',
            'is_active' => true,
            'checkout_timing' => 'postpay',
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'B2',
            'qr_token' => 'token-b2',
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Combo',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Set Meal',
            'price' => 150,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $existingOrder = Order::create([
            'store_id' => $store->id,
            'dining_table_id' => $table->id,
            'order_type' => 'dine_in',
            'order_no' => 'MERGE-001',
            'status' => 'completed',
            'payment_status' => 'unpaid',
            'subtotal' => 0,
            'total' => 0,
        ]);

        $this->post(route('customer.dinein.cart.items.store', [
            'store' => $store->slug,
            'table' => $table->qr_token,
        ]), [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertStatus(302);

        $response = $this->post(route('customer.dinein.cart.checkout', [
            'store' => $store->slug,
            'table' => $table->qr_token,
        ]), []);

        $response->assertStatus(302);
        $response->assertRedirect(route('customer.order.success', [
            'store' => $store->slug,
            'order' => $existingOrder->uuid,
        ]));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $existingOrder->id,
            'product_id' => $product->id,
            'qty' => 1,
            'subtotal' => 150,
        ]);

        $existingOrder->refresh();
        $this->assertSame('preparing', $existingOrder->status);
        $this->assertSame('unpaid', $existingOrder->payment_status);
        $this->assertSame(150, (int) $existingOrder->subtotal);
        $this->assertSame(150, (int) $existingOrder->total);
    }
}
