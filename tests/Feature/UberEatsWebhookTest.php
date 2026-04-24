<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UberEatsWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.uber_eats.api_base_url' => 'https://api.uber.test',
            'services.uber_eats.auth_url' => 'https://auth.uber.test/oauth/token',
            'services.uber_eats.scopes' => 'eats.order eats.store.orders.read',
            'services.uber_eats.timeout' => 15,
        ]);

        Cache::flush();
    }

    public function test_created_order_webhook_creates_local_takeout_order_and_accepts_it(): void
    {
        $store = Store::create([
            'name' => 'Uber Store',
            'slug' => 'uber-store',
            'is_active' => true,
            'uber_eats_enabled' => true,
            'uber_eats_store_id' => 'uber-store-1',
            'uber_eats_client_id' => 'store-client-id-1',
            'uber_eats_client_secret' => 'store-client-secret-1',
            'prep_time_minutes' => 20,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Meals',
            'sort' => 1,
            'prep_time_minutes' => 20,
            'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Fried Rice',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://auth.uber.test/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://api.uber.test/v2/eats/order/*' => Http::response(
                $this->makeUberOrderPayload('uber-order-1', 'uber-store-1')
            ),
            'https://api.uber.test/v1/eats/orders/*/accept_pos_order' => Http::response('', 204),
        ]);

        $response = $this->postSignedWebhook($store, [
            'event_id' => 'evt-1',
            'event_type' => 'orders.notification',
            'meta' => [
                'user_id' => 'uber-store-1',
                'resource_id' => 'uber-order-1',
            ],
        ]);

        $response->assertOk();

        $order = Order::query()
            ->with('items')
            ->where('source_platform', 'uber_eats')
            ->where('source_order_id', 'uber-order-1')
            ->first();

        $this->assertNotNull($order);
        $this->assertSame($store->id, $order->store_id);
        $this->assertSame('takeout', $order->order_type);
        $this->assertSame('preparing', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('UE12345', $order->source_display_id);
        $this->assertSame(320, $order->subtotal);
        $this->assertSame(2, $order->items->count());

        $matchedItem = $order->items->firstWhere('product_name', $product->name);
        $this->assertNotNull($matchedItem);
        $this->assertSame($product->id, $matchedItem->product_id);
        $this->assertStringContainsString('Large (+20)', (string) $matchedItem->note);

        $unmatchedItem = $order->items->firstWhere('product_name', 'Mystery Dessert');
        $this->assertNotNull($unmatchedItem);
        $this->assertNull($unmatchedItem->product_id);

        $this->assertDatabaseHas('uber_eats_webhook_events', [
            'event_id' => 'evt-1',
            'status' => 'processed',
            'local_store_id' => $store->id,
        ]);

        Http::assertSent(function (HttpRequest $request) use ($order): bool {
            return $request->url() === 'https://api.uber.test/v1/eats/orders/uber-order-1/accept_pos_order'
                && $request['external_reference_id'] === $order->order_no
                && $request['fields_relayed']['order_special_instructions'] === true
                && $request['fields_relayed']['item_special_instructions'] === true
                && $request['fields_relayed']['item_special_requests'] === true
                && $request['fields_relayed']['promotions'] === false;
        });
        Http::assertSent(function (HttpRequest $request): bool {
            if ($request->url() !== 'https://auth.uber.test/oauth/token') {
                return false;
            }

            parse_str($request->body(), $payload);

            return ($payload['client_id'] ?? null) === 'store-client-id-1'
                && ($payload['client_secret'] ?? null) === 'store-client-secret-1'
                && ($payload['scope'] ?? null) === 'eats.order eats.store.orders.read';
        });
    }

    public function test_processed_event_id_is_deduplicated_on_retry(): void
    {
        $store = Store::create([
            'name' => 'Uber Dedup Store',
            'slug' => 'uber-dedup-store',
            'is_active' => true,
            'uber_eats_enabled' => true,
            'uber_eats_store_id' => 'uber-store-dedup',
            'uber_eats_client_id' => 'store-client-id-dedup',
            'uber_eats_client_secret' => 'store-client-secret-dedup',
            'prep_time_minutes' => 20,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Meals',
            'sort' => 1,
            'prep_time_minutes' => 20,
            'is_active' => true,
        ]);

        Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Chicken Bowl',
            'price' => 150,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://auth.uber.test/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://api.uber.test/v2/eats/order/*' => Http::response(
                $this->makeUberOrderPayload('uber-order-dedup', 'uber-store-dedup', [
                    'cart' => [
                        'special_instructions' => '',
                        'items' => [
                            [
                                'id' => 'menu-item-1',
                                'title' => 'Chicken Bowl',
                                'quantity' => 1,
                                'price' => [
                                    'unit_price' => ['amount' => 150],
                                    'total_price' => ['amount' => 150],
                                ],
                            ],
                        ],
                    ],
                    'payment' => [
                        'charges' => [
                            'sub_total' => ['amount' => 150],
                        ],
                    ],
                ])
            ),
            'https://api.uber.test/v1/eats/orders/*/accept_pos_order' => Http::response('', 204),
        ]);

        $payload = [
            'event_id' => 'evt-dedup',
            'event_type' => 'orders.notification',
            'meta' => [
                'user_id' => 'uber-store-dedup',
                'resource_id' => 'uber-order-dedup',
            ],
        ];

        $this->postSignedWebhook($store, $payload)->assertOk();
        $this->postSignedWebhook($store, $payload)->assertOk();

        $this->assertSame(
            1,
            Order::query()
                ->where('source_platform', 'uber_eats')
                ->where('source_order_id', 'uber-order-dedup')
                ->count()
        );
        $this->assertDatabaseCount('uber_eats_webhook_events', 1);
        Http::assertSentCount(3);
    }

    public function test_cancel_webhook_marks_existing_order_as_cancelled(): void
    {
        $store = Store::create([
            'name' => 'Uber Cancel Store',
            'slug' => 'uber-cancel-store',
            'is_active' => true,
            'uber_eats_enabled' => true,
            'uber_eats_store_id' => 'uber-store-cancel',
            'uber_eats_client_id' => 'store-client-id-cancel',
            'uber_eats_client_secret' => 'store-client-secret-cancel',
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'UBER-CANCEL-1',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'source_platform' => 'uber_eats',
            'source_order_id' => 'uber-order-cancel',
            'subtotal' => 180,
            'total' => 180,
        ]);

        Http::fake();

        $response = $this->postSignedWebhook($store, [
            'event_id' => 'evt-cancel',
            'event_type' => 'orders.cancel',
            'meta' => [
                'user_id' => 'uber-store-cancel',
                'resource_id' => 'uber-order-cancel',
            ],
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('Cancelled on Uber Eats', $order->cancel_reason_other);
        $this->assertDatabaseHas('uber_eats_webhook_events', [
            'event_id' => 'evt-cancel',
            'status' => 'processed',
            'local_store_id' => $store->id,
        ]);

        Http::assertNothingSent();
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $store = Store::create([
            'name' => 'Uber Invalid Store',
            'slug' => 'uber-invalid-store',
            'is_active' => true,
            'uber_eats_enabled' => true,
            'uber_eats_store_id' => 'uber-store-invalid',
            'uber_eats_client_id' => 'store-client-id-invalid',
            'uber_eats_client_secret' => 'store-client-secret-invalid',
        ]);

        Http::fake();

        $body = json_encode([
            'event_id' => 'evt-invalid',
            'event_type' => 'orders.notification',
            'meta' => [
                'user_id' => 'uber-store-invalid',
                'resource_id' => 'uber-order-invalid',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $response = $this
            ->call(
                'POST',
                route('webhooks.uber-eats'),
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_X_UBER_SIGNATURE' => hash_hmac('sha256', $body, 'wrong-secret'),
                ],
                $body
            );

        $response->assertStatus(401);

        $this->assertDatabaseCount('uber_eats_webhook_events', 0);
        Http::assertNothingSent();
    }

    public function test_terminal_cancelled_order_is_not_imported_when_no_local_order_exists(): void
    {
        $store = Store::create([
            'name' => 'Uber Terminal Store',
            'slug' => 'uber-terminal-store',
            'is_active' => true,
            'uber_eats_enabled' => true,
            'uber_eats_store_id' => 'uber-store-terminal',
            'uber_eats_client_id' => 'store-client-id-terminal',
            'uber_eats_client_secret' => 'store-client-secret-terminal',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://auth.uber.test/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://api.uber.test/v2/eats/order/*' => Http::response(
                $this->makeUberOrderPayload('uber-order-terminal', 'uber-store-terminal', [
                    'current_state' => 'CANCELED',
                ])
            ),
        ]);

        $response = $this->postSignedWebhook($store, [
            'event_id' => 'evt-terminal',
            'event_type' => 'orders.notification',
            'meta' => [
                'user_id' => 'uber-store-terminal',
                'resource_id' => 'uber-order-terminal',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('orders', [
            'source_platform' => 'uber_eats',
            'source_order_id' => 'uber-order-terminal',
        ]);
        $this->assertDatabaseHas('uber_eats_webhook_events', [
            'event_id' => 'evt-terminal',
            'status' => 'ignored',
            'local_store_id' => $store->id,
        ]);

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://auth.uber.test/oauth/token');
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.uber.test/v2/eats/order/uber-order-terminal');
        Http::assertNotSent(
            fn (HttpRequest $request): bool => str_contains($request->url(), '/accept_pos_order')
                || str_contains($request->url(), '/deny_pos_order')
        );
    }

    private function postSignedWebhook(Store $store, array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $body, (string) $store->uber_eats_client_secret);

        return $this
            ->call(
                'POST',
                route('webhooks.uber-eats'),
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_X_UBER_SIGNATURE' => $signature,
                ],
                $body
            );
    }

    private function makeUberOrderPayload(string $orderId, string $uberStoreId, array $overrides = []): array
    {
        $payload = [
            'id' => $orderId,
            'display_id' => 'UE12345',
            'current_state' => 'CREATED',
            'placed_at' => '2026-04-24T10:00:00Z',
            'brand' => 'UBER_EATS',
            'type' => 'DELIVERY_BY_UBER',
            'store' => [
                'id' => $uberStoreId,
            ],
            'eater' => [
                'first_name' => 'Alice',
                'phone' => '+886912345678',
            ],
            'cart' => [
                'special_instructions' => 'Leave at front desk',
                'items' => [
                    [
                        'id' => 'menu-item-1',
                        'title' => 'Fried Rice',
                        'quantity' => 2,
                        'price' => [
                            'unit_price' => ['amount' => 120],
                            'total_price' => ['amount' => 240],
                        ],
                        'selected_modifier_groups' => [
                            [
                                'title' => 'Size',
                                'selected_items' => [
                                    [
                                        'title' => 'Large',
                                        'price' => [
                                            'unit_price' => ['amount' => 20],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'special_instructions' => 'No onions',
                    ],
                    [
                        'id' => 'menu-item-2',
                        'title' => 'Mystery Dessert',
                        'quantity' => 1,
                        'price' => [
                            'unit_price' => ['amount' => 80],
                            'total_price' => ['amount' => 80],
                        ],
                    ],
                ],
            ],
            'payment' => [
                'charges' => [
                    'sub_total' => ['amount' => 320],
                ],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
