<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class StoreOrderingHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_reports_ordering_availability_during_business_hours(): void
    {
        $store = Store::create([
            'name' => 'Breakfast House',
            'slug' => 'breakfast-house',
            'is_active' => true,
            'opening_time' => '09:00',
            'closing_time' => '18:00',
        ]);

        Date::setTestNow('2026-04-13 10:00:00');
        $this->assertTrue($store->fresh()->isOrderingAvailable());

        Date::setTestNow('2026-04-13 19:00:00');
        $this->assertFalse($store->fresh()->isOrderingAvailable());

        Date::setTestNow();
    }

    public function test_takeout_cannot_add_items_outside_business_hours(): void
    {
        Date::setTestNow('2026-04-13 21:00:00');

        $store = Store::create([
            'name' => 'Late Cafe',
            'slug' => 'late-cafe',
            'is_active' => true,
            'opening_time' => '09:00',
            'closing_time' => '18:00',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Drinks',
            'sort' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Black Tea',
            'price' => 40,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $response = $this->post(route('customer.takeout.cart.items.store', ['store' => $store]), [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response->assertRedirect(route('customer.takeout.menu', ['store' => $store]));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
        $this->assertFalse(session()->has('takeout_cart_token_' . $store->id));

        Date::setTestNow();
    }
}
