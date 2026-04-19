<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_invoice_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('onboarding_status', 24)->default('not_started');
            $table->unsignedTinyInteger('wizard_step')->default(1);
            $table->boolean('eligible_for_invoice')->default(false);
            $table->string('provider_mode', 24)->nullable();
            $table->string('provider_name')->nullable();
            $table->string('tax_id', 16)->nullable();
            $table->string('company_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('company_address')->nullable();
            $table->text('credential_notes')->nullable();
            $table->json('credential_files')->nullable();
            $table->string('invoice_track_prefix', 8)->nullable();
            $table->unsignedInteger('invoice_track_start')->nullable();
            $table->unsignedInteger('invoice_track_end')->nullable();
            $table->unsignedInteger('next_invoice_no')->nullable();
            $table->string('store_no', 16)->nullable();
            $table->string('machine_no', 16)->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_invoice_no', 16)->nullable();
            $table->timestamp('blank_tracks_uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['onboarding_status', 'eligible_for_invoice']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_invoice_settings');
    }
};

