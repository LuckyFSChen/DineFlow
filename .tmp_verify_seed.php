<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$merchant = App\Models\User::query()->where('email', 'merchant@dineflow.local')->first();
if (! $merchant) {
    echo "merchant=0\n";
    exit;
}

$storeIds = App\Models\Store::query()->where('user_id', $merchant->id)->pluck('id');

echo 'stores=' . $storeIds->count() . PHP_EOL;
echo 'categories=' . App\Models\Category::query()->whereIn('store_id', $storeIds)->count() . PHP_EOL;
echo 'products=' . App\Models\Product::query()->whereIn('store_id', $storeIds)->count() . PHP_EOL;
echo 'tables=' . App\Models\DiningTable::query()->whereIn('store_id', $storeIds)->count() . PHP_EOL;
echo 'orders=' . App\Models\Order::query()->whereIn('store_id', $storeIds)->count() . PHP_EOL;
