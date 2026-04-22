<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\NavFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavFeatureToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_nav_feature_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('super-admin.subscriptions.features.update'), [
                'features' => [
                    NavFeature::SUBSCRIPTION => '1',
                    NavFeature::FINANCIAL_REPORT => '1',
                    NavFeature::ORDER_HISTORY => '0',
                    NavFeature::INVOICE_CENTER => '0',
                    NavFeature::LOYALTY => '1',
                    NavFeature::STORE_BACKEND => '1',
                    NavFeature::BOARDS => '0',
                ],
            ]);

        $response->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'features']));
        $response->assertSessionHas('success');

        $this->assertTrue(NavFeature::enabled(NavFeature::SUBSCRIPTION));
        $this->assertFalse(NavFeature::enabled(NavFeature::ORDER_HISTORY));
        $this->assertFalse(NavFeature::enabled(NavFeature::INVOICE_CENTER));
        $this->assertFalse(NavFeature::enabled(NavFeature::BOARDS));
    }

    public function test_disabled_feature_is_hidden_from_merchant_navigation(): void
    {
        $merchant = User::factory()->create([
            'role' => 'merchant',
        ]);

        NavFeature::update([
            NavFeature::SUBSCRIPTION => true,
            NavFeature::FINANCIAL_REPORT => true,
            NavFeature::ORDER_HISTORY => true,
            NavFeature::INVOICE_CENTER => false,
            NavFeature::LOYALTY => true,
            NavFeature::STORE_BACKEND => true,
            NavFeature::BOARDS => true,
        ]);

        $response = $this
            ->actingAs($merchant)
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertDontSee(route('merchant.invoices.index'), false);
        $response->assertSee(route('merchant.orders.index'), false);
    }

    public function test_disabled_feature_blocks_direct_route_access(): void
    {
        $merchant = User::factory()->create([
            'role' => 'merchant',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        NavFeature::update([
            NavFeature::SUBSCRIPTION => true,
            NavFeature::FINANCIAL_REPORT => true,
            NavFeature::ORDER_HISTORY => false,
            NavFeature::INVOICE_CENTER => true,
            NavFeature::LOYALTY => true,
            NavFeature::STORE_BACKEND => true,
            NavFeature::BOARDS => true,
        ]);

        $this->actingAs($merchant)
            ->get(route('merchant.orders.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($admin)
            ->get(route('merchant.orders.index'))
            ->assertRedirect(route('super-admin.subscriptions.index', ['tab' => 'features']));
    }
}
