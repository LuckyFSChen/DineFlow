<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }
};
