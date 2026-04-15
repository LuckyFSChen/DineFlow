<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('country_code', 2)->default('tw')->after('currency');
        });

        DB::statement("UPDATE stores SET country_code = CASE LOWER(COALESCE(currency, 'twd')) WHEN 'vnd' THEN 'vn' WHEN 'cny' THEN 'cn' WHEN 'usd' THEN 'us' ELSE 'tw' END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
    }
};