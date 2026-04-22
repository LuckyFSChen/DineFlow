<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAccountDeletionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_account_deletion_soft_deletes_user_and_detaches_identifiers(): void
    {
        $customer = User::create([
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $store = Store::create([
            'name' => 'Deleted Data Store',
            'slug' => 'deleted-data-store',
            'is_active' => true,
        ]);

        $member = Member::create([
            'store_id' => $store->id,
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'points_balance' => 77,
            'total_spent' => 500,
            'total_orders' => 2,
            'last_order_at' => now()->subDay(),
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'OLD-ORDER-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_name' => 'Customer One',
            'customer_email' => 'customer.one@example.com',
            'customer_phone' => '0911222333',
            'subtotal' => 150,
            'total' => 150,
        ]);

        $response = $this->actingAs($customer)->delete('/profile', [
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $deletedCustomer = User::withTrashed()->find($customer->id);

        $this->assertGuest();
        $this->assertNotNull($deletedCustomer);
        $this->assertNotNull($deletedCustomer->deleted_at);
        $this->assertNotSame('customer.one@example.com', $deletedCustomer->getRawOriginal('email'));
        $this->assertNotSame('0911222333', $deletedCustomer->getRawOriginal('phone'));

        $member->refresh();
        $order->refresh();

        $this->assertNull($member->name);
        $this->assertNull($member->getRawOriginal('email'));
        $this->assertNull($member->getRawOriginal('phone'));
        $this->assertNull($order->getRawOriginal('customer_email'));
        $this->assertNull($order->getRawOriginal('customer_phone'));
    }

    public function test_re_registered_customer_does_not_receive_deleted_account_history_or_points(): void
    {
        $customer = User::create([
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $store = Store::create([
            'name' => 'Deleted Data Store',
            'slug' => 'deleted-data-store',
            'is_active' => true,
        ]);

        Member::create([
            'store_id' => $store->id,
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
            'phone' => '0911222333',
            'points_balance' => 77,
            'total_spent' => 500,
            'total_orders' => 2,
            'last_order_at' => now()->subDay(),
        ]);

        Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'OLD-ORDER-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_name' => 'Customer One',
            'customer_email' => 'customer.one@example.com',
            'customer_phone' => '0911222333',
            'subtotal' => 150,
            'total' => 150,
        ]);

        $this->actingAs($customer)->delete('/profile', [
            'password' => 'password',
        ]);

        $registerResponse = $this->post('/register', [
            'name' => 'Customer Recreated',
            'phone' => '0911222333',
            'email' => 'customer.one@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
            'account_type' => 'customer',
        ]);

        $newCustomer = User::query()->where('phone', '0911222333')->first();

        $registerResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertNotNull($newCustomer);
        $this->assertNotSame($customer->id, $newCustomer->id);
        $this->assertAuthenticatedAs($newCustomer);

        $historyResponse = $this->get(route('customer.order.history'));
        $historyResponse->assertOk();
        $historyResponse->assertDontSee('OLD-ORDER-001');
        $historyResponse->assertDontSee('Deleted Data Store');

        $pointsResponse = $this->get(route('customer.points.index'));
        $pointsResponse->assertOk();
        $pointsResponse->assertDontSee('Deleted Data Store');
    }
}
