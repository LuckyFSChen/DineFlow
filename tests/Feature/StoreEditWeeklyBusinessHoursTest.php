<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreEditWeeklyBusinessHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_prefills_weekly_business_hours_from_store_settings(): void
    {
        $merchant = User::factory()->create([
            'role' => 'merchant',
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $store = Store::create([
            'user_id' => $merchant->id,
            'name' => 'Hours Store',
            'slug' => 'hours-store',
            'is_active' => true,
            'weekly_business_hours' => [
                'mon' => ['start' => '09:00', 'end' => '18:00'],
                'sun' => ['start' => '10:30', 'end' => '16:45'],
            ],
        ]);

        $response = $this->actingAs($merchant)->get(route('admin.stores.edit', $store));

        $response->assertOk();
        $response->assertSeeInOrder([
            'name="business_hours[monday][start]"',
            'value="09:00"',
        ], false);
        $response->assertSeeInOrder([
            'name="business_hours[monday][end]"',
            'value="18:00"',
        ], false);
        $response->assertSeeInOrder([
            'name="business_hours[sunday][start]"',
            'value="10:30"',
        ], false);
        $response->assertSeeInOrder([
            'name="business_hours[sunday][end]"',
            'value="16:45"',
        ], false);
    }
}
