<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DineInCartAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_dine_in_add_to_cart_returns_json_without_redirect_for_ajax_requests(): void
    {
        $store = Store::create([
            'name' => 'Ajax Bistro',
            'slug' => 'ajax-bistro',
            'is_active' => true,
        ]);

        $table = DiningTable::create([
            'store_id' => $store->id,
            'table_no' => 'A1',
            'qr_token' => 'ajax-table-a1',
            'is_active' => true,
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
            'name' => 'Braised Pork Rice',
            'price' => 95,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $response = $this->postJson(route('customer.dinein.cart.items.store', [
            'store' => $store->slug,
            'table' => $table->qr_token,
        ]), [
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', __('customer.item_added_to_cart'))
            ->assertJsonPath('cart.count', 2)
            ->assertJsonPath('cart.total', 190);

        $this->assertSame(2, (int) collect(session()->get('dinein_cart.' . $store->id . '.' . $table->id, []))->sum('qty'));
    }
}
