<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->index(['store_id', 'last_order_at', 'id'], 'members_store_last_order_idx');
            $table->index(['store_id', 'created_at'], 'members_store_created_idx');
            $table->index(['store_id', 'total_orders'], 'members_store_total_orders_idx');
            $table->index(['store_id', 'total_spent'], 'members_store_total_spent_idx');
        });

        Schema::table('member_point_ledgers', function (Blueprint $table) {
            $table->index(['store_id', 'created_at', 'points_change'], 'member_points_store_created_change_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['store_id', 'member_id', 'created_at', 'id'], 'orders_store_member_created_idx');
            $table->index(['store_id', 'coupon_id', 'created_at'], 'orders_store_coupon_created_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'product_id'], 'order_items_order_product_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_product_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_store_coupon_created_idx');
            $table->dropIndex('orders_store_member_created_idx');
        });

        Schema::table('member_point_ledgers', function (Blueprint $table) {
            $table->dropIndex('member_points_store_created_change_idx');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('members_store_total_spent_idx');
            $table->dropIndex('members_store_total_orders_idx');
            $table->dropIndex('members_store_created_idx');
            $table->dropIndex('members_store_last_order_idx');
        });
    }
};
