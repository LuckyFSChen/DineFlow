<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('merchant_user_id')->constrained('users')->cascadeOnDelete();

            $table->text('store_names_snapshot')->nullable();

            $table->foreignId('old_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('old_plan_name')->nullable();
            $table->string('old_status', 20);
            $table->timestamp('old_subscription_ends_at')->nullable();

            $table->foreignId('new_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('new_plan_name')->nullable();
            $table->string('new_status', 20);
            $table->timestamp('new_subscription_ends_at')->nullable();

            $table->string('action', 20);
            $table->timestamps();

            $table->index(['merchant_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_change_logs');
    }
};
