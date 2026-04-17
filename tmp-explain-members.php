<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
foreach (Illuminate\Support\Facades\DB::select("EXPLAIN SELECT id FROM members WHERE name ILIKE '%a%' LIMIT 20") as $row) {
    echo ($row->{'QUERY PLAN'} ?? ''), PHP_EOL;
}
