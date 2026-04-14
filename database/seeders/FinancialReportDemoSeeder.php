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

class FinancialReportDemoSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = User::query()->where('email', 'merchant@dineflow.local')->first();
        if (! $merchant) {
            return;
        }

        $stores = Store::query()->where('user_id', $merchant->id)->get();
        if ($stores->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($stores): void {
            // Re-seed friendly: clear existing orders for merchant stores first.
            $storeIds = $stores->pluck('id')->all();
            $orderIds = Order::query()->whereIn('store_id', $storeIds)->pluck('id')->all();

            if (! empty($orderIds)) {
                OrderItem::query()->whereIn('order_id', $orderIds)->delete();
            }
            Order::query()->whereIn('store_id', $storeIds)->delete();

            foreach ($stores as $store) {
                $this->seedStoreOrders($store);
            }
        });
    }

    private function seedStoreOrders(Store $store): void
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
            ->pluck('id')
            ->values();

        $startDay = now()->subDays(44)->startOfDay();

        for ($d = 0; $d < 45; $d++) {
            $day = $startDay->copy()->addDays($d);
            $orderCount = random_int(4, 16);

            for ($i = 0; $i < $orderCount; $i++) {
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

                $order = Order::query()->create([
                    'store_id' => $store->id,
                    'dining_table_id' => $tableId,
                    'order_type' => $orderType,
                    'cart_token' => $orderType === 'takeout' ? 'demo_' . bin2hex(random_bytes(6)) : null,
                    'order_no' => sprintf(
                        'DF%sS%sD%sI%s%s',
                        $day->format('ymd'),
                        $store->id,
                        str_pad((string) $d, 2, '0', STR_PAD_LEFT),
                        str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                        strtoupper(bin2hex(random_bytes(2)))
                    ),
                    'status' => $status,
                    'customer_name' => '測試顧客' . random_int(1, 99),
                    'customer_phone' => '09' . random_int(10000000, 99999999),
                    'customer_email' => null,
                    'note' => null,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                ]);

                foreach ($rows as $row) {
                    $row['order_id'] = $order->id;
                    OrderItem::query()->create($row);
                }

                $order->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();
            }
        }
    }
}
