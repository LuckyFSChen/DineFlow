<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_invoice_allowances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_invoice_id')->constrained('store_invoices')->cascadeOnDelete();
            $table->string('status', 24)->default('pending');
            $table->string('allowance_number', 20)->nullable();
            $table->unsignedInteger('amount');
            $table->string('reason')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('upload_status', 24)->default('pending');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('legal_deadline_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_invoice_id', 'status']);
            $table->index(['legal_deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_invoice_allowances');
    }
};

