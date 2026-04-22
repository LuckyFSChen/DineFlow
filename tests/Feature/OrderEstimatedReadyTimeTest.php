<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderEstimatedReadyTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_page_uses_station_workload_for_mixed_orders(): void
    {
        $store = Store::create([
            'name' => 'ETA Store',
            'slug' => 'eta-store',
            'is_active' => true,
            'prep_time_minutes' => 20,
        ]);

        $slowCategory = Category::create([
            'store_id' => $store->id,
            'name' => 'Slow Meals',
            'sort' => 1,
            'prep_time_minutes' => 35,
            'is_active' => true,
        ]);

        $fastCategory = Category::create([
            'store_id' => $store->id,
            'name' => 'Fast Drinks',
            'sort' => 2,
            'prep_time_minutes' => 10,
            'is_active' => true,
        ]);

        $slowProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $slowCategory->id,
            'name' => 'Roasted Chicken',
            'price' => 250,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $fastProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $fastCategory->id,
            'name' => 'Black Tea',
            'price' => 40,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'ETA-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 290,
            'total' => 290,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $slowProduct->id,
            'product_name' => $slowProduct->name,
            'price' => 250,
            'qty' => 1,
            'subtotal' => 250,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $fastProduct->id,
            'product_name' => $fastProduct->name,
            'price' => 40,
            'qty' => 1,
            'subtotal' => 40,
        ]);

        $response = $this->get(route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]));

        $response->assertOk();
        $response->assertSee(__('customer.estimated_prep_time_only', ['minutes' => 37]));
    }

    public function test_estimate_prep_time_grows_for_multiple_items_in_same_category(): void
    {
        $store = Store::create([
            'name' => 'Batch Store',
            'slug' => 'batch-store',
            'is_active' => true,
            'prep_time_minutes' => 20,
        ]);

        $slowCategory = Category::create([
            'store_id' => $store->id,
            'name' => 'Slow Meals',
            'sort' => 1,
            'prep_time_minutes' => 35,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $slowCategory->id,
            'name' => 'Roasted Chicken',
            'price' => 250,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $estimatedMinutes = $store->estimatePrepTimeMinutesForOrderItems([
            ['product_id' => $product->id, 'qty' => 3],
        ]);

        $this->assertSame(53, $estimatedMinutes);
    }

    public function test_estimate_prep_time_falls_back_to_store_default_when_no_category_prep_is_set(): void
    {
        $store = Store::create([
            'name' => 'Fallback Store',
            'slug' => 'fallback-store',
            'is_active' => true,
            'prep_time_minutes' => 18,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Regular Meals',
            'sort' => 1,
            'prep_time_minutes' => null,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Noodles',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $estimatedMinutes = $store->estimatePrepTimeMinutesForOrderItems([
            ['product_id' => $product->id, 'qty' => 1],
        ]);

        $this->assertSame(18, $estimatedMinutes);
    }

    public function test_customer_ready_time_adds_queue_delay_from_active_orders_and_item_volume(): void
    {
        $store = Store::create([
            'name' => 'Queue Store',
            'slug' => 'queue-store',
            'is_active' => true,
            'prep_time_minutes' => 20,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Meals',
            'sort' => 1,
            'prep_time_minutes' => 20,
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

        foreach (range(1, 3) as $index) {
            $activeOrder = Order::create([
                'store_id' => $store->id,
                'order_type' => 'takeout',
                'order_no' => 'QUEUE-00'.$index,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => 240,
                'total' => 240,
            ]);

            OrderItem::create([
                'order_id' => $activeOrder->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => 120,
                'qty' => 2,
                'subtotal' => 240,
            ]);
        }

        $estimate = $store->estimateCustomerReadyTimeForOrderItems([
            ['product_id' => $product->id, 'qty' => 1],
        ]);

        $this->assertSame(20, $estimate['base_minutes']);
        $this->assertSame(10, $estimate['queue_delay_minutes']);
        $this->assertSame(30, $estimate['minutes']);
    }

    public function test_customer_ready_time_uses_recent_store_average_prep_speed(): void
    {
        $store = Store::create([
            'name' => 'Speed Store',
            'slug' => 'speed-store',
            'is_active' => true,
            'prep_time_minutes' => 20,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Meals',
            'sort' => 1,
            'prep_time_minutes' => 20,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beef Bowl',
            'price' => 160,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $completedOrder = Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'SPEED-001',
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 160,
            'total' => 160,
        ]);

        $completedOrder->forceFill([
            'created_at' => now()->subMinutes(30),
            'updated_at' => now(),
        ])->saveQuietly();

        OrderItem::create([
            'order_id' => $completedOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 160,
            'qty' => 1,
            'subtotal' => 160,
            'item_status' => 'completed',
            'completed_at' => now(),
        ]);

        $estimate = $store->estimateCustomerReadyTimeForOrderItems([
            ['product_id' => $product->id, 'qty' => 1],
        ]);

        $this->assertSame(20, $estimate['base_minutes']);
        $this->assertSame(30.0, $estimate['average_prep_minutes']);
        $this->assertSame(1.5, $estimate['speed_factor']);
        $this->assertSame(30, $estimate['minutes']);
    }
}
