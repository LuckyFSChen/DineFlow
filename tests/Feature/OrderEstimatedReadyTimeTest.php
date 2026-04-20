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
}
