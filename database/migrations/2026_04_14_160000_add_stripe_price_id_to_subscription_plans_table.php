<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Removed: Stripe price ID is no longer used.
    }

    public function down(): void
    {
        // No-op.
    }
};
