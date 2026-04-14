<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'order_locale')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('order_locale', 10)->default('zh_TW');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'order_locale')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('order_locale');
            });
        }
    }
};
