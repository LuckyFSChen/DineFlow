<?php

namespace App\Support;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class StoreFakeOrderGenerator
{
    private const STATUS_PATTERN = [
        'pending',
        'preparing',
        'completed',
        'completed',
        'cancelled',
        'pending',
        'preparing',
        'completed',
    ];

    private const ORDER_TYPE_PATTERN = [
        'dine_in',
        'takeout',
        'dine_in',
        'takeout',
        'dine_in',
        'takeout',
    ];

    private const ORDER_LOCALES = ['zh_TW', 'en', 'vi', 'zh_CN'];

    private const CUSTOMER_NAMES = [
        'Test Customer 01',
        'Test Customer 02',
        'Test Customer 03',
        'Test Customer 04',
        'Test Customer 05',
        'Test Customer 06',
        'Test Customer 07',
        'Test Customer 08',
    ];

    private const ORDER_NOTES = [
        null,
        'Testing checkout flow',
        'Rush order test',
        'Please prepare carefully',
        'Created by fake order command',
        null,
    ];

    private const ITEM_NOTES = [
        null,
        'Less ice',
        'No onions',
        'Extra sauce',
        'Less sugar',
        null,
    ];

    public function generate(Store $store, int $count = 20, int $days = 7, bool $clearExisting = false): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('The order count must be at least 1.');
        }

        if ($days < 1) {
            throw new InvalidArgumentException('The number of days must be at least 1.');
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->orderBy('sort')
            ->orderBy('id')
            ->get([
                'id',
                'store_id',
                'name',
                'price',
                'allow_item_note',
            ])
            ->values();

        if ($products->isEmpty()) {
            throw new RuntimeException('The target store has no active products to build fake orders from.');
        }

        $tables = DiningTable::query()
            ->where('store_id', $store->id)
            ->where('status', '!=', 'inactive')
            ->orderBy('table_no')
            ->orderBy('id')
            ->get([
                'id',
                'store_id',
                'table_no',
            ])
            ->values();

        $seededAt = now();
        $summary = [
            'store_id' => (int) $store->id,
            'store_slug' => (string) $store->slug,
            'store_name' => (string) $store->name,
            'cleared_orders' => 0,
            'created_orders' => 0,
            'status_counts' => [],
            'order_type_counts' => [],
        ];

        DB::transaction(function () use ($store, $products, $tables, $count, $days, $clearExisting, $seededAt, &$summary): void {
            if ($clearExisting) {
                $summary['cleared_orders'] = Order::query()
                    ->where('store_id', $store->id)
                    ->count();

                Order::query()
                    ->where('store_id', $store->id)
                    ->delete();
            }

            Model::withoutEvents(function () use ($store, $products, $tables, $count, $days, $seededAt, &$summary): void {
                for ($index = 0; $index < $count; $index++) {
                    $status = self::STATUS_PATTERN[$index % count(self::STATUS_PATTERN)];
                    $orderType = $this->resolveOrderType($index, $tables);
                    $tableId = $orderType === 'dine_in'
                        ? (int) $tables[$index % $tables->count()]->id
                        : null;
                    $createdAt = $this->resolveCreatedAt($seededAt, $index, $days);
                    $updatedAt = $this->resolveUpdatedAt($createdAt, $status, $seededAt, $index);
                    $selectedProducts = $this->resolveProductsForOrder($products, $index);
                    $lineItems = $this->buildLineItems($selectedProducts, $status, $createdAt, $updatedAt, $index);
                    $subtotal = (int) collect($lineItems)->sum('subtotal');
                    $customerName = self::CUSTOMER_NAMES[$index % count(self::CUSTOMER_NAMES)];
                    $customerEmail = sprintf('fake-customer-%02d@example.test', ($index % 50) + 1);

                    $order = new Order();
                    $order->forceFill([
                        'uuid' => (string) Str::uuid(),
                        'store_id' => $store->id,
                        'member_id' => null,
                        'coupon_id' => null,
                        'dining_table_id' => $tableId,
                        'order_type' => $orderType,
                        'cart_token' => $orderType === 'takeout' ? 'fake_' . Str::lower(Str::random(12)) : null,
                        'order_no' => Order::generateOrderNoForStore((int) $store->id),
                        'status' => $status,
                        'payment_status' => $this->resolvePaymentStatus($store, $status, $index),
                        'invoice_flow' => InvoiceFlow::NONE,
                        'invoice_mobile_barcode' => null,
                        'invoice_member_carrier_code' => null,
                        'invoice_donation_code' => null,
                        'invoice_company_tax_id' => null,
                        'invoice_company_name' => null,
                        'invoice_requested_at' => null,
                        'customer_name' => $customerName,
                        'customer_phone' => $this->generateCustomerPhone($store, $index),
                        'customer_email' => $customerEmail,
                        'order_locale' => self::ORDER_LOCALES[$index % count(self::ORDER_LOCALES)],
                        'note' => self::ORDER_NOTES[$index % count(self::ORDER_NOTES)],
                        'coupon_code' => null,
                        'coupon_discount' => 0,
                        'points_used' => 0,
                        'points_earned' => 0,
                        'cancel_reason_options' => $status === 'cancelled' ? ['customer_changed_mind'] : null,
                        'cancel_reason_other' => null,
                        'subtotal' => $subtotal,
                        'total' => $subtotal,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]);
                    $order->saveQuietly();

                    foreach ($lineItems as $lineItem) {
                        $item = new OrderItem();
                        $item->forceFill(array_merge($lineItem, [
                            'order_id' => $order->id,
                        ]));
                        $item->saveQuietly();
                    }

                    $summary['created_orders']++;
                    $summary['status_counts'][$status] = (int) ($summary['status_counts'][$status] ?? 0) + 1;
                    $summary['order_type_counts'][$orderType] = (int) ($summary['order_type_counts'][$orderType] ?? 0) + 1;
                }
            });
        });

        ksort($summary['status_counts']);
        ksort($summary['order_type_counts']);

        return $summary;
    }

    private function resolveOrderType(int $index, Collection $tables): string
    {
        if ($tables->isEmpty()) {
            return 'takeout';
        }

        return self::ORDER_TYPE_PATTERN[$index % count(self::ORDER_TYPE_PATTERN)];
    }

    private function resolveCreatedAt(Carbon $seededAt, int $index, int $days): Carbon
    {
        $createdAt = $seededAt->copy()
            ->subDays($index % $days)
            ->startOfDay()
            ->addHours(10 + (($index * 3) % 11))
            ->addMinutes(($index * 7) % 60)
            ->addSeconds(($index * 13) % 60);

        if ($createdAt->greaterThan($seededAt)) {
            return $seededAt->copy()->subMinutes(($index + 1) * 9);
        }

        return $createdAt;
    }

    private function resolveUpdatedAt(Carbon $createdAt, string $status, Carbon $seededAt, int $index): Carbon
    {
        $minutesToAdd = match ($status) {
            'pending' => 5 + ($index % 10),
            'preparing' => 18 + ($index % 18),
            'completed' => 30 + ($index % 30),
            'cancelled' => 8 + ($index % 12),
            default => 10 + ($index % 15),
        };

        $updatedAt = $createdAt->copy()->addMinutes($minutesToAdd);

        if ($updatedAt->greaterThan($seededAt)) {
            return $seededAt->copy();
        }

        return $updatedAt;
    }

    private function resolveProductsForOrder(Collection $products, int $index): Collection
    {
        $maxItems = min(4, $products->count());
        $itemCount = max(1, min($products->count(), 1 + ($index % $maxItems)));
        $selected = collect();

        for ($offset = 0; $offset < $itemCount; $offset++) {
            $selected->push($products[($index + $offset) % $products->count()]);
        }

        return $selected;
    }

    private function buildLineItems(Collection $products, string $status, Carbon $createdAt, Carbon $updatedAt, int $index): array
    {
        $itemCount = $products->count();

        return $products
            ->values()
            ->map(function (Product $product, int $offset) use ($status, $createdAt, $updatedAt, $index, $itemCount): array {
                $qty = 1 + (($index + $offset) % 3);
                $price = (int) $product->price;
                $itemStatus = $this->resolveItemStatus($status, $offset);
                $completedAt = $itemStatus === 'completed'
                    ? $updatedAt->copy()->subMinutes(max(0, $itemCount - $offset - 1))
                    : null;
                $itemCreatedAt = $createdAt->copy()->addMinutes(min($offset * 2, 6));

                return [
                    'product_id' => (int) $product->id,
                    'product_name' => (string) $product->name,
                    'price' => $price,
                    'qty' => $qty,
                    'subtotal' => $price * $qty,
                    'note' => $this->resolveItemNote($product, $index, $offset),
                    'item_status' => $itemStatus,
                    'completed_at' => $completedAt,
                    'created_at' => $itemCreatedAt,
                    'updated_at' => $completedAt ?? $updatedAt,
                ];
            })
            ->all();
    }

    private function resolveItemStatus(string $status, int $offset): string
    {
        return match ($status) {
            'completed' => 'completed',
            'preparing' => $offset % 2 === 0 ? 'completed' : 'preparing',
            'pending', 'cancelled' => 'preparing',
            default => 'preparing',
        };
    }

    private function resolveItemNote(Product $product, int $index, int $offset): ?string
    {
        if (! $product->allow_item_note) {
            return null;
        }

        return self::ITEM_NOTES[($index + $offset) % count(self::ITEM_NOTES)];
    }

    private function resolvePaymentStatus(Store $store, string $status, int $index): string
    {
        if ($status === 'cancelled') {
            return 'unpaid';
        }

        if (in_array($status, ['pending', 'preparing'], true)) {
            return 'unpaid';
        }

        if ($store->isPrepayCheckout()) {
            return $index % 6 === 0 ? 'unpaid' : 'paid';
        }

        return $index % 3 === 0 ? 'unpaid' : 'paid';
    }

    private function generateCustomerPhone(Store $store, int $index): string
    {
        $length = match (strtolower((string) ($store->country_code ?? 'tw'))) {
            'cn' => 11,
            'tw', 'vn', 'us' => 10,
            default => 10,
        };

        $bodyLength = max(1, $length - 1);

        return '0' . str_pad((string) (($index + 1) % (10 ** min($bodyLength, 9))), $bodyLength, '0', STR_PAD_LEFT);
    }
}
