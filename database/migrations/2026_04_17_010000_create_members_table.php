<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('points_balance')->default(0);
            $table->unsignedBigInteger('total_spent')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'email']);
            $table->index(['store_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

