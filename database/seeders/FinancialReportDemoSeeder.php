<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialReportDemoSeeder extends Seeder
{
    private Carbon $seededAt;

    public function run(): void
    {
        $this->seededAt = now()->copy();

        $stores = Store::query()->get();
        if ($stores->isEmpty()) {
            return;
        }

        $customers = User::query()
            ->where('role', 'customer')
            ->where('email', 'like', 'customer%@dineflow.local')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'phone']);

        if ($customers->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($stores, $customers): void {
            // Re-seed friendly: clear existing orders for merchant stores first.
            $storeIds = $stores->pluck('id')->all();
            $orderIds = Order::query()->whereIn('store_id', $storeIds)->pluck('id')->all();

            if (! empty($orderIds)) {
                OrderItem::query()->whereIn('order_id', $orderIds)->delete();
            }
            Order::query()->whereIn('store_id', $storeIds)->delete();

            foreach ($stores as $store) {
                $this->seedStoreOrders($store, $customers);
            }
        });
    }

    private function seedStoreOrders(Store $store, $customers): void
    {
        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        $tableIds = DiningTable::query()
            ->where('store_id', $store->id)
            ->get(['id', 'table_no'])
            ->filter(function ($table) {
                $tableNo = strtolower(trim((string) $table->table_no));
                return ! in_array($tableNo, ['外帶', 'takeout', 'take_out'], true);
            })
            ->pluck('id')
            ->values();

        $startDay = $this->seededAt->copy()->subDays(44)->startOfDay();

        for ($d = 0; $d < 45; $d++) {
            $day = $startDay->copy()->addDays($d);
            $orderCount = random_int(6, 18);

            for ($i = 0; $i < $orderCount; $i++) {
                $customer = $customers->random();
                $orderType = random_int(1, 100) <= 55 ? 'takeout' : 'dine_in';
                $statusRoll = random_int(1, 100);

                $status = match (true) {
                    $statusRoll <= 10 => 'cancelled',
                    $statusRoll <= 25 => 'pending',
                    $statusRoll <= 55 => 'preparing',
                    default => 'completed',
                };

                $pickedProducts = $products->shuffle()->take(random_int(1, min(5, $products->count())))->values();

                $subtotal = 0;
                $rows = [];

                foreach ($pickedProducts as $product) {
                    $qty = random_int(1, 3);
                    $price = (int) $product->price;
                    $lineSubtotal = $price * $qty;

                    $rows[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'price' => $price,
                        'qty' => $qty,
                        'subtotal' => $lineSubtotal,
                        'note' => null,
                    ];

                    $subtotal += $lineSubtotal;
                }

                $createdAt = $day->copy()->setTime(random_int(10, 21), random_int(0, 59), random_int(0, 59));
                $tableId = $orderType === 'dine_in' && $tableIds->isNotEmpty()
                    ? $tableIds->random()
                    : null;

                $paymentStatus = $this->resolvePaymentStatus($store->checkout_timing, $status);

                $order = Order::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'store_id' => $store->id,
                    'dining_table_id' => $tableId,
                    'order_type' => $orderType,
                    'cart_token' => $orderType === 'takeout' ? 'demo_' . bin2hex(random_bytes(6)) : null,
                    'order_no' => Order::generateOrderNoForStore((int) $store->id),
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'customer_name' => (string) $customer->name,
                    'customer_phone' => (string) ($customer->getRawOriginal('phone') ?? ''),
                    'customer_email' => (string) $customer->email,
                    'order_locale' => collect(['zh_TW', 'zh_CN', 'en', 'vi'])->random(),
                    'note' => null,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                ]);

                foreach ($rows as $row) {
                    $row['order_id'] = $order->id;
                    $item = OrderItem::query()->create($row);
                    $item->forceFill([
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ])->saveQuietly();
                }

                $updatedAt = $this->resolveUpdatedAt($createdAt, $status);
                if ($updatedAt->greaterThan($this->seededAt)) {
                    $updatedAt = $this->seededAt->copy();
                }

                $order->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ])->saveQuietly();
            }
        }
    }

    private function resolvePaymentStatus(string $checkoutTiming, string $status): string
    {
        $normalizedStatus = strtolower($status);
        $normalizedCheckoutTiming = strtolower($checkoutTiming);

        if ($normalizedStatus === 'cancelled') {
            return 'unpaid';
        }

        if ($normalizedCheckoutTiming === 'prepay') {
            return random_int(1, 100) <= 95 ? 'paid' : 'unpaid';
        }

        if (in_array($normalizedStatus, ['completed'], true)) {
            return random_int(1, 100) <= 70 ? 'paid' : 'unpaid';
        }

        return 'unpaid';
    }

    private function resolveUpdatedAt(Carbon $createdAt, string $status): Carbon
    {
        $minutesToAdd = match (strtolower($status)) {
            'pending' => random_int(3, 15),
            'preparing' => random_int(15, 35),
            'completed' => random_int(35, 70),
            'cancelled' => random_int(5, 25),
            default => random_int(5, 30),
        };

        return $createdAt->copy()->addMinutes($minutesToAdd);
    }
}
