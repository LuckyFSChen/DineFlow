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
        Schema::table('stores', function (Blueprint $table): void {
            $table->boolean('uber_eats_enabled')->default(false)->after('takeout_qr_enabled');
            $table->string('uber_eats_store_id')->nullable()->after('uber_eats_enabled');

            $table->unique('uber_eats_store_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('source_platform')->nullable()->after('order_locale');
            $table->string('source_order_id')->nullable()->after('source_platform');
            $table->string('source_store_id')->nullable()->after('source_order_id');
            $table->string('source_display_id')->nullable()->after('source_store_id');
            $table->timestamp('platform_ordered_at')->nullable()->after('source_display_id');
            $table->json('source_payload')->nullable()->after('platform_ordered_at');

            $table->index(['source_platform', 'source_order_id']);
            $table->unique(['source_platform', 'source_order_id'], 'orders_source_platform_order_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('orders_source_platform_order_id_unique');
            $table->dropIndex(['source_platform', 'source_order_id']);

            $table->dropColumn([
                'source_platform',
                'source_order_id',
                'source_store_id',
                'source_display_id',
                'platform_ordered_at',
                'source_payload',
            ]);
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropUnique(['uber_eats_store_id']);
            $table->dropColumn([
                'uber_eats_enabled',
                'uber_eats_store_id',
            ]);
        });
    }
};
