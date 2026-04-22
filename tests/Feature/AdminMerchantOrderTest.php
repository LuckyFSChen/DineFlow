<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMerchantOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_backend_dine_in_order_for_selected_table(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-order@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Backend Order Store',
            'slug' => 'backend-order-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'A1',
            'qr_token' => 'merchant-a1',
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => '主餐',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => '牛肉麵',
            'price' => 150,
            'is_active' => true,
            'is_sold_out' => false,
            'allow_item_note' => true,
            'option_groups' => [
                [
                    'id' => 'noodle',
                    'name' => '麵體',
                    'type' => 'single',
                    'required' => true,
                    'choices' => [
                        ['id' => 'udon', 'name' => '烏龍麵', 'price' => 20],
                        ['id' => 'thin', 'name' => '細麵', 'price' => 0],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stores.orders.store', $store), [
            'dining_table_id' => $table->id,
            'customer_name' => '王小姐',
            'customer_phone' => '0912-345-678',
            'note' => '先上小菜',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'option_payload' => json_encode([
                        'noodle' => ['udon'],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'item_note' => '不要蔥',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.stores.orders.create', $store));
        $response->assertSessionHas('success');

        $order = Order::query()->with('items')->sole();

        $this->assertSame($store->id, $order->store_id);
        $this->assertSame($table->id, $order->dining_table_id);
        $this->assertSame('dine_in', $order->order_type);
        $this->assertSame('王小姐', $order->customer_name);
        $this->assertSame('0912345678', $order->getRawOriginal('customer_phone'));
        $this->assertSame('先上小菜', $order->note);
        $this->assertSame(340, (int) $order->subtotal);
        $this->assertSame(340, (int) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame(170, (int) $order->items[0]->price);
        $this->assertSame(2, (int) $order->items[0]->qty);
        $this->assertSame(340, (int) $order->items[0]->subtotal);
        $this->assertSame('麵體: 烏龍麵 (+20) | 備註 不要蔥', $order->items[0]->note);
    }

    public function test_backend_dine_in_order_appends_to_existing_unpaid_order_on_same_table(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-append@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Append Store',
            'slug' => 'append-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'B2',
            'qr_token' => 'merchant-b2',
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => '飲品',
            'sort' => 1,
            'is_active' => true,
        ]);

        $existingProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => '紅茶',
            'price' => 40,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $newProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => '奶茶',
            'price' => 55,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'dining_table_id' => $table->id,
            'order_type' => 'dine_in',
            'order_no' => 'APPEND-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_name' => '現場口頭點餐',
            'subtotal' => 40,
            'total' => 40,
        ]);

        $order->items()->create([
            'product_id' => $existingProduct->id,
            'product_name' => $existingProduct->name,
            'price' => 40,
            'qty' => 1,
            'subtotal' => 40,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stores.orders.store', $store), [
            'dining_table_id' => $table->id,
            'customer_name' => '現場客人',
            'items' => [
                [
                    'product_id' => $newProduct->id,
                    'qty' => 2,
                    'option_payload' => '',
                    'item_note' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.stores.orders.create', $store));

        $this->assertSame(1, Order::query()->count());

        $order->refresh();
        $order->load('items');

        $this->assertSame(150, (int) $order->subtotal);
        $this->assertSame(150, (int) $order->total);
        $this->assertCount(2, $order->items);
        $this->assertTrue($order->items->contains(fn ($item) => $item->product_name === '奶茶' && (int) $item->qty === 2));
    }
}
