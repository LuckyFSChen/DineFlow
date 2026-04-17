<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo json_encode([
    'members' => Illuminate\Support\Facades\DB::table('members')->count(),
    'stores' => Illuminate\Support\Facades\DB::table('stores')->count(),
    'orders' => Illuminate\Support\Facades\DB::table('orders')->count(),
], JSON_UNESCAPED_UNICODE), PHP_EOL;
