<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Support\TakeoutCartSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderReorderToCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_reorder_takeout_items_and_skip_unavailable_products(): void
    {
        $customer = User::create([
            'name' => 'Reorder User',
            'email' => 'reorder@example.com',
            'phone' => '0911222333',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $store = Store::create([
            'name' => 'Takeout Reorder Store',
            'slug' => 'takeout-reorder-store',
            'is_active' => true,
            'takeout_qr_enabled' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Meals',
            'sort' => 1,
            'is_active' => true,
        ]);

        $availableProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Available Meal',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $inactiveProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Inactive Meal',
            'price' => 180,
            'is_active' => false,
            'is_sold_out' => false,
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'REORDER-T-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'subtotal' => 420,
            'total' => 420,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $availableProduct->id,
            'product_name' => $availableProduct->name,
            'price' => 120,
            'qty' => 2,
            'subtotal' => 240,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $inactiveProduct->id,
            'product_name' => $inactiveProduct->name,
            'price' => 180,
            'qty' => 1,
            'subtotal' => 180,
        ]);

        $response = $this->actingAs($customer)->post(route('customer.order.reorder', ['order' => $order]));

        $response->assertRedirect(route('customer.takeout.cart.show', ['store' => $store]));
        $response->assertSessionHas('success');

        $cartKey = TakeoutCartSession::cartSessionKey($store->id, TakeoutCartSession::userToken($customer));
        $cart = session()->get($cartKey, []);

        $this->assertSame(2, (int) collect($cart)->sum('qty'));
        $this->assertTrue(collect($cart)->contains(fn ($item) => (int) ($item['product_id'] ?? 0) === $availableProduct->id));
        $this->assertFalse(collect($cart)->contains(fn ($item) => (int) ($item['product_id'] ?? 0) === $inactiveProduct->id));
    }

    public function test_customer_can_reorder_dinein_items_into_original_table_cart(): void
    {
        $customer = User::create([
            'name' => 'Dinein Reorder User',
            'email' => 'dinein.reorder@example.com',
            'phone' => '0922333444',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $store = Store::create([
            'name' => 'Dinein Reorder Store',
            'slug' => 'dinein-reorder-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'A1',
            'qr_token' => 'reorder-a1',
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
            'name' => 'Dinein Meal',
            'price' => 160,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'dining_table_id' => $table->id,
            'order_type' => 'dine_in',
            'order_no' => 'REORDER-D-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'subtotal' => 320,
            'total' => 320,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 160,
            'qty' => 2,
            'subtotal' => 320,
        ]);

        $response = $this->actingAs($customer)->post(route('customer.order.reorder', ['order' => $order]));

        $response->assertRedirect(route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]));
        $response->assertSessionHas('success');

        $cart = session()->get('dinein_cart.' . $store->id . '.' . $table->id, []);

        $this->assertSame(2, (int) collect($cart)->sum('qty'));
        $this->assertTrue(collect($cart)->contains(fn ($item) => (int) ($item['product_id'] ?? 0) === $product->id));
    }

    public function test_customer_cannot_reorder_another_customers_order(): void
    {
        $owner = User::create([
            'name' => 'Owner Customer',
            'email' => 'owner@example.com',
            'phone' => '0911000111',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $other = User::create([
            'name' => 'Other Customer',
            'email' => 'other@example.com',
            'phone' => '0922000222',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $store = Store::create([
            'name' => 'Auth Reorder Store',
            'slug' => 'auth-reorder-store',
            'is_active' => true,
            'takeout_qr_enabled' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'REORDER-AUTH-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_email' => $owner->email,
            'customer_phone' => $owner->phone,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($other)->post(route('customer.order.reorder', ['order' => $order]));

        $response->assertForbidden();
    }
}

