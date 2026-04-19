<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'invoice_flow')) {
                $table->string('invoice_flow', 32)->default('none')->after('payment_status');
            }

            if (! Schema::hasColumn('orders', 'invoice_mobile_barcode')) {
                $table->string('invoice_mobile_barcode', 64)->nullable()->after('invoice_flow');
            }

            if (! Schema::hasColumn('orders', 'invoice_member_carrier_code')) {
                $table->string('invoice_member_carrier_code', 64)->nullable()->after('invoice_mobile_barcode');
            }

            if (! Schema::hasColumn('orders', 'invoice_donation_code')) {
                $table->string('invoice_donation_code', 16)->nullable()->after('invoice_member_carrier_code');
            }

            if (! Schema::hasColumn('orders', 'invoice_company_tax_id')) {
                $table->string('invoice_company_tax_id', 16)->nullable()->after('invoice_donation_code');
            }

            if (! Schema::hasColumn('orders', 'invoice_company_name')) {
                $table->string('invoice_company_name')->nullable()->after('invoice_company_tax_id');
            }

            if (! Schema::hasColumn('orders', 'invoice_requested_at')) {
                $table->timestamp('invoice_requested_at')->nullable()->after('invoice_company_name');
            }

            $table->index(['store_id', 'payment_status', 'invoice_flow'], 'orders_store_payment_invoice_flow_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_store_payment_invoice_flow_idx');

            if (Schema::hasColumn('orders', 'invoice_requested_at')) {
                $table->dropColumn('invoice_requested_at');
            }

            if (Schema::hasColumn('orders', 'invoice_company_name')) {
                $table->dropColumn('invoice_company_name');
            }

            if (Schema::hasColumn('orders', 'invoice_company_tax_id')) {
                $table->dropColumn('invoice_company_tax_id');
            }

            if (Schema::hasColumn('orders', 'invoice_donation_code')) {
                $table->dropColumn('invoice_donation_code');
            }

            if (Schema::hasColumn('orders', 'invoice_member_carrier_code')) {
                $table->dropColumn('invoice_member_carrier_code');
            }

            if (Schema::hasColumn('orders', 'invoice_mobile_barcode')) {
                $table->dropColumn('invoice_mobile_barcode');
            }

            if (Schema::hasColumn('orders', 'invoice_flow')) {
                $table->dropColumn('invoice_flow');
            }
        });
    }
};

