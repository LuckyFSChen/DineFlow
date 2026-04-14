<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscription_payments', 'ecpay_merchant_trade_no')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('ecpay_merchant_trade_no')->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('subscription_payments', 'ecpay_trade_no')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('ecpay_trade_no')->nullable()->index();
            });
        }

        if (! Schema::hasColumn('subscription_payments', 'ecpay_payment_type')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('ecpay_payment_type')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn(['ecpay_payment_type', 'ecpay_trade_no', 'ecpay_merchant_trade_no']);
        });
    }
};
