<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->string('invoice_number', 16)->nullable();
            $table->string('random_number', 8)->nullable();
            $table->string('invoice_flow', 32)->default('none');
            $table->string('carrier_type', 32)->nullable();
            $table->string('carrier_code', 64)->nullable();
            $table->string('donation_code', 16)->nullable();
            $table->string('company_tax_id', 16)->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedInteger('issue_attempts')->default(0);
            $table->unsignedInteger('void_attempts')->default(0);
            $table->string('upload_status', 24)->default('pending');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('legal_deadline_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('qr_code_url')->nullable();
            $table->string('pdf_url')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'upload_status']);
            $table->index(['legal_deadline_at']);
            $table->index(['invoice_flow']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_invoices');
    }
};

