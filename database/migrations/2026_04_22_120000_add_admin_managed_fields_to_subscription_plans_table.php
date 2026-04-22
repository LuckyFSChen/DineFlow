<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('category')->nullable()->after('slug');
            $table->unsignedInteger('discount_twd')->default(0)->after('price_twd');
            $table->text('description')->nullable()->after('features');
        });

        DB::table('subscription_plans')
            ->select(['id', 'slug'])
            ->orderBy('id')
            ->get()
            ->each(function (object $plan): void {
                $fallbackCategory = (string) strtok((string) $plan->slug, '-');

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'category' => $fallbackCategory !== '' ? $fallbackCategory : 'general',
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['category', 'discount_twd', 'description']);
        });
    }
};
