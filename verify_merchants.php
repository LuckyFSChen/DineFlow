<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stores = App\Models\Store::query()->get();
$merchantIds = App\Models\User::query()->where('role', 'merchant')->pluck('id');
$storeMerchantIds = $stores->pluck('user_id')->filter()->unique();

echo 'stores=' . $stores->count() . PHP_EOL;
echo 'store_unique_merchants=' . $storeMerchantIds->count() . PHP_EOL;
echo 'merchant_users=' . $merchantIds->count() . PHP_EOL;
echo 'one_store_one_merchant=' . (($stores->count() === 20 && $storeMerchantIds->count() === 20) ? 'yes' : 'no') . PHP_EOL;
