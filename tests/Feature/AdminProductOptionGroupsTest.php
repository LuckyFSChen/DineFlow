<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProductOptionGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_product_management_page(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-products@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'user_id' => $admin->id,
            'name' => 'Option Store',
            'slug' => 'option-store',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.stores.products.index', $store));

        $response->assertOk();
        $response->assertSee(route('admin.stores.workspace', ['store' => $store, 'tab' => 'boards']), false);
        $response->assertDontSee(route('admin.stores.kitchen', $store), false);
    }

    public function test_admin_can_store_product_with_multiple_choice_option_group(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-products-save@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $store = Store::create([
            'user_id' => $admin->id,
            'name' => 'Combo Store',
            'slug' => 'combo-store',
            'is_active' => true,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Combos',
            'sort' => 1,
            'is_active' => true,
        ]);

        $optionGroups = [
            [
                'name' => 'Main Choice',
                'type' => 'multiple',
                'required' => true,
                'max_select' => 2,
                'choices' => [
                    ['name' => 'Chicken Steak', 'price' => 0],
                    ['name' => 'Pork Chop', 'price' => 0],
                    ['name' => 'Beef Steak', 'price' => 40],
                ],
            ],
        ];

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.stores.products.store', $store), [
                'category_id' => $category->id,
                'name' => 'Double Combo',
                'description' => 'Pick up to two mains in this combo.',
                'price' => 299,
                'cost' => 160,
                'is_active' => '1',
                'is_sold_out' => '0',
                'allow_item_note' => '1',
                'option_groups_json' => json_encode($optionGroups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

        $response->assertRedirect(route('admin.stores.products.index', $store));

        $product = Product::query()
            ->where('store_id', $store->id)
            ->where('name', 'Double Combo')
            ->first();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertTrue((bool) $product->allow_item_note);
        $this->assertSame([
            [
                'id' => 'main_choice',
                'name' => 'Main Choice',
                'type' => 'multiple',
                'required' => true,
                'max_select' => 2,
                'choices' => [
                    ['id' => 'chicken_steak', 'name' => 'Chicken Steak', 'price' => 0],
                    ['id' => 'pork_chop', 'name' => 'Pork Chop', 'price' => 0],
                    ['id' => 'beef_steak', 'name' => 'Beef Steak', 'price' => 40],
                ],
            ],
        ], $product->option_groups);
    }
}
