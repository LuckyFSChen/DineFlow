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
        $columns = array_values(array_filter([
            Schema::hasColumn('stores', 'uber_eats_client_id') ? 'uber_eats_client_id' : null,
            Schema::hasColumn('stores', 'uber_eats_client_secret') ? 'uber_eats_client_secret' : null,
            Schema::hasColumn('stores', 'uber_eats_webhook_signing_key') ? 'uber_eats_webhook_signing_key' : null,
        ]));

        if ($columns !== []) {
            Schema::table('stores', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            if (! Schema::hasColumn('stores', 'uber_eats_client_id')) {
                $table->string('uber_eats_client_id')->nullable()->after('uber_eats_store_url');
            }

            if (! Schema::hasColumn('stores', 'uber_eats_client_secret')) {
                $table->text('uber_eats_client_secret')->nullable()->after('uber_eats_client_id');
            }

            if (! Schema::hasColumn('stores', 'uber_eats_webhook_signing_key')) {
                $table->text('uber_eats_webhook_signing_key')->nullable()->after('uber_eats_client_secret');
            }
        });
    }
};
