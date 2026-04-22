<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreFakeMenuCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_fake_menu_for_a_store_using_the_store_slug(): void
    {
        $merchant = User::factory()->create([
            'role' => 'merchant',
        ]);

        $store = Store::query()->create([
            'user_id' => $merchant->id,
            'name' => '測試店家',
            'slug' => 'demo-store',
            'is_active' => true,
        ]);

        $this->artisan('stores:fake-menu', [
            'store' => 'demo-store',
        ])
            ->expectsOutputToContain('Fake menu seeded successfully.')
            ->assertSuccessful();

        $this->assertSame(5, Category::query()->where('store_id', $store->id)->count());
        $this->assertSame(20, Product::query()->where('store_id', $store->id)->count());

        $product = Product::query()
            ->where('store_id', $store->id)
            ->where('name', '炙燒雞腿飯')
            ->first();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertTrue((bool) $product->allow_item_note);
        $this->assertIsArray($product->option_groups);
        $this->assertSame(185, (int) $product->price);

        $this->artisan('stores:fake-menu', [
            'store' => 'demo-store',
        ])->assertSuccessful();

        $this->assertSame(5, Category::query()->where('store_id', $store->id)->count());
        $this->assertSame(20, Product::query()->where('store_id', $store->id)->count());
    }

    public function test_replace_option_clears_existing_menu_before_seeding_fake_data(): void
    {
        $merchant = User::factory()->create([
            'role' => 'merchant',
            'email' => 'merchant@example.com',
        ]);

        $store = Store::query()->create([
            'user_id' => $merchant->id,
            'name' => '舊店家',
            'slug' => 'legacy-store',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'store_id' => $store->id,
            'name' => '舊分類',
            'sort' => 1,
            'is_active' => true,
        ]);

        Product::query()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => '舊商品',
            'price' => 99,
            'cost' => 40,
            'sort' => 1,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $this->artisan('stores:fake-menu', [
            'store' => 'merchant@example.com',
            '--replace' => true,
        ])->assertSuccessful();

        $this->assertFalse(Category::query()->where('store_id', $store->id)->where('name', '舊分類')->exists());
        $this->assertFalse(Product::query()->where('store_id', $store->id)->where('name', '舊商品')->exists());
        $this->assertSame(20, Product::query()->where('store_id', $store->id)->count());
        $this->assertSame(1, Product::withTrashed()->where('store_id', $store->id)->where('name', '舊商品')->count());
    }
}
