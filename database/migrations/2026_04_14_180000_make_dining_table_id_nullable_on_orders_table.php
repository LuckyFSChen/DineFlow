<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders ALTER COLUMN dining_table_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders ALTER COLUMN dining_table_id SET NOT NULL');
    }
};
