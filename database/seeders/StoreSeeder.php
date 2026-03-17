<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::create([
            'name' => 'Test Store',
            'phone' => '1234567890',
            'address' => '123 Test Street',
            'contact_email' => 'test@example.com',
            'notification_email' => 'notify@example.com',
            'is_active' => true,
        ]);
    }
}
