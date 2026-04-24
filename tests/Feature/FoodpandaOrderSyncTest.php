<?php

namespace Tests\Feature;

use App\Models\FoodpandaWebhookEvent;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FoodpandaOrderSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_foodpanda_webhook_creates_a_local_order(): void
    {
        $store = $this->makeFoodpandaStore([
            'foodpanda_store_id' => 'fp-store-001',
            'foodpanda_external_partner_config_id' => 'dineflow-store-001',
            'foodpanda_webhook_secret' => 'Basic fp-secret',
        ]);

        $response = $this->postJson(
            route('webhooks.foodpanda.orders'),
            $this->foodpandaPayload([
                'status' => 'RECEIVED',
                'client' => [
                    'chain_id' => 'chain-001',
                    'store_id' => 'fp-store-001',
                    'external_partner_config_id' => 'dineflow-store-001',
                ],
            ]),
            ['Authorization' => 'Basic fp-secret']
        );

        $response->assertOk();

        $order = Order::query()->with('items')->sole();

        $this->assertSame($store->id, $order->store_id);
        $this->assertSame('foodpanda', $order->source_platform);
        $this->assertSame('order-1001', $order->source_order_id);
        $this->assertSame('pending', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('FP-1001', $order->source_display_id);
        $this->assertSame('Foodpanda', $order->customer_name);
        $this->assertSame(300, (int) $order->subtotal);
        $this->assertSame(325, (int) $order->total);
        $this->assertStringContainsString('Foodpanda #FP-1001', (string) $order->note);
        $this->assertCount(1, $order->items);
        $this->assertSame('Braised Beef Rice', $order->items[0]->product_name);
        $this->assertSame(150, (int) $order->items[0]->price);
        $this->assertSame(2, (int) $order->items[0]->qty);
        $this->assertSame(300, (int) $order->items[0]->subtotal);
        $this->assertSame(1, FoodpandaWebhookEvent::query()->count());
    }

    public function test_foodpanda_received_webhook_does_not_duplicate_or_downgrade_a_preparing_order(): void
    {
        $store = $this->makeFoodpandaStore([
            'foodpanda_store_id' => 'fp-store-002',
            'foodpanda_external_partner_config_id' => 'dineflow-store-002',
            'foodpanda_webhook_secret' => 'Basic fp-secret-2',
        ]);

        $payload = $this->foodpandaPayload([
            'order_id' => 'order-2001',
            'order_code' => 'FP-2001',
            'status' => 'RECEIVED',
            'client' => [
                'chain_id' => 'chain-001',
                'store_id' => 'fp-store-002',
                'external_partner_config_id' => 'dineflow-store-002',
            ],
            'sys' => [
                'created_at' => '2026-04-24T08:00:00Z',
                'updated_at' => '2026-04-24T08:00:00Z',
            ],
        ]);

        $this->postJson(route('webhooks.foodpanda.orders'), $payload, [
            'Authorization' => 'Basic fp-secret-2',
        ])->assertOk();

        $order = Order::query()->sole();
        $order->update(['status' => 'preparing']);

        $payload['sys']['updated_at'] = '2026-04-24T08:05:00Z';

        $this->postJson(route('webhooks.foodpanda.orders'), $payload, [
            'Authorization' => 'Basic fp-secret-2',
        ])->assertOk();

        $order->refresh();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame('preparing', $order->status);
        $this->assertSame(2, FoodpandaWebhookEvent::query()->count());
    }

    public function test_foodpanda_webhook_does_not_use_global_webhook_secret_fallback(): void
    {
        config()->set('services.foodpanda.webhook_secret', 'Basic global-secret');

        $store = $this->makeFoodpandaStore([
            'foodpanda_store_id' => 'fp-store-003',
            'foodpanda_external_partner_config_id' => 'dineflow-store-003',
            'foodpanda_webhook_secret' => null,
        ]);

        $response = $this->postJson(
            route('webhooks.foodpanda.orders'),
            $this->foodpandaPayload([
                'order_id' => 'order-3000',
                'order_code' => 'FP-3000',
                'client' => [
                    'chain_id' => 'chain-001',
                    'store_id' => 'fp-store-003',
                    'external_partner_config_id' => 'dineflow-store-003',
                ],
            ]),
            ['Authorization' => 'Basic global-secret']
        );

        $response->assertOk();
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(1, FoodpandaWebhookEvent::query()->count());
        $this->assertSame($store->id, FoodpandaWebhookEvent::query()->value('local_store_id'));
        $this->assertSame('ignored', FoodpandaWebhookEvent::query()->value('status'));
    }

    public function test_store_cannot_enable_foodpanda_without_store_level_credentials(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.stores.create'))
            ->post(route('admin.stores.store'), [
                'name' => 'Missing Foodpanda Credentials Store',
                'foodpanda_enabled' => '1',
            ]);

        $response
            ->assertRedirect(route('admin.stores.create'))
            ->assertSessionHasErrors([
                'foodpanda_chain_id',
                'foodpanda_store_id',
                'foodpanda_external_partner_config_id',
                'foodpanda_client_id',
                'foodpanda_client_secret',
                'foodpanda_webhook_secret',
            ]);
    }

    public function test_cashier_cancellation_of_foodpanda_order_updates_foodpanda_and_local_order(): void
    {
        Cache::flush();
        Http::preventStrayRequests();

        config()->set('services.foodpanda.api_base_url', 'https://foodpanda.test');
        config()->set('services.foodpanda.auth_url', 'https://foodpanda.test/v2/oauth/token');

        Http::fake([
            'https://foodpanda.test/v2/oauth/token' => Http::response([
                'access_token' => 'fp-access-token',
                'expires_in' => 7200,
            ], 200),
            'https://foodpanda.test/v2/chains/chain-002/orders/order-3001' => Http::response([
                'order_id' => 'order-3001',
                'status' => 'CANCELLED',
            ], 200),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $store = $this->makeFoodpandaStore([
            'foodpanda_chain_id' => 'chain-002',
            'foodpanda_client_id' => 'client-002',
            'foodpanda_client_secret' => 'secret-002',
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'dining_table_id' => null,
            'order_type' => 'takeout',
            'order_no' => 'FP-CANCEL-3001',
            'status' => 'pending',
            'payment_status' => 'paid',
            'source_platform' => 'foodpanda',
            'source_order_id' => 'order-3001',
            'source_payload' => $this->foodpandaPayload([
                'order_id' => 'order-3001',
                'order_code' => 'FP-3001',
                'status' => 'RECEIVED',
            ]),
            'subtotal' => 300,
            'total' => 325,
        ]);

        $order->items()->create([
            'product_id' => null,
            'product_name' => 'Braised Beef Rice',
            'price' => 150,
            'qty' => 2,
            'subtotal' => 300,
            'note' => 'SKU: SKU-1001',
        ]);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.stores.cashier.orders.status', [$store, $order]),
            [
                'status' => 'cancelled',
                'cancel_reason_options' => ['ITEM_UNAVAILABLE'],
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $order->refresh();

        $this->assertSame('cancelled', $order->status);

        Http::assertSent(function (HttpRequest $request): bool {
            $data = $request->data();

            return $request->url() === 'https://foodpanda.test/v2/oauth/token'
                && $request->method() === 'POST'
                && ($data['client_id'] ?? null) === 'client-002'
                && ($data['client_secret'] ?? null) === 'secret-002'
                && ($data['grant_type'] ?? null) === 'client_credentials';
        });

        Http::assertSent(function (HttpRequest $request): bool {
            $data = $request->data();

            return $request->url() === 'https://foodpanda.test/v2/chains/chain-002/orders/order-3001'
                && $request->method() === 'PUT'
                && ($data['status'] ?? null) === 'CANCELLED'
                && (($data['cancellation']['reason'] ?? null) === 'ITEM_UNAVAILABLE')
                && count($data['items'] ?? []) === 1;
        });
    }

    public function test_kitchen_last_item_completion_marks_foodpanda_order_ready_for_pickup(): void
    {
        Cache::flush();
        Http::preventStrayRequests();

        config()->set('services.foodpanda.api_base_url', 'https://foodpanda.test');
        config()->set('services.foodpanda.auth_url', 'https://foodpanda.test/v2/oauth/token');

        Http::fake([
            'https://foodpanda.test/v2/oauth/token' => Http::response([
                'access_token' => 'fp-access-token',
                'expires_in' => 7200,
            ], 200),
            'https://foodpanda.test/v2/chains/chain-003/orders/order-4001' => Http::response([
                'order_id' => 'order-4001',
                'status' => 'READY_FOR_PICKUP',
            ], 200),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $store = $this->makeFoodpandaStore([
            'foodpanda_chain_id' => 'chain-003',
            'foodpanda_client_id' => 'client-003',
            'foodpanda_client_secret' => 'secret-003',
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'dining_table_id' => null,
            'order_type' => 'takeout',
            'order_no' => 'FP-KITCHEN-4001',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'source_platform' => 'foodpanda',
            'source_order_id' => 'order-4001',
            'source_payload' => $this->foodpandaPayload([
                'order_id' => 'order-4001',
                'order_code' => 'FP-4001',
                'status' => 'RECEIVED',
                'transport_type' => 'LOGISTICS_DELIVERY',
            ]),
            'subtotal' => 300,
            'total' => 325,
        ]);

        $item = $order->items()->create([
            'product_id' => null,
            'product_name' => 'Braised Beef Rice',
            'price' => 150,
            'qty' => 2,
            'subtotal' => 300,
            'note' => 'SKU: SKU-1001',
            'item_status' => 'preparing',
        ]);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.stores.kitchen.orders.status', [$store, $order]),
            [
                'item_id' => $item->id,
                'item_status' => 'completed',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $order->refresh();
        $item->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertSame('completed', $item->item_status);

        Http::assertSent(function (HttpRequest $request): bool {
            $data = $request->data();

            return $request->url() === 'https://foodpanda.test/v2/chains/chain-003/orders/order-4001'
                && $request->method() === 'PUT'
                && ($data['status'] ?? null) === 'READY_FOR_PICKUP';
        });
    }

    public function test_kitchen_whole_order_completion_marks_vendor_delivery_foodpanda_order_as_dispatched(): void
    {
        Cache::flush();
        Http::preventStrayRequests();

        config()->set('services.foodpanda.api_base_url', 'https://foodpanda.test');
        config()->set('services.foodpanda.auth_url', 'https://foodpanda.test/v2/oauth/token');

        Http::fake([
            'https://foodpanda.test/v2/oauth/token' => Http::response([
                'access_token' => 'fp-access-token',
                'expires_in' => 7200,
            ], 200),
            'https://foodpanda.test/v2/chains/chain-004/orders/order-5001' => Http::response([
                'order_id' => 'order-5001',
                'status' => 'DISPATCHED',
            ], 200),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $store = $this->makeFoodpandaStore([
            'foodpanda_chain_id' => 'chain-004',
            'foodpanda_client_id' => 'client-004',
            'foodpanda_client_secret' => 'secret-004',
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'dining_table_id' => null,
            'order_type' => 'takeout',
            'order_no' => 'FP-KITCHEN-5001',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'source_platform' => 'foodpanda',
            'source_order_id' => 'order-5001',
            'source_payload' => $this->foodpandaPayload([
                'order_id' => 'order-5001',
                'order_code' => 'FP-5001',
                'status' => 'RECEIVED',
                'transport_type' => 'VENDOR_DELIVERY',
            ]),
            'subtotal' => 300,
            'total' => 325,
        ]);

        $order->items()->create([
            'product_id' => null,
            'product_name' => 'Braised Beef Rice',
            'price' => 150,
            'qty' => 2,
            'subtotal' => 300,
            'note' => 'SKU: SKU-1001',
            'item_status' => 'preparing',
        ]);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.stores.kitchen.orders.status', [$store, $order]),
            [
                'status' => 'completed',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $order->refresh();

        $this->assertSame('completed', $order->status);

        Http::assertSent(function (HttpRequest $request): bool {
            $data = $request->data();

            return $request->url() === 'https://foodpanda.test/v2/chains/chain-004/orders/order-5001'
                && $request->method() === 'PUT'
                && ($data['status'] ?? null) === 'DISPATCHED';
        });
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeFoodpandaStore(array $overrides = []): Store
    {
        return Store::create(array_merge([
            'name' => 'Foodpanda Store',
            'slug' => 'foodpanda-store-'.fake()->unique()->slug(),
            'is_active' => true,
            'foodpanda_enabled' => true,
            'foodpanda_chain_id' => 'chain-001',
            'foodpanda_store_id' => 'fp-store',
            'foodpanda_external_partner_config_id' => 'dineflow-store',
            'foodpanda_client_id' => 'client-id',
            'foodpanda_client_secret' => 'client-secret',
            'foodpanda_webhook_secret' => 'Basic fp-secret',
        ], $overrides));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function foodpandaPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'order_id' => 'order-1001',
            'external_order_id' => 'external-1001',
            'order_code' => 'FP-1001',
            'status' => 'RECEIVED',
            'comment' => 'No onions',
            'transport_type' => 'LOGISTICS_DELIVERY',
            'client' => [
                'chain_id' => 'chain-001',
                'store_id' => 'fp-store',
                'external_partner_config_id' => 'dineflow-store',
            ],
            'customer' => [
                'first_name' => '',
                'last_name' => '',
                'phone_number' => '',
                'delivery_address' => [
                    'instructions' => 'Leave at the door',
                ],
            ],
            'payment' => [
                'sub_total' => 300,
                'order_total' => 325,
            ],
            'items' => [
                [
                    '_id' => 'item-1001',
                    'name' => 'Braised Beef Rice',
                    'sku' => 'SKU-1001',
                    'instructions' => 'Less spicy',
                    'pricing' => [
                        'pricing_type' => 'UNIT',
                        'quantity' => 2,
                        'unit_price' => 150,
                        'total_price' => 300,
                        'weight' => 0,
                    ],
                    'status' => 'IN_CART',
                ],
            ],
            'sys' => [
                'created_at' => '2026-04-24T08:00:00Z',
                'updated_at' => '2026-04-24T08:00:00Z',
            ],
        ], $overrides);
    }
}
