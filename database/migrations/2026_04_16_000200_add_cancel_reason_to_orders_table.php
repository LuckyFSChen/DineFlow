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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'cancel_reason_options')) {
                $table->json('cancel_reason_options')->nullable()->after('note');
            }

            if (! Schema::hasColumn('orders', 'cancel_reason_other')) {
                $table->text('cancel_reason_other')->nullable()->after('cancel_reason_options');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cancel_reason_other')) {
                $table->dropColumn('cancel_reason_other');
            }

            if (Schema::hasColumn('orders', 'cancel_reason_options')) {
                $table->dropColumn('cancel_reason_options');
            }
        });
    }
};
