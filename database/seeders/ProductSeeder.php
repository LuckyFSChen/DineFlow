<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $menuData = [
            '主餐' => [
                ['name' => '招牌滷肉飯', 'price' => 45],
                ['name' => '香煎雞腿飯', 'price' => 120],
                [
                    'name' => '超值主餐套餐',
                    'price' => 199,
                    'option_groups' => [
                        [
                            'id' => 'main_choice',
                            'name' => '主餐',
                            'type' => 'single',
                            'required' => true,
                            'choices' => [
                                ['id' => 'chicken_steak', 'name' => '香煎雞腿排', 'price' => 0],
                                ['id' => 'pork_cutlet', 'name' => '酥炸豬排', 'price' => 0],
                                ['id' => 'grilled_mackerel', 'name' => '鹽烤鯖魚', 'price' => 20],
                            ],
                        ],
                        [
                            'id' => 'side_choice',
                            'name' => '附餐',
                            'type' => 'single',
                            'required' => true,
                            'choices' => [
                                ['id' => 'fries', 'name' => '薯條', 'price' => 0],
                                ['id' => 'salad', 'name' => '沙拉', 'price' => 0],
                                ['id' => 'soup', 'name' => '濃湯', 'price' => 0],
                            ],
                        ],
                        [
                            'id' => 'drink_choice',
                            'name' => '飲料',
                            'type' => 'single',
                            'required' => true,
                            'choices' => [
                                ['id' => 'black_tea', 'name' => '紅茶', 'price' => 0],
                                ['id' => 'green_tea', 'name' => '綠茶', 'price' => 0],
                                ['id' => 'milk_tea', 'name' => '奶茶', 'price' => 10],
                            ],
                        ],
                        [
                            'id' => 'addon_choice',
                            'name' => '加購',
                            'type' => 'multiple',
                            'required' => false,
                            'max_select' => 2,
                            'choices' => [
                                ['id' => 'extra_egg', 'name' => '加蛋', 'price' => 15],
                                ['id' => 'extra_cheese', 'name' => '加起司', 'price' => 20],
                                ['id' => 'extra_sauce', 'name' => '加醬', 'price' => 10],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => '牛排',
                    'price' => 260,
                    'option_groups' => [
                        [
                            'id' => 'doneness',
                            'name' => '熟度',
                            'type' => 'single',
                            'required' => true,
                            'choices' => [
                                ['id' => 'rare', 'name' => '三分熟', 'price' => 0],
                                ['id' => 'medium', 'name' => '五分熟', 'price' => 0],
                                ['id' => 'well', 'name' => '全熟', 'price' => 0],
                            ],
                        ],
                        [
                            'id' => 'extras',
                            'name' => '配料',
                            'type' => 'multiple',
                            'required' => false,
                            'max_select' => 3,
                            'choices' => [
                                ['id' => 'egg', 'name' => '加蛋', 'price' => 20],
                                ['id' => 'cheese', 'name' => '加起司', 'price' => 25],
                                ['id' => 'sauce', 'name' => '蘑菇醬', 'price' => 15],
                            ],
                        ],
                    ],
                ],
            ],
            '小菜' => [
                ['name' => '燙青菜', 'price' => 30],
                ['name' => '滷蛋', 'price' => 15],
            ],
            '飲料' => [
                ['name' => '紅茶', 'price' => 35],
                ['name' => '冬瓜茶', 'price' => 25],
                ['name' => '奶茶', 'price' => 35],
            ],
        ];

        $categories = Category::whereIn('name', array_keys($menuData))
            ->where('is_active', true)
            ->get();

        foreach ($categories as $category) {
            foreach ($menuData[$category->name] as $index => $product) {
                Product::updateOrCreate(
                    [
                        'store_id' => $category->store_id,
                        'category_id' => $category->id,
                        'name' => $product['name'],
                    ],
                    [
                        'description' => null,
                        'price' => $product['price'],
                        'option_groups' => $product['option_groups'] ?? null,
                        'sort' => $index + 1,
                        'is_active' => true,
                        'is_sold_out' => false,
                    ]
                );
            }
        }
    }
}
