<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->index(['is_active', 'takeout_qr_enabled'], 'stores_active_takeout_idx');
            $table->index(['user_id', 'is_active'], 'stores_user_active_idx');
            $table->index('country_code', 'stores_country_code_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['store_id', 'is_active', 'sort'], 'categories_store_active_sort_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['store_id', 'is_active', 'is_sold_out', 'sort'], 'products_store_active_stock_sort_idx');
            $table->index(['category_id', 'is_active', 'is_sold_out', 'sort'], 'products_category_active_stock_sort_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['store_id', 'status', 'payment_status', 'created_at'], 'orders_store_status_payment_created_idx');
            $table->index(['store_id', 'dining_table_id', 'order_type', 'customer_name'], 'orders_store_table_type_customer_idx');
            $table->index(['store_id', 'order_type', 'created_at'], 'orders_store_type_created_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'item_status'], 'order_items_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_status_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_store_type_created_idx');
            $table->dropIndex('orders_store_table_type_customer_idx');
            $table->dropIndex('orders_store_status_payment_created_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_active_stock_sort_idx');
            $table->dropIndex('products_store_active_stock_sort_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_store_active_sort_idx');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex('stores_country_code_idx');
            $table->dropIndex('stores_user_active_idx');
            $table->dropIndex('stores_active_takeout_idx');
        });
    }
};
