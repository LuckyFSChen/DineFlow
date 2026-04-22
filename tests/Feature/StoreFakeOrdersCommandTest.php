<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreFakeOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_fake_orders_for_the_target_store_only(): void
    {
        $target = $this->createStoreFixture('alpha-store', 'Alpha Store');
        $other = $this->createStoreFixture('beta-store', 'Beta Store');

        $this->artisan('stores:fake-orders', [
            'store' => $target['store']->slug,
            '--count' => 6,
            '--days' => 3,
        ])
            ->expectsOutputToContain('Fake orders generated successfully.')
            ->expectsOutputToContain('created_orders: 6')
            ->assertSuccessful();

        $targetOrders = Order::query()
            ->with('items')
            ->where('store_id', $target['store']->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(6, $targetOrders);
        $this->assertSame(0, Order::query()->where('store_id', $other['store']->id)->count());
        $this->assertTrue($targetOrders->every(fn (Order $order) => filled($order->uuid) && filled($order->order_no)));
        $this->assertTrue($targetOrders->every(fn (Order $order) => $order->items->isNotEmpty()));
        $this->assertTrue($targetOrders->contains(fn (Order $order) => $order->order_type === 'dine_in'));
        $this->assertTrue($targetOrders->contains(fn (Order $order) => $order->order_type === 'takeout'));
        $this->assertGreaterThan(0, OrderItem::query()->count());
    }

    public function test_clear_option_replaces_existing_orders_for_the_target_store_only(): void
    {
        $target = $this->createStoreFixture('clear-target-store', 'Clear Target Store');
        $other = $this->createStoreFixture('clear-other-store', 'Clear Other Store');

        $oldTargetOrder = Order::create([
            'store_id' => $target['store']->id,
            'dining_table_id' => $target['table']->id,
            'order_type' => 'dine_in',
            'order_no' => 'OLD-TARGET-ORDER',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_name' => 'Legacy Target Customer',
            'subtotal' => 100,
            'total' => 100,
        ]);

        $oldTargetOrder->items()->create([
            'product_id' => $target['products'][0]->id,
            'product_name' => $target['products'][0]->name,
            'price' => 100,
            'qty' => 1,
            'subtotal' => 100,
        ]);

        $otherOrder = Order::create([
            'store_id' => $other['store']->id,
            'dining_table_id' => $other['table']->id,
            'order_type' => 'dine_in',
            'order_no' => 'KEEP-OTHER-ORDER',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_name' => 'Keep Other Customer',
            'subtotal' => 120,
            'total' => 120,
        ]);

        $otherOrder->items()->create([
            'product_id' => $other['products'][0]->id,
            'product_name' => $other['products'][0]->name,
            'price' => 120,
            'qty' => 1,
            'subtotal' => 120,
        ]);

        $this->artisan('stores:fake-orders', [
            'store' => $target['store']->slug,
            '--count' => 4,
            '--clear' => true,
        ])
            ->expectsOutputToContain('created_orders: 4')
            ->expectsOutputToContain('cleared_orders: 1')
            ->assertSuccessful();

        $this->assertSame(4, Order::query()->where('store_id', $target['store']->id)->count());
        $this->assertDatabaseMissing('orders', [
            'id' => $oldTargetOrder->id,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $otherOrder->id,
            'store_id' => $other['store']->id,
        ]);
    }

    private function createStoreFixture(string $slug, string $name): array
    {
        $store = Store::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'checkout_timing' => 'postpay',
            'opening_time' => '00:00',
            'closing_time' => '23:59',
            'country_code' => 'tw',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'A1',
            'qr_token' => 'qr-' . $slug,
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Main',
            'sort' => 1,
            'is_active' => true,
        ]);

        $products = collect([
            Product::create([
                'store_id' => $store->id,
                'category_id' => $category->id,
                'name' => $name . ' Product 1',
                'price' => 100,
                'is_active' => true,
                'is_sold_out' => false,
                'allow_item_note' => true,
            ]),
            Product::create([
                'store_id' => $store->id,
                'category_id' => $category->id,
                'name' => $name . ' Product 2',
                'price' => 140,
                'is_active' => true,
                'is_sold_out' => false,
                'allow_item_note' => false,
            ]),
            Product::create([
                'store_id' => $store->id,
                'category_id' => $category->id,
                'name' => $name . ' Product 3',
                'price' => 180,
                'is_active' => true,
                'is_sold_out' => false,
                'allow_item_note' => true,
            ]),
        ]);

        return [
            'store' => $store,
            'table' => $table,
            'category' => $category,
            'products' => $products,
        ];
    }
}
