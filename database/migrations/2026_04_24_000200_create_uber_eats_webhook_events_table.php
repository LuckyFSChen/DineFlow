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
        Schema::create('uber_eats_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('uber_store_id')->nullable();
            $table->string('uber_order_id')->nullable();
            $table->foreignId('local_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('status')->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['uber_store_id', 'uber_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uber_eats_webhook_events');
    }
};
