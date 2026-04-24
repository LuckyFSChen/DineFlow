<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\UberEatsWebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UberEatsOrderSyncService
{
    private const SOURCE_PLATFORM = 'uber_eats';

    public function __construct(
        private readonly UberEatsApiClient $client
    ) {
    }

    public function verifySignature(Store $store, string $rawBody, ?string $signature): bool
    {
        return $this->client->verifySignature($store, $rawBody, $signature);
    }

    /**
     * @return array{status:string,local_store_id:int|null,message:?string}
     */
    public function process(UberEatsWebhookEvent $event, ?Store $verifiedStore = null): array
    {
        $payload = is_array($event->payload) ? $event->payload : [];

        return match ($event->event_type) {
            'orders.notification',
            'orders.scheduled.notification',
            'order.fulfillment_issues.resolved' => $this->processIncomingOrderEvent($event, $payload, $verifiedStore),
            'orders.cancel',
            'orders.failure' => $this->processCancelledOrderEvent($event, $payload, $verifiedStore),
            'store.provisioned' => $this->processStoreProvisionedEvent($payload, $verifiedStore),
            'store.deprovisioned' => $this->processStoreDeprovisionedEvent($payload, $verifiedStore),
            default => [
                'status' => 'ignored',
                'local_store_id' => null,
                'message' => 'Unsupported Uber Eats event type.',
            ],
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string,local_store_id:int|null,message:?string}
     */
    private function processIncomingOrderEvent(UberEatsWebhookEvent $event, array $payload, ?Store $verifiedStore = null): array
    {
        $orderId = $this->extractOrderId($payload);
        if ($orderId === null) {
            return [
                'status' => 'ignored',
                'local_store_id' => null,
                'message' => 'Missing Uber Eats order id.',
            ];
        }

        $apiStore = $verifiedStore ?? $this->resolveLocalStore($this->extractStoreId($payload));
        if (! $apiStore || ! $this->client->hasCredentials($apiStore)) {
            throw new RuntimeException('Uber Eats credentials are not configured for the webhook store.');
        }

        $orderPayload = $this->client->fetchOrder($apiStore, $orderId);
        $uberStoreId = $this->extractOrderStoreId($orderPayload) ?? $this->extractStoreId($payload);
        $existingOrder = $this->findExistingOrder($orderId);
        $localStore = $existingOrder?->store ?? $verifiedStore ?? $this->resolveLocalStore($uberStoreId);
        $localStoreId = $localStore?->id;
        $state = strtoupper((string) Arr::get($orderPayload, 'current_state', 'UNKNOWN'));

        if ($localStore === null) {
            if ($state === 'CREATED') {
                $this->client->denyOrder(
                    $apiStore,
                    $orderId,
                    'POS_OFFLINE',
                    'Store is not mapped to DineFlow.'
                );

                return [
                    'status' => 'processed',
                    'local_store_id' => null,
                    'message' => 'Order denied because no matching DineFlow store was found.',
                ];
            }

            return [
                'status' => 'ignored',
                'local_store_id' => null,
                'message' => 'No matching DineFlow store was found.',
            ];
        }

        if ($existingOrder === null && in_array($state, ['CANCELED', 'DENIED'], true)) {
            return [
                'status' => 'ignored',
                'local_store_id' => $localStoreId,
                'message' => 'Ignored terminal Uber Eats order state because no matching DineFlow order exists yet.',
            ];
        }

        if ($existingOrder === null && $state === 'CREATED') {
            if (! $localStore->hasUberEatsIntegration()) {
                $this->client->denyOrder(
                    $apiStore,
                    $orderId,
                    'POS_OFFLINE',
                    'Uber Eats integration is disabled for this store.'
                );

                return [
                    'status' => 'processed',
                    'local_store_id' => $localStoreId,
                    'message' => 'Order denied because the integration is disabled for this store.',
                ];
            }

            if (! $localStore->isOrderingAvailable()) {
                $this->client->denyOrder(
                    $apiStore,
                    $orderId,
                    'STORE_CLOSED',
                    'Store is currently unavailable in DineFlow.'
                );

                return [
                    'status' => 'processed',
                    'local_store_id' => $localStoreId,
                    'message' => 'Order denied because the store is currently unavailable.',
                ];
            }
        }

        $order = $this->syncOrderFromPayload($localStore, $orderPayload);

        if ($existingOrder === null && $state === 'CREATED') {
            try {
                $this->acceptImportedOrder($apiStore, $localStore, $order);
            } catch (\Throwable $e) {
                if (! Order::query()->whereKey($order->id)->exists()) {
                    throw $e;
                }

                $order->delete();

                throw $e;
            }
        }

        return [
            'status' => 'processed',
            'local_store_id' => $localStoreId,
            'message' => null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string,local_store_id:int|null,message:?string}
     */
    private function processCancelledOrderEvent(UberEatsWebhookEvent $event, array $payload, ?Store $verifiedStore = null): array
    {
        $orderId = $this->extractOrderId($payload);
        if ($orderId === null) {
            return [
                'status' => 'ignored',
                'local_store_id' => null,
                'message' => 'Missing Uber Eats order id.',
            ];
        }

        $localStore = $verifiedStore ?? $this->resolveLocalStore($this->extractStoreId($payload));
        $order = Order::query()
            ->where('source_platform', self::SOURCE_PLATFORM)
            ->where('source_order_id', $orderId)
            ->first();

        if (! $order) {
            return [
                'status' => 'ignored',
                'local_store_id' => $localStore?->id,
                'message' => 'No matching DineFlow order was found for the cancelled Uber Eats order.',
            ];
        }

        $order->update([
            'status' => 'cancelled',
            'cancel_reason_other' => trim((string) ($order->cancel_reason_other ?? '')) !== ''
                ? $order->cancel_reason_other
                : 'Cancelled on Uber Eats',
            'source_payload' => $payload,
        ]);

        return [
            'status' => 'processed',
            'local_store_id' => $order->store_id,
            'message' => null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string,local_store_id:int|null,message:?string}
     */
    private function processStoreProvisionedEvent(array $payload, ?Store $verifiedStore = null): array
    {
        $uberStoreId = $this->extractStoreId($payload);
        $localStore = $verifiedStore ?? $this->resolveLocalStore($uberStoreId);

        if (! $localStore) {
            return [
                'status' => 'ignored',
                'local_store_id' => null,
                'message' => 'Store provisioned event did not match any DineFlow store.',
            ];
        }

        return [
            'status' => 'processed',
            'local_store_id' => $localStore->id,
            'message' => null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string,local_store_id:int|null,message:?string}
     */
    private function processStoreDeprovisionedEvent(array $payload, ?Store $verifiedStore = null): array
    {
        $uberStoreId = $this->extractStoreId($payload);
        $localStore = $verifiedStore ?? $this->resolveLocalStore($uberStoreId);

        if (! $localStore) {
            return [
                'status' => 'ignored',
                'local_store_id' => null,
                'message' => 'Store deprovisioned event did not match any DineFlow store.',
            ];
        }

        $localStore->update([
            'uber_eats_enabled' => false,
        ]);

        return [
            'status' => 'processed',
            'local_store_id' => $localStore->id,
            'message' => null,
        ];
    }

    /**
     * @param array<string, mixed> $orderPayload
     */
    private function syncOrderFromPayload(Store $store, array $orderPayload): Order
    {
        $sourceOrderId = trim((string) Arr::get($orderPayload, 'id', ''));
        if ($sourceOrderId === '') {
            throw new RuntimeException('Uber Eats order payload does not contain an order id.');
        }

        $existingOrder = Order::query()
            ->where('source_platform', self::SOURCE_PLATFORM)
            ->where('source_order_id', $sourceOrderId)
            ->first();

        if ($existingOrder) {
            $this->syncExistingOrderState($existingOrder, $orderPayload);

            return $existingOrder->fresh(['items']) ?? $existingOrder;
        }

        $builtItems = $this->buildOrderItems($store, $orderPayload);
        if ($builtItems === []) {
            throw new RuntimeException('Uber Eats order payload does not contain any importable items.');
        }

        $subtotal = $this->resolveOrderSubtotal($builtItems, $orderPayload);
        $customerName = $this->resolveCustomerName($orderPayload);
        $customerPhone = $this->normalizeExternalPhone((string) Arr::get($orderPayload, 'eater.phone', ''));
        $platformOrderedAt = $this->parseOrderDate(Arr::get($orderPayload, 'placed_at'));
        $sourceStoreId = trim((string) ($this->extractOrderStoreId($orderPayload) ?? ''));
        $sourceDisplayId = trim((string) Arr::get($orderPayload, 'display_id', ''));

        return DB::transaction(function () use (
            $store,
            $orderPayload,
            $builtItems,
            $subtotal,
            $customerName,
            $customerPhone,
            $platformOrderedAt,
            $sourceOrderId,
            $sourceStoreId,
            $sourceDisplayId
        ): Order {
            $order = Order::query()->create([
                'store_id' => $store->id,
                'dining_table_id' => null,
                'order_type' => 'takeout',
                'cart_token' => null,
                'order_no' => Order::generateOrderNoForStore((int) $store->id),
                'status' => $this->mapLocalOrderStatus((string) Arr::get($orderPayload, 'current_state', 'CREATED')),
                'payment_status' => 'paid',
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => null,
                'order_locale' => $this->resolveOrderLocaleForStore($store),
                'source_platform' => self::SOURCE_PLATFORM,
                'source_order_id' => $sourceOrderId,
                'source_store_id' => $sourceStoreId !== '' ? $sourceStoreId : null,
                'source_display_id' => $sourceDisplayId !== '' ? $sourceDisplayId : null,
                'platform_ordered_at' => $platformOrderedAt,
                'source_payload' => $orderPayload,
                'note' => $this->buildOrderNote($orderPayload),
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            foreach ($builtItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                    'note' => $item['note'],
                ]);
            }

            return $order->fresh(['items']) ?? $order;
        });
    }

    /**
     * @param array<string, mixed> $orderPayload
     */
    private function syncExistingOrderState(Order $order, array $orderPayload): void
    {
        $updates = [
            'status' => $this->mapLocalOrderStatus((string) Arr::get($orderPayload, 'current_state', 'CREATED')),
            'payment_status' => 'paid',
            'source_store_id' => $this->extractOrderStoreId($orderPayload),
            'source_display_id' => trim((string) Arr::get($orderPayload, 'display_id', '')) ?: null,
            'platform_ordered_at' => $this->parseOrderDate(Arr::get($orderPayload, 'placed_at')),
            'source_payload' => $orderPayload,
            'note' => $this->buildOrderNote($orderPayload),
        ];

        if (strtolower((string) $updates['status']) === 'cancelled' && ! $order->hasCancelReasons()) {
            $updates['cancel_reason_other'] = 'Cancelled on Uber Eats';
        }

        $order->update($updates);
    }

    private function acceptImportedOrder(Store $apiStore, Store $store, Order $order): void
    {
        $estimatedReady = $store->estimateCustomerReadyTimeForOrderItems(
            $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'qty' => $item->qty,
            ])->all()
        );

        $pickupAt = now($store->businessTimezone())
            ->addMinutes(max(1, (int) ($estimatedReady['minutes'] ?? 15)))
            ->timestamp;

        $this->client->acceptOrder($apiStore, (string) $order->source_order_id, [
            'reason' => 'Accepted by DineFlow',
            'pickup_time' => $pickupAt,
            'external_reference_id' => (string) $order->order_no,
            'fields_relayed' => [
                'order_special_instructions' => true,
                'item_special_instructions' => true,
                'item_special_requests' => true,
                'promotions' => false,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $orderPayload
     * @return array<int, array{product_id:int|null,product_name:string,price:int,qty:int,subtotal:int,note:?string}>
     */
    private function buildOrderItems(Store $store, array $orderPayload): array
    {
        $rawItems = Arr::get($orderPayload, 'cart.items', []);
        if (! is_array($rawItems)) {
            return [];
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->get(['id', 'name']);

        $lookup = [];
        foreach ($products as $product) {
            foreach ($this->buildLookupKeys((string) $product->name) as $key) {
                if ($key !== '' && ! isset($lookup[$key])) {
                    $lookup[$key] = (int) $product->id;
                }
            }
        }

        $items = [];

        foreach ($rawItems as $rawItem) {
            if (! is_array($rawItem)) {
                continue;
            }

            $productName = trim((string) Arr::get($rawItem, 'title', Arr::get($rawItem, 'id', 'Uber Eats Item')));
            if ($productName === '') {
                $productName = 'Uber Eats Item';
            }

            $qty = max(1, (int) Arr::get($rawItem, 'quantity', 1));
            $price = max(
                0,
                (int) (
                    Arr::get($rawItem, 'price.unit_price.amount')
                    ?? Arr::get($rawItem, 'price.total_price.amount', 0) / max($qty, 1)
                )
            );
            $subtotal = max(
                0,
                (int) (Arr::get($rawItem, 'price.total_price.amount') ?? ($price * $qty))
            );

            $matchedProductId = null;
            foreach ($this->buildLookupKeys($productName) as $key) {
                if (isset($lookup[$key])) {
                    $matchedProductId = $lookup[$key];
                    break;
                }
            }

            $items[] = [
                'product_id' => $matchedProductId,
                'product_name' => $productName,
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $subtotal,
                'note' => $this->buildOrderItemNote($rawItem),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $orderPayload
     */
    private function buildOrderNote(array $orderPayload): ?string
    {
        $parts = [];
        $brand = strtoupper(trim((string) Arr::get($orderPayload, 'brand', '')));
        $displayId = trim((string) Arr::get($orderPayload, 'display_id', ''));
        $type = strtoupper(trim((string) Arr::get($orderPayload, 'type', '')));
        $specialInstructions = trim((string) Arr::get($orderPayload, 'cart.special_instructions', ''));

        $sourceLabel = match ($brand) {
            'POSTMATES' => 'Postmates',
            default => 'Uber Eats',
        };

        $parts[] = $displayId !== ''
            ? $sourceLabel.' #'.$displayId
            : $sourceLabel;

        $fulfillmentLabel = match ($type) {
            'PICK_UP' => 'Pickup',
            'DELIVERY_BY_RESTAURANT' => 'Merchant Delivery',
            'DELIVERY_BY_UBER' => 'Uber Delivery',
            'DINE_IN' => 'Dine In',
            default => null,
        };

        if ($fulfillmentLabel !== null) {
            $parts[] = $fulfillmentLabel;
        }

        if ($specialInstructions !== '') {
            $parts[] = 'Order note: '.$specialInstructions;
        }

        $filteredParts = array_values(array_filter($parts, fn ($value) => trim((string) $value) !== ''));

        return $filteredParts === [] ? null : implode(' | ', $filteredParts);
    }

    /**
     * @param array<string, mixed> $rawItem
     */
    private function buildOrderItemNote(array $rawItem): ?string
    {
        $parts = [];
        $modifierGroups = Arr::get($rawItem, 'selected_modifier_groups', []);

        if (is_array($modifierGroups)) {
            foreach ($modifierGroups as $group) {
                if (! is_array($group)) {
                    continue;
                }

                $groupTitle = trim((string) Arr::get($group, 'title', ''));

                $selectedItems = Arr::get($group, 'selected_items', []);
                if (is_array($selectedItems) && $selectedItems !== []) {
                    $labels = [];
                    foreach ($selectedItems as $selectedItem) {
                        if (! is_array($selectedItem)) {
                            continue;
                        }

                        $title = trim((string) Arr::get($selectedItem, 'title', ''));
                        if ($title === '') {
                            continue;
                        }

                        $extraPrice = (int) Arr::get($selectedItem, 'price.unit_price.amount', 0);
                        $labels[] = $extraPrice > 0
                            ? $title.' (+'.$extraPrice.')'
                            : $title;
                    }

                    if ($labels !== []) {
                        $parts[] = $groupTitle !== ''
                            ? $groupTitle.': '.implode(', ', $labels)
                            : implode(', ', $labels);
                    }
                }

                $removedItems = Arr::get($group, 'removed_items', []);
                if (is_array($removedItems) && $removedItems !== []) {
                    $labels = [];
                    foreach ($removedItems as $removedItem) {
                        if (! is_array($removedItem)) {
                            continue;
                        }

                        $title = trim((string) Arr::get($removedItem, 'title', ''));
                        if ($title === '') {
                            continue;
                        }

                        $labels[] = 'No '.$title;
                    }

                    if ($labels !== []) {
                        $parts[] = $groupTitle !== ''
                            ? $groupTitle.': '.implode(', ', $labels)
                            : implode(', ', $labels);
                    }
                }
            }
        }

        $specialInstructions = trim((string) Arr::get($rawItem, 'special_instructions', ''));
        if ($specialInstructions !== '') {
            $parts[] = 'Note: '.$specialInstructions;
        }

        $filteredParts = array_values(array_filter($parts, fn ($value) => trim((string) $value) !== ''));

        return $filteredParts === [] ? null : implode(' | ', $filteredParts);
    }

    /**
     * @param array<int, array{product_id:int|null,product_name:string,price:int,qty:int,subtotal:int,note:?string}> $builtItems
     * @param array<string, mixed> $orderPayload
     */
    private function resolveOrderSubtotal(array $builtItems, array $orderPayload): int
    {
        $calculatedSubtotal = (int) collect($builtItems)->sum('subtotal');
        $reportedSubtotal = max(0, (int) Arr::get($orderPayload, 'payment.charges.sub_total.amount', 0));

        return $reportedSubtotal > 0 ? $reportedSubtotal : $calculatedSubtotal;
    }

    /**
     * @param array<string, mixed> $orderPayload
     */
    private function resolveCustomerName(array $orderPayload): ?string
    {
        $firstName = trim((string) Arr::get($orderPayload, 'eater.first_name', ''));

        return $firstName !== '' ? $firstName : 'Uber Eats';
    }

    private function normalizeExternalPhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone));

        return is_string($digits) && $digits !== '' ? $digits : null;
    }

    private function resolveOrderLocaleForStore(Store $store): string
    {
        return match (strtolower((string) ($store->country_code ?? 'tw'))) {
            'vn' => 'vi',
            'us' => 'en',
            'cn' => 'zh_CN',
            default => 'zh_TW',
        };
    }

    private function mapLocalOrderStatus(string $uberState): string
    {
        return match (strtoupper(trim($uberState))) {
            'CANCELED', 'DENIED' => 'cancelled',
            'FINISHED' => 'completed',
            default => 'preparing',
        };
    }

    private function parseOrderDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractOrderId(array $payload): ?string
    {
        $orderId = trim((string) (
            Arr::get($payload, 'meta.resource_id')
            ?? Arr::get($payload, 'resource_id')
            ?? ''
        ));

        return $orderId !== '' ? $orderId : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractStoreId(array $payload): ?string
    {
        $storeId = trim((string) (
            Arr::get($payload, 'meta.user_id')
            ?? Arr::get($payload, 'store_id')
            ?? ''
        ));

        return $storeId !== '' ? $storeId : null;
    }

    /**
     * @param array<string, mixed> $orderPayload
     */
    private function extractOrderStoreId(array $orderPayload): ?string
    {
        $storeId = trim((string) Arr::get($orderPayload, 'store.id', ''));

        return $storeId !== '' ? $storeId : null;
    }

    private function findExistingOrder(string $orderId): ?Order
    {
        return Order::query()
            ->with('store')
            ->where('source_platform', self::SOURCE_PLATFORM)
            ->where('source_order_id', $orderId)
            ->first();
    }

    private function resolveLocalStore(?string $uberStoreId): ?Store
    {
        $normalizedStoreId = trim((string) ($uberStoreId ?? ''));
        if ($normalizedStoreId === '') {
            return null;
        }

        return Store::query()
            ->where('uber_eats_store_id', $normalizedStoreId)
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function buildLookupKeys(string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $lower = mb_strtolower($trimmed, 'UTF-8');
        $spaced = preg_replace('/\s+/u', ' ', $lower) ?? $lower;
        $compact = preg_replace('/[\s\-_]+/u', '', $lower) ?? $lower;

        return array_values(array_unique(array_filter([$spaced, $compact])));
    }
}
