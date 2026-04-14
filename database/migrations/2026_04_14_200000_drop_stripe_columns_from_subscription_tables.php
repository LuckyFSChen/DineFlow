<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'stripe_customer_id') || Schema::hasColumn('users', 'stripe_subscription_id')) {
            Schema::table('users', function (Blueprint $table) {
                $drops = [];

                if (Schema::hasColumn('users', 'stripe_customer_id')) {
                    $drops[] = 'stripe_customer_id';
                }
                if (Schema::hasColumn('users', 'stripe_subscription_id')) {
                    $drops[] = 'stripe_subscription_id';
                }

                if (! empty($drops)) {
                    $table->dropColumn($drops);
                }
            });
        }

        if (Schema::hasColumn('subscription_plans', 'stripe_price_id')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('stripe_price_id');
            });
        }

        $stripeColumns = [
            'stripe_event_id',
            'stripe_checkout_session_id',
            'stripe_subscription_id',
            'stripe_invoice_id',
            'stripe_payment_intent_id',
        ];

        $existingStripeColumns = array_values(array_filter($stripeColumns, fn (string $column) => Schema::hasColumn('subscription_payments', $column)));
        if (! empty($existingStripeColumns)) {
            Schema::table('subscription_payments', function (Blueprint $table) use ($existingStripeColumns) {
                $table->dropColumn($existingStripeColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'stripe_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_customer_id')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'stripe_subscription_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_subscription_id')->nullable();
            });
        }

        if (! Schema::hasColumn('subscription_plans', 'stripe_price_id')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->string('stripe_price_id')->nullable();
            });
        }

        if (! Schema::hasColumn('subscription_payments', 'stripe_event_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('stripe_event_id')->nullable();
            });
        }

        if (! Schema::hasColumn('subscription_payments', 'stripe_checkout_session_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('stripe_checkout_session_id')->nullable();
            });
        }

        if (! Schema::hasColumn('subscription_payments', 'stripe_subscription_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('stripe_subscription_id')->nullable();
            });
        }

        if (! Schema::hasColumn('subscription_payments', 'stripe_invoice_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('stripe_invoice_id')->nullable();
            });
        }

        if (! Schema::hasColumn('subscription_payments', 'stripe_payment_intent_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('stripe_payment_intent_id')->nullable();
            });
        }
    }
};
