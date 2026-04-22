<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class FakeMenuSeeder
{
    public function seed(Store $store, bool $replace = false): array
    {
        return DB::transaction(function () use ($store, $replace): array {
            if ($replace) {
                Product::query()->where('store_id', $store->id)->delete();
                Category::query()->where('store_id', $store->id)->delete();
            }

            $summary = [
                'categories_created' => 0,
                'categories_updated' => 0,
                'products_created' => 0,
                'products_updated' => 0,
            ];

            foreach ($this->catalog() as $categoryIndex => $categoryData) {
                $category = Category::withTrashed()->firstOrNew([
                    'store_id' => $store->id,
                    'name' => $categoryData['name'],
                ]);

                $categoryAlreadyExists = $category->exists;
                $restoreCategory = $category->trashed();

                $category->fill([
                    'sort' => $categoryIndex + 1,
                    'prep_time_minutes' => $categoryData['prep_time_minutes'] ?? null,
                    'is_active' => true,
                ]);

                if ($restoreCategory) {
                    $category->restore();
                }

                $categoryChanged = $restoreCategory || $category->isDirty();
                $category->save();

                if (! $categoryAlreadyExists) {
                    $summary['categories_created']++;
                } elseif ($categoryChanged) {
                    $summary['categories_updated']++;
                }

                foreach ($categoryData['products'] as $productIndex => $productData) {
                    $product = Product::withTrashed()->firstOrNew([
                        'store_id' => $store->id,
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                    ]);

                    $productAlreadyExists = $product->exists;
                    $restoreProduct = $product->trashed();

                    $product->fill([
                        'sort' => $productIndex + 1,
                        'description' => $productData['description'] ?? null,
                        'price' => (int) $productData['price'],
                        'cost' => $this->resolveCost($productData),
                        'is_active' => true,
                        'is_sold_out' => false,
                        'option_groups' => $productData['option_groups'] ?? null,
                        'allow_item_note' => (bool) ($productData['allow_item_note'] ?? false),
                    ]);

                    if ($restoreProduct) {
                        $product->restore();
                    }

                    $productChanged = $restoreProduct || $product->isDirty();
                    $product->save();

                    if (! $productAlreadyExists) {
                        $summary['products_created']++;
                    } elseif ($productChanged) {
                        $summary['products_updated']++;
                    }
                }
            }

            return $summary;
        });
    }

    public function findStore(string $identifier): array
    {
        $value = trim($identifier);

        if ($value === '') {
            return [
                'store' => null,
                'error' => 'Please provide a store id, slug, exact name, or merchant email.',
            ];
        }

        $query = Store::query()->whereNull('deleted_at');

        if (ctype_digit($value)) {
            $store = (clone $query)->find((int) $value);

            if ($store instanceof Store) {
                return ['store' => $store, 'error' => null];
            }
        }

        $store = (clone $query)->where('slug', $value)->first();

        if ($store instanceof Store) {
            return ['store' => $store, 'error' => null];
        }

        $namedStores = (clone $query)
            ->where('name', $value)
            ->orderBy('id')
            ->get();

        if ($namedStores->count() === 1) {
            return [
                'store' => $namedStores->first(),
                'error' => null,
            ];
        }

        if ($namedStores->count() > 1) {
            return [
                'store' => null,
                'error' => 'Multiple stores share this name. Please use a specific store id or slug: ' . $this->storeHints($namedStores->all()),
            ];
        }

        $merchantStores = (clone $query)
            ->whereHas('owner', function ($ownerQuery) use ($value): void {
                $ownerQuery->where('email', $value);
            })
            ->orderBy('id')
            ->get();

        if ($merchantStores->count() === 1) {
            return [
                'store' => $merchantStores->first(),
                'error' => null,
            ];
        }

        if ($merchantStores->count() > 1) {
            return [
                'store' => null,
                'error' => 'Merchant has multiple stores. Please use a specific store id or slug: ' . $this->storeHints($merchantStores->all()),
            ];
        }

        return [
            'store' => null,
            'error' => sprintf('Store not found for [%s].', $value),
        ];
    }

    private function resolveCost(array $productData): int
    {
        if (array_key_exists('cost', $productData)) {
            return max(0, (int) $productData['cost']);
        }

        return max(0, (int) floor(((int) $productData['price']) * 0.42));
    }

    private function storeHints(array $stores): string
    {
        return collect($stores)
            ->map(fn (Store $store) => sprintf('#%d %s (%s)', $store->id, $store->name, $store->slug))
            ->implode(', ');
    }

    private function catalog(): array
    {
        return [
            [
                'name' => '人氣主餐',
                'prep_time_minutes' => 18,
                'products' => [
                    [
                        'name' => '炙燒雞腿飯',
                        'description' => '厚切雞腿排搭配時蔬與溏心蛋。',
                        'price' => 185,
                        'allow_item_note' => true,
                        'option_groups' => [
                            [
                                'id' => 'rice_size',
                                'name' => '飯量',
                                'type' => 'single',
                                'required' => true,
                                'choices' => [
                                    ['id' => 'regular', 'name' => '正常', 'price' => 0],
                                    ['id' => 'less', 'name' => '少飯', 'price' => 0],
                                    ['id' => 'extra', 'name' => '加飯', 'price' => 15],
                                ],
                            ],
                            [
                                'id' => 'extras',
                                'name' => '加點',
                                'type' => 'multiple',
                                'required' => false,
                                'max_select' => 2,
                                'choices' => [
                                    ['id' => 'egg', 'name' => '溏心蛋', 'price' => 20],
                                    ['id' => 'kimchi', 'name' => '泡菜', 'price' => 15],
                                    ['id' => 'broccoli', 'name' => '青花菜', 'price' => 20],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '椒鹽松阪豬飯',
                        'description' => '帶有脆口油脂香氣的松阪豬定食。',
                        'price' => 210,
                        'allow_item_note' => true,
                    ],
                    [
                        'name' => '蒜香牛排飯',
                        'description' => '蒜片牛排搭配奶油時蔬與特製醬汁。',
                        'price' => 260,
                        'allow_item_note' => true,
                        'option_groups' => [
                            [
                                'id' => 'doneness',
                                'name' => '熟度',
                                'type' => 'single',
                                'required' => true,
                                'choices' => [
                                    ['id' => 'medium_rare', 'name' => '五分熟', 'price' => 0],
                                    ['id' => 'medium', 'name' => '七分熟', 'price' => 0],
                                    ['id' => 'well_done', 'name' => '全熟', 'price' => 0],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '味噌鮭魚飯',
                        'description' => '鹹香味噌醬搭配煎烤鮭魚。',
                        'price' => 235,
                        'allow_item_note' => true,
                    ],
                ],
            ],
            [
                'name' => '麵食熱湯',
                'prep_time_minutes' => 14,
                'products' => [
                    [
                        'name' => '胡麻冷麵',
                        'description' => '清爽胡麻醬搭配溫泉蛋與蔬菜絲。',
                        'price' => 145,
                        'allow_item_note' => true,
                    ],
                    [
                        'name' => '豚骨拉麵',
                        'description' => '濃郁豚骨湯底，附叉燒與糖心蛋。',
                        'price' => 190,
                        'allow_item_note' => true,
                        'option_groups' => [
                            [
                                'id' => 'noodle_texture',
                                'name' => '麵體硬度',
                                'type' => 'single',
                                'required' => true,
                                'choices' => [
                                    ['id' => 'soft', 'name' => '偏軟', 'price' => 0],
                                    ['id' => 'regular', 'name' => '正常', 'price' => 0],
                                    ['id' => 'firm', 'name' => '偏硬', 'price' => 0],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '番茄海鮮義大利麵',
                        'description' => '番茄基底搭配蝦仁與花枝圈。',
                        'price' => 225,
                        'allow_item_note' => true,
                    ],
                    [
                        'name' => '奶油野菇濃湯',
                        'description' => '適合單點或搭配主餐的熱湯。',
                        'price' => 75,
                    ],
                ],
            ],
            [
                'name' => '小點炸物',
                'prep_time_minutes' => 9,
                'products' => [
                    [
                        'name' => '黃金脆薯',
                        'description' => '現炸馬鈴薯條，外酥內綿。',
                        'price' => 65,
                        'option_groups' => [
                            [
                                'id' => 'dip',
                                'name' => '沾醬',
                                'type' => 'single',
                                'required' => true,
                                'choices' => [
                                    ['id' => 'ketchup', 'name' => '番茄醬', 'price' => 0],
                                    ['id' => 'mustard', 'name' => '蜂蜜芥末', 'price' => 0],
                                    ['id' => 'truffle', 'name' => '松露美乃滋', 'price' => 15],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '唐揚雞',
                        'description' => '日式醃製雞腿肉，附檸檬角。',
                        'price' => 95,
                    ],
                    [
                        'name' => '炸豆腐',
                        'description' => '外酥內嫩，搭配柴魚醬汁。',
                        'price' => 70,
                    ],
                    [
                        'name' => '酥炸洋蔥圈',
                        'description' => '搭配店家特調胡椒粉。',
                        'price' => 80,
                    ],
                ],
            ],
            [
                'name' => '手搖飲品',
                'prep_time_minutes' => 4,
                'products' => [
                    [
                        'name' => '熟成紅茶',
                        'description' => '帶麥芽尾韻的經典紅茶。',
                        'price' => 35,
                        'option_groups' => [
                            [
                                'id' => 'ice_level',
                                'name' => '冰量',
                                'type' => 'single',
                                'required' => true,
                                'choices' => [
                                    ['id' => 'normal', 'name' => '正常冰', 'price' => 0],
                                    ['id' => 'less', 'name' => '少冰', 'price' => 0],
                                    ['id' => 'free', 'name' => '去冰', 'price' => 0],
                                ],
                            ],
                            [
                                'id' => 'sugar_level',
                                'name' => '甜度',
                                'type' => 'single',
                                'required' => true,
                                'choices' => [
                                    ['id' => 'full', 'name' => '全糖', 'price' => 0],
                                    ['id' => 'half', 'name' => '半糖', 'price' => 0],
                                    ['id' => 'light', 'name' => '微糖', 'price' => 0],
                                    ['id' => 'none', 'name' => '無糖', 'price' => 0],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '烏龍奶茶',
                        'description' => '焙香烏龍茶與鮮奶比例調和。',
                        'price' => 60,
                        'option_groups' => [
                            [
                                'id' => 'toppings',
                                'name' => '配料',
                                'type' => 'multiple',
                                'required' => false,
                                'max_select' => 2,
                                'choices' => [
                                    ['id' => 'boba', 'name' => '珍珠', 'price' => 10],
                                    ['id' => 'grass_jelly', 'name' => '仙草', 'price' => 10],
                                    ['id' => 'pudding', 'name' => '布丁', 'price' => 15],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '蜂蜜檸檬',
                        'description' => '酸甜平衡，夏天很受歡迎。',
                        'price' => 55,
                    ],
                    [
                        'name' => '氣泡柚子飲',
                        'description' => '微氣泡口感，適合搭配炸物。',
                        'price' => 70,
                    ],
                ],
            ],
            [
                'name' => '甜點',
                'prep_time_minutes' => 6,
                'products' => [
                    [
                        'name' => '焦糖布丁',
                        'description' => '滑順布丁搭配微苦焦糖。',
                        'price' => 55,
                    ],
                    [
                        'name' => '巴斯克乳酪蛋糕',
                        'description' => '表面焦香，內層濕潤綿密。',
                        'price' => 95,
                    ],
                    [
                        'name' => '抹茶紅豆鬆餅',
                        'description' => '現烤鬆餅搭配紅豆與鮮奶油。',
                        'price' => 120,
                        'allow_item_note' => true,
                    ],
                    [
                        'name' => '香草冰淇淋',
                        'description' => '餐後小甜點，份量剛好。',
                        'price' => 45,
                    ],
                ],
            ],
        ];
    }
}
