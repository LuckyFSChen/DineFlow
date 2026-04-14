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
                        'sort' => $index + 1,
                        'is_active' => true,
                        'is_sold_out' => false,
                    ]
                );
            }
        }
    }
}
