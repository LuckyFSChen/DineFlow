<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FoodpandaOrderSyncService
{
    private const SOURCE_PLATFORM = 'foodpanda';

    private const TERMINAL_STATUSES = ['cancelled', 'canceled', 'complete', 'completed', 'picked_up', 'collected', 'served'];

    public function __construct(
        private readonly FoodpandaApiClient $client
    ) {
    }

    /**
     * @return array{status:string,local_store_id:int|null,message:?string}
     */
    public function processWebhook(array $payload, ?string $authorizationHeader): array
    {
        $orderId = $this->extractOrderId($payload);
        $localStore = $this->resolveLocalStoreFromPayload($payload);

        if ($localStore === null) {
            return [
                'status' => 'ignored',
                'local_store_id' => null,
                'message' => 'No matching DineFlow store was found.',
            ];
        }

        if (! $localStore->hasFoodpandaIntegration()) {
            return [
                'status' => 'ignored',
                'local_store_id' => $localStore->id,
                'message' => 'Foodpanda integration is disabled for this store.',
            ];
        }

        if (! $this->isValidWebhookAuthorization($localStore, $authorizationHeader)) {
            return [
                'status' => 'unauthorized',
                'local_store_id' => $localStore->id,
                'message' => 'Invalid Foodpanda webhook authorization header.',
            ];
        }

        if ($orderId === null) {
            return [
                'status' => 'ignored',
                'local_store_id' => $localStore->id,
                'message' => 'Missing Foodpanda order id.',
            ];
        }

        $this->syncOrderFromPayload($localStore, $payload);

        return [
            'status' => 'processed',
            'local_store_id' => $localStore->id,
            'message' => null,
        ];
    }

    public function resolveLocalStoreFromPayload(array $payload): ?Store
    {
        $chainId = trim((string) Arr::get($payload, 'client.chain_id', ''));
        $externalPartnerConfigId = trim((string) Arr::get($payload, 'client.external_partner_config_id', ''));
        $storeId = trim((string) Arr::get($payload, 'client.store_id', ''));

        if ($externalPartnerConfigId !== '') {
            $store = Store::query()
                ->where('foodpanda_enabled', true)
                ->where('foodpanda_external_partner_config_id', $externalPartnerConfigId)
                ->when($chainId !== '', function ($query) use ($chainId) {
                    $query->where(function ($inner) use ($chainId) {
                        $inner->whereNull('foodpanda_chain_id')
                            ->orWhere('foodpanda_chain_id', $chainId);
                    });
                })
                ->first();

            if ($store) {
                return $store;
            }
        }

        if ($storeId === '') {
            return null;
        }

        return Store::query()
            ->where('foodpanda_enabled', true)
            ->where('foodpanda_store_id', $storeId)
            ->when($chainId !== '', function ($query) use ($chainId) {
                $query->where(function ($inner) use ($chainId) {
                    $inner->whereNull('foodpanda_chain_id')
                        ->orWhere('foodpanda_chain_id', $chainId);
                });
            })
            ->first();
    }

    public function isValidWebhookAuthorization(Store $store, ?string $authorizationHeader): bool
    {
        $expected = $this->client->webhookSecret($store);
        $received = trim((string) $authorizationHeader);

        return $expected !== '' && $received !== '' && hash_equals($expected, $received);
    }

    public function cancelOrder(Order $order, array $cancelReasons = [], ?string $otherReason = null): void
    {
        $store = $order->store()->first();

        if (! $store instanceof Store) {
            throw new RuntimeException('Foodpanda order is not attached to a store.');
        }

        $this->client->updateOrderStatus(
            $store,
            $this->resolveSourceOrderId($order),
            'CANCELLED',
            $this->buildOutboundItems($order),
            [
                'reason' => $this->mapCancellationReason($cancelReasons, $otherReason),
            ]
        );
    }

    public function completeOrder(Order $order): void
    {
        $store = $order->store()->first();

        if (! $store instanceof Store) {
            throw new RuntimeException('Foodpanda order is not attached to a store.');
        }

        $this->client->updateOrderStatus(
            $store,
            $this->resolveSourceOrderId($order),
            $this->resolveCompletionStatus($order),
            $this->buildOutboundItems($order)
        );
    }

    public function isFoodpandaOrder(Order $order): bool
    {
        return strtolower((string) $order->source_platform) === self::SOURCE_PLATFORM
            && trim((string) ($order->source_order_id ?? '')) !== '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function buildWebhookEventId(array $payload): string
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', is_string($encoded) ? $encoded : serialize($payload));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function extractWebhookEventType(array $payload): string
    {
        $status = trim((string) ($payload['status'] ?? ''));

        return $status !== '' ? $status : 'unknown';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function extractWebhookStoreId(array $payload): ?string
    {
        $storeId = trim((string) (
            Arr::get($payload, 'client.external_partner_config_id')
            ?? Arr::get($payload, 'client.store_id')
            ?? ''
        ));

        return $storeId !== '' ? $storeId : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function extractOrderId(array $payload): ?string
    {
        $orderId = trim((string) Arr::get($payload, 'order_id', ''));

        return $orderId !== '' ? $orderId : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncOrderFromPayload(Store $store, array $payload): Order
    {
        $sourceOrderId = $this->extractOrderId($payload);

        if ($sourceOrderId === null) {
            throw new RuntimeException('Foodpanda order payload does not contain an order id.');
        }

        $existingOrder = Order::query()
            ->where('source_platform', self::SOURCE_PLATFORM)
            ->where('source_order_id', $sourceOrderId)
            ->first();

        if ($existingOrder) {
            $this->syncExistingOrderState($existingOrder, $payload);

            return $existingOrder->fresh(['items']) ?? $existingOrder;
        }

        $builtItems = $this->buildOrderItems($store, $payload);
        if ($builtItems === []) {
            throw new RuntimeException('Foodpanda order payload does not contain any importable items.');
        }

        $status = $this->mapLocalOrderStatus((string) Arr::get($payload, 'status', 'RECEIVED'));
        $subtotal = $this->resolveRoundedAmount(
            Arr::get($payload, 'payment.sub_total'),
            (float) collect($builtItems)->sum('subtotal')
        );
        $total = $this->resolveRoundedAmount(
            Arr::get($payload, 'payment.order_total'),
            $subtotal
        );
        $customerName = $this->resolveCustomerName($payload);
        $customerPhone = $this->normalizeExternalPhone((string) Arr::get($payload, 'customer.phone_number', ''));
        $platformOrderedAt = $this->parseOrderDate(
            Arr::get($payload, 'sys.created_at')
            ?? Arr::get($payload, 'created_at')
            ?? Arr::get($payload, 'accepted_for')
        );
        $sourceStoreId = trim((string) Arr::get($payload, 'client.store_id', ''));
        $sourceDisplayId = trim((string) (
            Arr::get($payload, 'order_code')
            ?? Arr::get($payload, 'external_order_id')
            ?? ''
        ));

        return DB::transaction(function () use (
            $store,
            $payload,
            $builtItems,
            $status,
            $subtotal,
            $total,
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
                'order_no' => $this->buildLocalOrderNo($store, $sourceDisplayId, $sourceOrderId),
                'status' => $status,
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
                'source_payload' => $payload,
                'note' => $this->buildOrderNote($payload),
                'cancel_reason_other' => $status === 'cancelled' ? $this->resolveCancellationNote($payload) : null,
                'subtotal' => $subtotal,
                'total' => $total,
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
     * @param array<string, mixed> $payload
     */
    private function syncExistingOrderState(Order $order, array $payload): void
    {
        $mappedStatus = $this->mapLocalOrderStatus((string) Arr::get($payload, 'status', 'RECEIVED'));
        $updates = [
            'payment_status' => 'paid',
            'source_store_id' => trim((string) Arr::get($payload, 'client.store_id', '')) ?: null,
            'source_display_id' => trim((string) (
                Arr::get($payload, 'order_code')
                ?? Arr::get($payload, 'external_order_id')
                ?? ''
            )) ?: null,
            'platform_ordered_at' => $this->parseOrderDate(
                Arr::get($payload, 'sys.created_at')
                ?? Arr::get($payload, 'created_at')
                ?? Arr::get($payload, 'accepted_for')
            ),
            'source_payload' => $payload,
            'note' => $this->buildOrderNote($payload),
            'subtotal' => $this->resolveRoundedAmount(
                Arr::get($payload, 'payment.sub_total'),
                (float) $order->subtotal
            ),
            'total' => $this->resolveRoundedAmount(
                Arr::get($payload, 'payment.order_total'),
                (float) $order->total
            ),
        ];

        if ($this->shouldUpdateLocalStatus($order, $mappedStatus)) {
            $updates['status'] = $mappedStatus;
        }

        if ($mappedStatus === 'cancelled' && ! $order->hasCancelReasons()) {
            $updates['cancel_reason_other'] = $this->resolveCancellationNote($payload);
        }

        DB::transaction(function () use ($order, $payload, $updates): void {
            $order->update($updates);

            if ($this->shouldRefreshItems($order)) {
                $builtItems = $this->buildOrderItems($order->store, $payload);

                if ($builtItems !== []) {
                    $order->items()->delete();

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
                }
            }
        });
    }

    private function shouldRefreshItems(Order $order): bool
    {
        return in_array(strtolower((string) $order->status), ['pending', 'accepted', 'confirmed', 'received'], true)
            && ! $order->items()->whereNotNull('completed_at')->exists();
    }

    private function shouldUpdateLocalStatus(Order $order, string $incomingStatus): bool
    {
        $currentStatus = strtolower((string) $order->status);
        $incomingStatus = strtolower(trim($incomingStatus));

        if ($incomingStatus === '') {
            return false;
        }

        if ($currentStatus === $incomingStatus) {
            return true;
        }

        if ($currentStatus === 'cancelled' && $incomingStatus !== 'cancelled') {
            return false;
        }

        if ($incomingStatus === 'cancelled') {
            return true;
        }

        if (in_array($currentStatus, self::TERMINAL_STATUSES, true) && ! in_array($incomingStatus, self::TERMINAL_STATUSES, true)) {
            return false;
        }

        return $this->statusRank($incomingStatus) >= $this->statusRank($currentStatus);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{product_id:int|null,product_name:string,price:int,qty:int,subtotal:int,note:?string}>
     */
    private function buildOrderItems(Store $store, array $payload): array
    {
        $rawItems = Arr::get($payload, 'items', []);
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

            $productName = trim((string) Arr::get($rawItem, 'name', 'Foodpanda Item'));
            if ($productName === '') {
                $productName = 'Foodpanda Item';
            }

            $qty = max(1, (int) Arr::get($rawItem, 'pricing.quantity', 1));
            $price = $this->resolveRoundedAmount(
                Arr::get($rawItem, 'pricing.unit_price'),
                (float) Arr::get($rawItem, 'pricing.total_price', 0) / max($qty, 1)
            );
            $subtotal = $this->resolveRoundedAmount(
                Arr::get($rawItem, 'pricing.total_price'),
                $price * $qty
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
     * @param array<string, mixed> $payload
     */
    private function buildOrderNote(array $payload): ?string
    {
        $parts = [];
        $orderCode = trim((string) Arr::get($payload, 'order_code', ''));
        $transportType = strtoupper(trim((string) Arr::get($payload, 'transport_type', '')));
        $comment = trim((string) Arr::get($payload, 'comment', ''));
        $deliveryInstructions = trim((string) Arr::get($payload, 'customer.delivery_address.instructions', ''));

        $parts[] = $orderCode !== ''
            ? 'Foodpanda #'.$orderCode
            : 'Foodpanda';

        $transportLabel = match ($transportType) {
            'VENDOR_DELIVERY' => 'Merchant Delivery',
            'LOGISTICS_DELIVERY' => 'Foodpanda Delivery',
            default => null,
        };

        if ($transportLabel !== null) {
            $parts[] = $transportLabel;
        }

        if ($comment !== '') {
            $parts[] = 'Order note: '.$comment;
        }

        if ($deliveryInstructions !== '') {
            $parts[] = 'Delivery note: '.$deliveryInstructions;
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
        $instructions = trim((string) Arr::get($rawItem, 'instructions', ''));
        $sku = trim((string) Arr::get($rawItem, 'sku', ''));

        if ($instructions !== '') {
            $parts[] = 'Note: '.$instructions;
        }

        if ($sku !== '') {
            $parts[] = 'SKU: '.$sku;
        }

        $filteredParts = array_values(array_filter($parts, fn ($value) => trim((string) $value) !== ''));

        return $filteredParts === [] ? null : implode(' | ', $filteredParts);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildOutboundItems(Order $order): array
    {
        $payloadItems = Arr::get($order->source_payload ?? [], 'items', []);

        if (! is_array($payloadItems) || $payloadItems === []) {
            throw new RuntimeException('Foodpanda order payload does not include syncable items.');
        }

        $items = [];

        foreach ($payloadItems as $payloadItem) {
            if (! is_array($payloadItem)) {
                continue;
            }

            $itemId = trim((string) Arr::get($payloadItem, '_id', ''));
            $sku = trim((string) Arr::get($payloadItem, 'sku', ''));

            if ($itemId === '' && $sku === '') {
                continue;
            }

            $pricing = array_filter([
                'pricing_type' => Arr::get($payloadItem, 'pricing.pricing_type', 'UNIT'),
                'quantity' => (int) Arr::get($payloadItem, 'pricing.quantity', 1),
                'unit_price' => Arr::get($payloadItem, 'pricing.unit_price'),
                'weight' => Arr::get($payloadItem, 'pricing.weight'),
            ], fn ($value) => $value !== null && $value !== '');

            $items[] = array_filter([
                '_id' => $itemId !== '' ? $itemId : null,
                'sku' => $sku !== '' ? $sku : null,
                'replaced_id' => Arr::get($payloadItem, 'replaced_id'),
                'pricing' => $pricing,
                'status' => Arr::get($payloadItem, 'status', 'IN_CART'),
            ], fn ($value) => $value !== null);
        }

        if ($items === []) {
            throw new RuntimeException('Foodpanda order payload does not include valid outbound items.');
        }

        return $items;
    }

    private function resolveCompletionStatus(Order $order): string
    {
        $transportType = strtoupper(trim((string) Arr::get($order->source_payload ?? [], 'transport_type', '')));

        return $transportType === 'VENDOR_DELIVERY'
            ? 'DISPATCHED'
            : 'READY_FOR_PICKUP';
    }

    private function resolveSourceOrderId(Order $order): string
    {
        $sourceOrderId = trim((string) ($order->source_order_id ?? ''));

        if ($sourceOrderId === '') {
            throw new RuntimeException('Order is missing the Foodpanda source order id.');
        }

        return $sourceOrderId;
    }

    /**
     * @param array<int, string> $cancelReasons
     */
    private function mapCancellationReason(array $cancelReasons, ?string $otherReason = null): string
    {
        $haystack = Str::lower(trim(implode(' ', array_filter([
            implode(' ', $cancelReasons),
            $otherReason,
        ]))));

        if ($haystack !== '') {
            if (str_contains($haystack, 'busy') || str_contains($haystack, 'crowd')) {
                return 'TOO_BUSY';
            }

            if (
                str_contains($haystack, 'unavailable')
                || str_contains($haystack, 'sold out')
                || str_contains($haystack, 'out of stock')
                || str_contains($haystack, 'missing item')
            ) {
                return 'ITEM_UNAVAILABLE';
            }
        }

        return 'CLOSED';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveCustomerName(array $payload): ?string
    {
        $firstName = trim((string) Arr::get($payload, 'customer.first_name', ''));
        $lastName = trim((string) Arr::get($payload, 'customer.last_name', ''));
        $fullName = trim($firstName.' '.$lastName);

        return $fullName !== '' ? $fullName : 'Foodpanda';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveCancellationNote(array $payload): string
    {
        $reason = trim((string) Arr::get($payload, 'cancellation.reason', ''));

        return $reason !== ''
            ? 'Cancelled on Foodpanda: '.$reason
            : 'Cancelled on Foodpanda';
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

    private function mapLocalOrderStatus(string $foodpandaStatus): string
    {
        return match (strtoupper(trim($foodpandaStatus))) {
            'CANCELLED' => 'cancelled',
            'DISPATCHED' => 'picked_up',
            'DELIVERED' => 'completed',
            'READY_FOR_PICKUP' => 'ready_for_pickup',
            default => 'pending',
        };
    }

    private function statusRank(string $status): int
    {
        return match (strtolower(trim($status))) {
            'pending' => 10,
            'accepted', 'confirmed', 'received' => 15,
            'preparing', 'processing', 'cooking', 'in_progress' => 20,
            'ready', 'ready_for_pickup' => 30,
            'picked_up', 'collected', 'served' => 40,
            'complete', 'completed' => 50,
            'cancelled', 'canceled' => 100,
            default => 0,
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

    private function buildLocalOrderNo(Store $store, ?string $sourceDisplayId, string $sourceOrderId): string
    {
        $displayToken = strtoupper(preg_replace('/[^A-Z0-9]+/', '', Str::upper((string) ($sourceDisplayId ?? ''))) ?? '');
        $orderToken = strtoupper(substr(preg_replace('/[^A-Z0-9]+/', '', Str::upper($sourceOrderId)) ?? '', 0, 8));
        $candidate = 'FP-'.$store->id.'-'.($displayToken !== '' ? $displayToken : $orderToken);

        if (! Order::query()->where('order_no', $candidate)->exists()) {
            return $candidate;
        }

        return Order::generateOrderNoForStore((int) $store->id);
    }

    private function resolveRoundedAmount(mixed $value, float|int $fallback = 0): int
    {
        if (is_numeric($value)) {
            return max(0, (int) round((float) $value));
        }

        return max(0, (int) round((float) $fallback));
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
