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
        $stores = Store::whereIn('slug', ['test-store', 'lucky-cafe'])->get();

        if ($stores->isEmpty()) {
            return;
        }

        foreach ($stores as $store) {
            $this->createDiningTablesForStore($store);
        }
    }

    private function createDiningTablesForStore($store){
        $tables = ["A1", "A2", "A3", "B1", "B2", "B3", "C1", "C2", "C3", "外帶"];

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
