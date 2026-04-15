<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MultiStoreDemoSeeder extends Seeder
{
    private const TARGET_MERCHANT_COUNT = 20;

    public function run(): void
    {
        $merchants = $this->ensureMerchants();
        if ($merchants->isEmpty()) {
            return;
        }

        $stores = $this->ensureStores($merchants);
        $this->seedCatalogAndTables($stores);
    }

    private function ensureMerchants(): Collection
    {
        $growthPlanId = SubscriptionPlan::query()
            ->where('slug', 'growth-monthly')
            ->value('id');

        $emails = ['merchant@dineflow.local'];
        for ($i = 2; $i <= self::TARGET_MERCHANT_COUNT; $i++) {
            $emails[] = sprintf('merchant%02d@dineflow.local', $i);
        }

        $merchants = collect();

        foreach ($emails as $index => $email) {
            $displayIndex = $index + 1;

            $merchant = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => sprintf('Merchant %02d', $displayIndex),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role' => 'merchant',
                    'subscription_ends_at' => now()->addMonths(2),
                    'subscription_plan_id' => $growthPlanId,
                    'merchant_region' => 'tw',
                ]
            );

            $merchants->push($merchant);
        }

        return $merchants->values();
    }

    private function ensureStores(Collection $merchants): Collection
    {
        $storeColumns = array_flip(Schema::getColumnListing('stores'));

        $storeProfiles = [
            ['name' => 'Test Store', 'slug' => 'test-store', 'description' => 'Seeded test store'],
            ['name' => 'Lucky Cafe', 'slug' => 'lucky-cafe', 'description' => 'Seeded lucky cafe'],
        ];

        for ($i = 3; $i <= self::TARGET_MERCHANT_COUNT; $i++) {
            $storeProfiles[] = [
                'name' => sprintf('Demo Store %02d', $i),
                'slug' => sprintf('demo-store-%02d', $i),
                'description' => 'Seeded demo store for local development.',
            ];
        }

        $stores = collect();

        foreach ($storeProfiles as $index => $profile) {
            $merchant = $merchants[$index] ?? null;
            if (! $merchant) {
                continue;
            }

            $store = Store::withTrashed()
                ->where('slug', $profile['slug'])
                ->first();

            $payload = [
                'user_id' => $merchant->id,
                'name' => $profile['name'],
                'slug' => $profile['slug'],
                'description' => $profile['description'],
                'phone' => '09' . random_int(10000000, 99999999),
                'address' => 'Demo Address #' . ($index + 1),
                'is_active' => true,
                'takeout_qr_enabled' => true,
                'checkout_timing' => random_int(0, 1) === 1 ? 'postpay' : 'prepay',
                'currency' => 'twd',
                'country_code' => 'tw',
                'timezone' => 'Asia/Taipei',
                'monthly_revenue_target' => random_int(150000, 500000),
            ];
            $payload = array_intersect_key($payload, $storeColumns);

            if ($store) {
                $store->fill($payload);
                $store->save();

                if ($store->trashed()) {
                    $store->restore();
                }
            } else {
                $store = Store::query()->create($payload);
            }

            $stores->push($store->fresh());
        }

        $allowedSlugs = collect($storeProfiles)->pluck('slug')->all();
        Store::query()
            ->whereIn('user_id', $merchants->pluck('id'))
            ->whereNotIn('slug', $allowedSlugs)
            ->get()
            ->each(function (Store $store): void {
                $store->delete();
            });

        return $stores->values();
    }

    private function seedCatalogAndTables(Collection $stores): void
    {
        $categoryNames = [
            1 => 'Main',
            2 => 'Snack',
            3 => 'Drink',
            4 => 'Dessert',
        ];

        $productMap = [
            'Main' => [
                ['name' => 'Grilled Chicken Rice', 'price' => 135],
                ['name' => 'Pork Cutlet Bento', 'price' => 145],
                ['name' => 'Braised Beef Noodle', 'price' => 165],
                ['name' => 'Salmon Teriyaki Set', 'price' => 199],
            ],
            'Snack' => [
                ['name' => 'French Fries', 'price' => 55],
                ['name' => 'Onion Rings', 'price' => 65],
                ['name' => 'Chicken Nuggets', 'price' => 75],
            ],
            'Drink' => [
                ['name' => 'Black Tea', 'price' => 35],
                ['name' => 'Green Tea', 'price' => 35],
                ['name' => 'Milk Tea', 'price' => 45],
                ['name' => 'Americano', 'price' => 60],
            ],
            'Dessert' => [
                ['name' => 'Pudding', 'price' => 50],
                ['name' => 'Cheesecake', 'price' => 85],
                ['name' => 'Ice Cream', 'price' => 70],
            ],
        ];

        foreach ($stores as $store) {
            $categoryIdMap = [];

            foreach ($categoryNames as $sort => $categoryName) {
                $category = Category::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'name' => $categoryName,
                    ],
                    [
                        'sort' => $sort,
                        'is_active' => true,
                    ]
                );

                $categoryIdMap[$categoryName] = $category->id;
            }

            foreach ($productMap as $categoryName => $products) {
                $categoryId = $categoryIdMap[$categoryName] ?? null;
                if (! $categoryId) {
                    continue;
                }

                foreach ($products as $index => $product) {
                    Product::query()->updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'category_id' => $categoryId,
                            'name' => $product['name'],
                        ],
                        [
                            'description' => 'Seeded demo product',
                            'price' => $product['price'],
                            'cost' => (int) floor($product['price'] * 0.55),
                            'sort' => $index + 1,
                            'is_active' => true,
                            'is_sold_out' => false,
                            'allow_item_note' => true,
                        ]
                    );
                }
            }

            $tables = ['A1', 'A2', 'A3', 'A4', 'B1', 'B2', 'B3', 'B4', 'C1', 'C2'];
            foreach ($tables as $tableNo) {
                $table = DiningTable::withTrashed()->firstOrNew([
                    'store_id' => $store->id,
                    'table_no' => $tableNo,
                ]);

                if (! $table->exists || blank($table->qr_token)) {
                    $table->qr_token = (string) Str::uuid();
                }

                $table->status = 'available';
                $table->save();

                if ($table->trashed()) {
                    $table->restore();
                }
            }

            // Keep demo data predictable: remove legacy/special tables (e.g. "外帶")
            DiningTable::query()
                ->where('store_id', $store->id)
                ->whereNotIn('table_no', $tables)
                ->get()
                ->each(function (DiningTable $table): void {
                    $table->delete();
                });
        }
    }
}
