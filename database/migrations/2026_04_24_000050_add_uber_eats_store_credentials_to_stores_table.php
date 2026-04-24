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
            $table->string('uber_eats_client_id')->nullable()->after('uber_eats_store_id');
            $table->text('uber_eats_client_secret')->nullable()->after('uber_eats_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn([
                'uber_eats_client_id',
                'uber_eats_client_secret',
            ]);
        });
    }
};
