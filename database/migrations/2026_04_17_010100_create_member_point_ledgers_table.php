<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_point_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points_change');
            $table->integer('balance_after');
            $table->string('type', 32);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'member_id', 'created_at']);
            $table->index(['store_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_point_ledgers');
    }
};

