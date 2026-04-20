<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('allow_dine_in')->default(true)->after('ends_at');
            $table->boolean('allow_takeout')->default(true)->after('allow_dine_in');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['allow_dine_in', 'allow_takeout']);
        });
    }
};
