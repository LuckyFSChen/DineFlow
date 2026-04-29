<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscription_plans', 'nav_features')) {
                $table->json('nav_features')->nullable()->after('features');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            if (Schema::hasColumn('subscription_plans', 'nav_features')) {
                $table->dropColumn('nav_features');
            }
        });
    }
};
