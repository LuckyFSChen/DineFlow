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
        Schema::create('external_product_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('platform');
            $table->string('external_item_id');
            $table->string('external_item_name')->nullable();
            $table->string('external_category_id')->nullable();
            $table->string('external_category_name')->nullable();
            $table->integer('external_price')->nullable();
            $table->string('external_currency', 16)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('external_payload')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'platform', 'external_item_id'], 'external_product_mappings_unique');
            $table->index(['store_id', 'platform', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_product_mappings');
    }
};
