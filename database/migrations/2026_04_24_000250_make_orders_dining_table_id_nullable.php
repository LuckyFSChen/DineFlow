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
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['dining_table_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('dining_table_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('dining_table_id')
                ->references('id')
                ->on('dining_tables')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['dining_table_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('dining_table_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('dining_table_id')
                ->references('id')
                ->on('dining_tables')
                ->cascadeOnDelete();
        });
    }
};
