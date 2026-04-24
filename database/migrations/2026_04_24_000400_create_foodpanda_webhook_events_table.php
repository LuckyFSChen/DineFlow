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
        Schema::create('foodpanda_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('foodpanda_store_id')->nullable();
            $table->string('foodpanda_order_id')->nullable();
            $table->foreignId('local_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('status')->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['foodpanda_store_id', 'foodpanda_order_id'], 'foodpanda_webhook_events_store_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foodpanda_webhook_events');
    }
};
