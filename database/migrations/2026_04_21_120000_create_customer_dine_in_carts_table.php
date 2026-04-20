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
        Schema::create('customer_dine_in_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dining_table_id')->constrained()->cascadeOnDelete();
            $table->json('cart_items');
            $table->timestamps();

            $table->unique(['store_id', 'dining_table_id']);
            $table->index('dining_table_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_dine_in_carts');
    }
};
