<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageAccessRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_all_boards_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $store = Store::create([
            'user_id' => $admin->id,
            'name' => 'Regression Cafe',
            'phone' => '0912345678',
            'address' => 'Test Address',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.stores.boards', $store));

        $response->assertOk();
        $response->assertSee('Regression Cafe');
    }

    public function test_admin_can_open_super_admin_subscription_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        SubscriptionPlan::create([
            'name' => 'Basic Monthly',
            'slug' => 'basic-monthly',
            'price_twd' => 999,
            'duration_days' => 30,
            'max_stores' => 1,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('super-admin.subscriptions.index'));

        $response->assertOk();
        $response->assertSee('Basic Monthly');
    }
}
