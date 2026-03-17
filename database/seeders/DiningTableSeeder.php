<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DiningTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::first();

        if (!$store) {
            return;
        }

        $tables = ["A1", "A2", "A3", "B1", "B2", "B3", "C1", "C2", "C3"];

        foreach ($tables as $tableNo) {
            DiningTable::create([
                'store_id' => $store->id,
                'table_no' => $tableNo,
                'qr_token' => Str::uuid(),
                'status' => 'available',
            ]);
        }
    }
}
