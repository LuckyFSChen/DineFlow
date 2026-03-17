<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::firstOrCreate([
            'name' => 'DineFlow Demo Store'
        ]);

        if (!$store) {
            return;
        }

        $categories = [
            ['name' => '主餐', 'sort' => 1],
            ['name' => '小菜', 'sort' => 2],
            ['name' => '飲料', 'sort' => 3],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'store_id' => $store->id,
                'name' => $cat['name'],
                'sort' => $cat['sort'],
                'is_active' => true,
            ]);
        }
    }
}
