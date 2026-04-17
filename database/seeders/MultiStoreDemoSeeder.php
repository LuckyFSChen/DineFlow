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
    private const TARGET_STORE_COUNT = 10;

    public function run(): void
    {
        $backendUsers = $this->ensureBackendUsers();
        if ($backendUsers->isEmpty()) {
            return;
        }

        $this->ensureCustomers();

        $stores = $this->ensureStores($backendUsers);
        $this->seedCatalogAndTables($stores);
    }

    private function ensureBackendUsers(): Collection
    {
        $planIds = [
            'basic-monthly' => SubscriptionPlan::query()->where('slug', 'basic-monthly')->value('id'),
            'growth-quarterly' => SubscriptionPlan::query()->where('slug', 'growth-quarterly')->value('id'),
            'pro-yearly' => SubscriptionPlan::query()->where('slug', 'pro-yearly')->value('id'),
            'growth-monthly' => SubscriptionPlan::query()->where('slug', 'growth-monthly')->value('id'),
        ];

        $seededAt = now();

        $backendProfiles = [
            [
                'email' => 'admin@dineflow.local',
                'name' => 'System Admin',
                'phone' => '0911000001',
                'role' => 'admin',
                'subscription_plan_id' => null,
                'subscription_ends_at' => $seededAt->copy()->addYears(10),
            ],
            [
                'email' => 'merchant.basic@dineflow.local',
                'name' => 'Merchant Basic',
                'phone' => '0911000002',
                'role' => 'merchant',
                'merchant_region' => 'tw',
                'subscription_plan_id' => $planIds['basic-monthly'],
                'subscription_ends_at' => $seededAt->copy()->addDays(30),
            ],
            [
                'email' => 'merchant.growth@dineflow.local',
                'name' => 'Merchant Growth',
                'phone' => '0911000003',
                'role' => 'merchant',
                'merchant_region' => 'tw',
                'subscription_plan_id' => $planIds['growth-quarterly'],
                'subscription_ends_at' => $seededAt->copy()->addDays(90),
            ],
            [
                'email' => 'merchant.pro@dineflow.local',
                'name' => 'Merchant Pro',
                'phone' => '0911000004',
                'role' => 'merchant',
                'merchant_region' => 'tw',
                'subscription_plan_id' => $planIds['pro-yearly'],
                'subscription_ends_at' => $seededAt->copy()->addDays(365),
            ],
            [
                'email' => 'merchant.plus@dineflow.local',
                'name' => 'Merchant Plus',
                'phone' => '0911000005',
                'role' => 'merchant',
                'merchant_region' => 'tw',
                'subscription_plan_id' => $planIds['growth-monthly'],
                'subscription_ends_at' => $seededAt->copy()->addDays(30),
            ],
        ];

        $users = collect();

        foreach ($backendProfiles as $profile) {
            $user = User::query()->updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'phone' => $profile['phone'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => $seededAt,
                    'role' => $profile['role'],
                    'merchant_region' => $profile['merchant_region'] ?? null,
                    'subscription_ends_at' => $profile['subscription_ends_at'],
                    'subscription_plan_id' => $profile['subscription_plan_id'],
                ]
            );

            $users->put($profile['email'], $user);
        }

        return $users;
    }

    private function ensureCustomers(): void
    {
        $seededAt = now();

        for ($i = 1; $i <= 24; $i++) {
            $email = sprintf('customer%02d@dineflow.local', $i);

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => sprintf('Customer %02d', $i),
                    'phone' => sprintf('0922%06d', $i),
                    'password' => Hash::make('password'),
                    'email_verified_at' => $seededAt,
                    'role' => 'customer',
                    'subscription_ends_at' => null,
                    'subscription_plan_id' => null,
                    'merchant_region' => null,
                    'store_id' => null,
                ]
            );
        }
    }

    private function ensureStores(Collection $backendUsers): Collection
    {
        $storeColumns = array_flip(Schema::getColumnListing('stores'));

        $storeProfiles = [
            ['name' => 'Seed Store 01', 'slug' => 'seed-store-01', 'description' => 'Seeded store #01', 'owner_email' => 'merchant.basic@dineflow.local'],
            ['name' => 'Seed Store 02', 'slug' => 'seed-store-02', 'description' => 'Seeded store #02', 'owner_email' => 'merchant.growth@dineflow.local'],
            ['name' => 'Seed Store 03', 'slug' => 'seed-store-03', 'description' => 'Seeded store #03', 'owner_email' => 'merchant.growth@dineflow.local'],
            ['name' => 'Seed Store 04', 'slug' => 'seed-store-04', 'description' => 'Seeded store #04', 'owner_email' => 'merchant.growth@dineflow.local'],
            ['name' => 'Seed Store 05', 'slug' => 'seed-store-05', 'description' => 'Seeded store #05', 'owner_email' => 'merchant.pro@dineflow.local'],
            ['name' => 'Seed Store 06', 'slug' => 'seed-store-06', 'description' => 'Seeded store #06', 'owner_email' => 'merchant.pro@dineflow.local'],
            ['name' => 'Seed Store 07', 'slug' => 'seed-store-07', 'description' => 'Seeded store #07', 'owner_email' => 'merchant.pro@dineflow.local'],
            ['name' => 'Seed Store 08', 'slug' => 'seed-store-08', 'description' => 'Seeded store #08', 'owner_email' => 'merchant.pro@dineflow.local'],
            ['name' => 'Seed Store 09', 'slug' => 'seed-store-09', 'description' => 'Seeded store #09', 'owner_email' => 'merchant.pro@dineflow.local'],
            ['name' => 'Seed Store 10', 'slug' => 'seed-store-10', 'description' => 'Seeded store #10', 'owner_email' => 'admin@dineflow.local'],
        ];

        $stores = collect();

        foreach ($storeProfiles as $index => $profile) {
            $owner = $backendUsers->get($profile['owner_email']);
            if (! $owner) {
                continue;
            }

            $store = Store::withTrashed()
                ->where('slug', $profile['slug'])
                ->first();

            $payload = [
                'user_id' => $owner->id,
                'name' => $profile['name'],
                'slug' => $profile['slug'],
                'description' => $profile['description'],
                'phone' => sprintf('0933%06d', $index + 1),
                'address' => 'Demo Address #' . ($index + 1),
                'is_active' => true,
                'takeout_qr_enabled' => true,
                'checkout_timing' => ($index % 2 === 0) ? 'postpay' : 'prepay',
                'currency' => 'twd',
                'country_code' => 'tw',
                'timezone' => 'Asia/Taipei',
                'monthly_revenue_target' => 180000 + (($index + 1) * 20000),
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

        if ($stores->count() > self::TARGET_STORE_COUNT) {
            $stores = $stores->take(self::TARGET_STORE_COUNT);
        }

        $allowedSlugs = $stores->pluck('slug')->all();
        Store::query()->whereNotIn('slug', $allowedSlugs)->where('slug', 'like', 'seed-store-%')->delete();

        User::query()
            ->whereIn('role', ['merchant', 'admin'])
            ->whereNotNull('store_id')
            ->update(['store_id' => null]);

        $firstGrowthStore = $stores->firstWhere('user_id', optional($backendUsers->get('merchant.growth@dineflow.local'))->id);
        if ($firstGrowthStore) {
            $growthOwner = $backendUsers->get('merchant.growth@dineflow.local');
            if ($growthOwner) {
                $growthOwner->forceFill(['store_id' => $firstGrowthStore->id])->saveQuietly();
            }
        }

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
