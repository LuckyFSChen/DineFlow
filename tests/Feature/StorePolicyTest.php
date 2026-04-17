<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StorePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_cannot_edit_another_merchants_store(): void
    {
        $owner = User::create([
            'name' => 'Owner Merchant',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $intruder = User::create([
            'name' => 'Intruder Merchant',
            'email' => 'intruder@example.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $store = Store::create([
            'user_id' => $owner->id,
            'name' => 'Owner Store',
            'slug' => 'owner-store',
            'is_active' => true,
        ]);

        $response = $this->actingAs($intruder)->get(route('admin.stores.edit', $store));

        $response->assertForbidden();
    }

    public function test_cashier_can_access_assigned_store_cashier_board_but_not_others(): void
    {
        $owner = User::create([
            'name' => 'Owner Merchant',
            'email' => 'merchant@example.com',
            'password' => Hash::make('password'),
            'role' => 'merchant',
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $assignedStore = Store::create([
            'user_id' => $owner->id,
            'name' => 'Assigned Store',
            'slug' => 'assigned-store',
            'is_active' => true,
        ]);

        $otherStore = Store::create([
            'user_id' => $owner->id,
            'name' => 'Other Store',
            'slug' => 'other-store',
            'is_active' => true,
        ]);

        $cashier = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'store_id' => $assignedStore->id,
        ]);

        $this->actingAs($cashier)
            ->get(route('admin.stores.cashier', $assignedStore))
            ->assertOk();

        $this->actingAs($cashier)
            ->get(route('admin.stores.cashier', $otherStore))
            ->assertForbidden();
    }
}
