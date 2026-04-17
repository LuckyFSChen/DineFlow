<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('CREATE INDEX IF NOT EXISTS members_name_trgm_idx ON members USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS members_email_trgm_idx ON members USING gin (email gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS members_phone_trgm_idx ON members USING gin (phone gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS stores_name_trgm_idx ON stores USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS stores_slug_trgm_idx ON stores USING gin (slug gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS stores_description_trgm_idx ON stores USING gin (description gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS stores_address_trgm_idx ON stores USING gin (address gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS stores_phone_trgm_idx ON stores USING gin (phone gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS stores_phone_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS stores_address_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS stores_description_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS stores_slug_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS stores_name_trgm_idx');

        DB::statement('DROP INDEX IF EXISTS members_phone_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS members_email_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS members_name_trgm_idx');
    }
};
