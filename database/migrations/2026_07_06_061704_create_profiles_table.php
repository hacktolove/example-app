<?php

use App\Support\ServiceSchema;
use App\Support\ServiceStore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every VAS service owns its own database, so this table is created once
     * per service connection. Existing tables are left alone: in production
     * these databases are telco-owned and provisioned outside this app.
     *
     * The migrator only ever runs this file once. A connection added to
     * config/vasws.php afterwards will not get its table from here — run
     * `php artisan vasws:ensure-tables` instead, which shares this same
     * per-connection, idempotent logic via App\Support\ServiceSchema.
     */
    public function up(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            ServiceSchema::ensureProfilesTable($connection);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            Schema::connection($connection)->dropIfExists('profiles');
        }
    }
};
