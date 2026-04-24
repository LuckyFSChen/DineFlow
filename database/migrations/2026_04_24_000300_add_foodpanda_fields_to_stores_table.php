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
            $table->boolean('foodpanda_enabled')->default(false)->after('uber_eats_store_id');
            $table->string('foodpanda_chain_id')->nullable()->after('foodpanda_enabled');
            $table->string('foodpanda_store_id')->nullable()->after('foodpanda_chain_id');
            $table->string('foodpanda_external_partner_config_id')->nullable()->after('foodpanda_store_id');
            $table->string('foodpanda_client_id')->nullable()->after('foodpanda_external_partner_config_id');
            $table->text('foodpanda_client_secret')->nullable()->after('foodpanda_client_id');
            $table->text('foodpanda_webhook_secret')->nullable()->after('foodpanda_client_secret');

            $table->index(['foodpanda_chain_id', 'foodpanda_store_id'], 'stores_foodpanda_chain_store_index');
            $table->index(['foodpanda_chain_id', 'foodpanda_external_partner_config_id'], 'stores_foodpanda_chain_external_config_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropIndex('stores_foodpanda_chain_store_index');
            $table->dropIndex('stores_foodpanda_chain_external_config_index');

            $table->dropColumn([
                'foodpanda_enabled',
                'foodpanda_chain_id',
                'foodpanda_store_id',
                'foodpanda_external_partner_config_id',
                'foodpanda_client_id',
                'foodpanda_client_secret',
                'foodpanda_webhook_secret',
            ]);
        });
    }
};
