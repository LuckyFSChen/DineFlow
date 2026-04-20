<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderAutoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_dinein_checkout_auto_logs_in_newly_registered_customer(): void
    {
        [$store, $product] = $this->createStoreAndProduct([
            'name' => 'Dine In Auto Login Store',
            'slug' => 'dinein-auto-login-store',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'A1',
            'qr_token' => 'auto-login-a1',
            'status' => 'available',
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
            'customer_name' => 'Dine In Guest',
            'customer_email' => 'dinein@example.com',
            'customer_phone' => '0912345678',
        ]);

        $user = User::query()->where('phone', '0912345678')->first();
        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($order);
        $this->assertTrue($user->isCustomer());
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]));
    }

    public function test_takeout_checkout_auto_logs_in_newly_registered_customer(): void
    {
        [$store, $product] = $this->createStoreAndProduct([
            'name' => 'Takeout Auto Login Store',
            'slug' => 'takeout-auto-login-store',
            'takeout_qr_enabled' => true,
        ]);

        $this->post(route('customer.takeout.cart.items.store', [
            'store' => $store->slug,
        ]), [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertStatus(302);

        $response = $this->post(route('customer.takeout.cart.checkout', [
            'store' => $store->slug,
        ]), [
            'customer_name' => 'Takeout Guest',
            'customer_email' => 'takeout@example.com',
            'customer_phone' => '0987654321',
            'create_account_with_phone' => '1',
        ]);

        $user = User::query()->where('phone', '0987654321')->first();
        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($order);
        $this->assertTrue($user->isCustomer());
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]));
    }

    /**
     * @return array{0: Store, 1: Product}
     */
    private function createStoreAndProduct(array $storeOverrides = []): array
    {
        $store = Store::create(array_merge([
            'name' => 'Auto Login Store',
            'slug' => 'auto-login-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ], $storeOverrides));

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Meals',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Chicken Rice',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        return [$store, $product];
    }
}
