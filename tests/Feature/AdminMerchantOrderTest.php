<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\MemberPointLedger;
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

    public function test_admin_can_create_backend_takeout_order_without_table(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-takeout-order@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Backend Takeout Store',
            'slug' => 'backend-takeout-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Takeout',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Takeout Bento',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stores.orders.store', $store), [
            'order_type' => 'takeout',
            'customer_name' => 'Pickup Guest',
            'customer_phone' => '0912-345-678',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'option_payload' => '',
                    'item_note' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.stores.orders.create', $store));
        $response->assertSessionHas('success');

        $order = Order::query()->with('items')->sole();

        $this->assertNull($order->dining_table_id);
        $this->assertSame('takeout', $order->order_type);
        $this->assertSame('Pickup Guest', $order->customer_name);
        $this->assertSame('0912345678', $order->getRawOriginal('customer_phone'));
        $this->assertSame(240, (int) $order->subtotal);
        $this->assertSame(240, (int) $order->total);
        $this->assertCount(1, $order->items);
    }

    public function test_backend_prepay_order_marked_collected_goes_directly_to_kitchen(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-prepay-collected@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Prepay Backend Store',
            'slug' => 'prepay-backend-store',
            'is_active' => true,
            'checkout_timing' => 'prepay',
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'P1',
            'qr_token' => 'prepay-p1',
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Prepay',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Paid Meal',
            'price' => 180,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stores.orders.store', $store), [
            'order_type' => 'dine_in',
            'dining_table_id' => $table->id,
            'payment_collected' => '1',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'option_payload' => '',
                    'item_note' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.stores.orders.create', $store));

        $order = Order::query()->sole();

        $this->assertSame('preparing', $order->status);
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_admin_can_lookup_available_coupons_by_customer_phone_for_backend_order(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-coupon-lookup@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Coupon Lookup Store',
            'slug' => 'coupon-lookup-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        Member::create([
            'store_id' => $store->id,
            'name' => 'Alice',
            'phone' => '0912345678',
            'points_balance' => 120,
        ]);

        Coupon::create([
            'store_id' => $store->id,
            'code' => 'SAVE50',
            'name' => 'Save 50',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_order_amount' => 200,
            'points_cost' => 0,
            'reward_per_amount' => 0,
            'reward_points' => 0,
            'usage_limit' => null,
            'used_count' => 0,
            'allow_dine_in' => true,
            'allow_takeout' => false,
            'is_active' => true,
        ]);

        Coupon::create([
            'store_id' => $store->id,
            'code' => 'TAKEOUTONLY',
            'name' => 'Takeout Only',
            'discount_type' => 'fixed',
            'discount_value' => 30,
            'min_order_amount' => 100,
            'points_cost' => 0,
            'reward_per_amount' => 0,
            'reward_points' => 0,
            'usage_limit' => null,
            'used_count' => 0,
            'allow_dine_in' => false,
            'allow_takeout' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.stores.orders.coupons', $store).'?customer_phone=0912-345-678&subtotal=260');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('member.name', 'Alice')
            ->assertJsonPath('member.phone', '0912-345-678')
            ->assertJsonPath('member.points_balance', 120)
            ->assertJsonCount(1, 'coupons')
            ->assertJsonPath('coupons.0.code', 'SAVE50')
            ->assertJsonPath('coupons.0.discount', 50);
    }

    public function test_admin_can_apply_coupon_when_creating_backend_dine_in_order(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-coupon-store@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Coupon Order Store',
            'slug' => 'coupon-order-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'C3',
            'qr_token' => 'merchant-c3',
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Main',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Braised Beef Rice',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $member = Member::create([
            'store_id' => $store->id,
            'name' => 'Alice',
            'phone' => '0912345678',
            'points_balance' => 80,
        ]);

        $coupon = Coupon::create([
            'store_id' => $store->id,
            'code' => 'SAVE30',
            'name' => 'Save 30',
            'discount_type' => 'fixed',
            'discount_value' => 30,
            'min_order_amount' => 200,
            'points_cost' => 0,
            'reward_per_amount' => 0,
            'reward_points' => 0,
            'usage_limit' => null,
            'used_count' => 0,
            'allow_dine_in' => true,
            'allow_takeout' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stores.orders.store', $store), [
            'dining_table_id' => $table->id,
            'customer_name' => 'Alice',
            'customer_phone' => '0912-345-678',
            'coupon_code' => 'save30',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'option_payload' => '',
                    'item_note' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.stores.orders.create', $store));
        $response->assertSessionHas('success');

        $order = Order::query()->with('items')->sole();
        $member->refresh();
        $coupon->refresh();

        $this->assertSame($member->id, $order->member_id);
        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame('SAVE30', $order->coupon_code);
        $this->assertSame(240, (int) $order->subtotal);
        $this->assertSame(30, (int) $order->coupon_discount);
        $this->assertSame(210, (int) $order->total);
        $this->assertSame(0, (int) $order->points_used);
        $this->assertSame(0, (int) $order->points_earned);
        $this->assertSame(1, (int) $coupon->used_count);
        $this->assertSame(80, (int) $member->points_balance);
        $this->assertSame(1, (int) $member->total_orders);
        $this->assertSame(210, (int) $member->total_spent);
        $this->assertCount(1, $order->items);
        $this->assertSame(0, MemberPointLedger::query()->count());
    }

    public function test_admin_can_apply_coupon_with_discount_and_bonus_points(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-coupon-bonus@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Coupon Combo Store',
            'slug' => 'coupon-combo-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'D4',
            'qr_token' => 'merchant-d4',
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
            'name' => 'Chicken Rice',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $member = Member::create([
            'store_id' => $store->id,
            'name' => 'Alice',
            'phone' => '0912345678',
            'points_balance' => 80,
        ]);

        $coupon = Coupon::create([
            'store_id' => $store->id,
            'code' => 'SAVE30PLUS',
            'name' => 'Save 30 Plus',
            'discount_type' => 'fixed',
            'discount_value' => 30,
            'min_order_amount' => 200,
            'points_cost' => 0,
            'reward_per_amount' => 100,
            'reward_points' => 5,
            'usage_limit' => null,
            'used_count' => 0,
            'allow_dine_in' => true,
            'allow_takeout' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stores.orders.store', $store), [
            'dining_table_id' => $table->id,
            'customer_name' => 'Alice',
            'customer_phone' => '0912-345-678',
            'coupon_code' => 'save30plus',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'option_payload' => '',
                    'item_note' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.stores.orders.create', $store));
        $response->assertSessionHas('success');

        $order = Order::query()->sole();
        $member->refresh();
        $coupon->refresh();

        $this->assertSame(240, (int) $order->subtotal);
        $this->assertSame(30, (int) $order->coupon_discount);
        $this->assertSame(210, (int) $order->total);
        $this->assertSame(10, (int) $order->points_earned);
        $this->assertSame(90, (int) $member->points_balance);
        $this->assertSame(210, (int) $member->total_spent);
        $this->assertSame(1, MemberPointLedger::query()->count());
        $this->assertSame(1, (int) $coupon->used_count);
    }

    public function test_admin_order_with_customer_phone_creates_customer_account_and_member_without_switching_session(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-customer-register@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'name' => 'Auto Member Store',
            'slug' => 'auto-member-store',
            'is_active' => true,
            'opening_time' => '00:00',
            'closing_time' => '23:59',
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'E5',
            'qr_token' => 'merchant-e5',
            'status' => 'available',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Main',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Pork Chop Rice',
            'price' => 150,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.stores.orders.store', $store), [
            'dining_table_id' => $table->id,
            'customer_name' => 'Walk-in Guest',
            'customer_phone' => '0912-345-678',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'option_payload' => '',
                    'item_note' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.stores.orders.create', $store));
        $response->assertSessionHas('success');

        $customer = User::query()
            ->where('phone', '0912345678')
            ->where('role', 'customer')
            ->first();

        $member = Member::query()
            ->where('store_id', $store->id)
            ->where('phone', '0912345678')
            ->first();

        $order = Order::query()->sole();

        $this->assertNotNull($customer);
        $this->assertNotNull($member);
        $this->assertTrue($customer->isCustomer());
        $this->assertTrue((bool) $customer->must_change_password);
        $this->assertSame('Walk-in Guest', $customer->name);
        $this->assertSame($member->id, $order->member_id);
        $this->assertAuthenticatedAs($admin);
    }
}
