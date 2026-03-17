<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $main = Category::where('name', '主餐')->first();
        $side = Category::where('name', '小菜')->first();
        $drink = Category::where('name', '飲料')->first();

        if (!$main || !$side || !$drink) {
            return;
        }

        // 主餐
        Product::create([
            'store_id' => $main->store_id,
            'category_id' => $main->id,
            'name' => '滷肉飯',
            'price' => 45,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        Product::create([
            'store_id' => $main->store_id,
            'category_id' => $main->id,
            'name' => '雞腿便當',
            'price' => 120,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 小菜
        Product::create([
            'store_id' => $side->store_id,
            'category_id' => $side->id,
            'name' => '燙青菜',
            'price' => 30,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        Product::create([
            'store_id' => $side->store_id,
            'category_id' => $side->id,
            'name' => '滷蛋',
            'price' => 15,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 飲料
        Product::create([
            'store_id' => $drink->store_id,
            'category_id' => $drink->id,
            'name' => '紅茶',
            'price' => 25,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        Product::create([
            'store_id' => $drink->store_id,
            'category_id' => $drink->id,
            'name' => '奶茶',
            'price' => 35,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
    }
}
