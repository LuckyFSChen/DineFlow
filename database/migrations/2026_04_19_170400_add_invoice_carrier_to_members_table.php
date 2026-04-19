<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'invoice_carrier_code')) {
                $table->string('invoice_carrier_code', 64)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('members', 'invoice_carrier_bound_at')) {
                $table->timestamp('invoice_carrier_bound_at')->nullable()->after('invoice_carrier_code');
            }

            $table->index(['store_id', 'invoice_carrier_code'], 'members_store_invoice_carrier_idx');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex('members_store_invoice_carrier_idx');

            if (Schema::hasColumn('members', 'invoice_carrier_bound_at')) {
                $table->dropColumn('invoice_carrier_bound_at');
            }

            if (Schema::hasColumn('members', 'invoice_carrier_code')) {
                $table->dropColumn('invoice_carrier_code');
            }
        });
    }
};

