<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            CREATE INDEX IF NOT EXISTS orders_reporting_active_idx
            ON orders (store_id, created_at)
            WHERE status IS NULL OR LOWER(status) NOT IN ('cancel', 'cancelled', 'canceled')
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS orders_reporting_order_type_idx
            ON orders (store_id, created_at, LOWER(order_type))
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS orders_reporting_order_type_idx');
        DB::statement('DROP INDEX IF EXISTS orders_reporting_active_idx');
    }
};
