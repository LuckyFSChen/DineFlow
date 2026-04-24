<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMerchantConsoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_navigation_includes_full_management_links(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $merchant = User::factory()->create([
            'role' => 'merchant',
        ]);

        $store = Store::create([
            'user_id' => $merchant->id,
            'name' => 'Cross Merchant Cafe',
            'phone' => '0912345678',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.stores.index'));

        $response->assertOk();
        $response->assertSee(route('super-admin.subscriptions.index'), false);
        $response->assertSee(route('merchant.reports.financial'), false);
        $response->assertSee(route('merchant.orders.index'), false);
        $response->assertSee(route('merchant.invoices.index'), false);
        $response->assertSee(route('merchant.loyalty.index'), false);
        $response->assertSee(route('admin.stores.workspace', ['store' => $store->slug, 'tab' => 'boards']), false);
    }

    public function test_admin_can_open_merchant_management_pages_for_any_store(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $merchant = User::factory()->create([
            'role' => 'merchant',
        ]);

        $store = Store::create([
            'user_id' => $merchant->id,
            'name' => 'Cross Merchant Cafe',
            'phone' => '0912345678',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('merchant.reports.financial', ['store_id' => $store->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('merchant.orders.index', ['store_id' => $store->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('merchant.invoices.index', ['store_id' => $store->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('merchant.loyalty.index', ['store_id' => $store->id]))
            ->assertOk();
    }
}
