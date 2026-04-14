<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'name' => 'Test Store',
                'description' => '測試餐廳',
                'address' => '台北市信義區',
                'phone' => '0912-345-678',
                'is_active' => 1,
            ],
            [
                'name' => 'Lucky Cafe',
                'description' => '咖啡廳',
                'address' => '台北市中山區',
                'phone' => '0922-333-444',
                'is_active' => 1,
            ],
        ];

        foreach ($stores as $data) {
            // 先產 base slug
            $baseSlug = Str::slug($data['name']);

            // 中文 fallback（很重要）
            if (empty($baseSlug)) {
                $baseSlug = 'store';
            }

            // 避免重複
            $slug = $baseSlug;
            $count = 1;

            while (Store::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            $data['slug'] = $slug;

            Store::create($data);
        }
    }
}