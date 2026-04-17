<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->after('member_id')->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->integer('coupon_discount')->default(0)->after('coupon_code');
            $table->unsignedInteger('points_used')->default(0)->after('coupon_discount');
            $table->unsignedInteger('points_earned')->default(0)->after('points_used');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'coupon_discount', 'points_used', 'points_earned']);
        });
    }
};

