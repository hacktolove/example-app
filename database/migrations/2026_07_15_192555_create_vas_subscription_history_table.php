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
     * History is stored per service, alongside that service's profiles, so
     * each service connection gets its own copy of this table.
     *
     * The migrator only ever runs this file once. A connection added to
     * config/vasws.php afterwards will not get its table from here — run
     * `php artisan vasws:ensure-tables` instead, which shares this same
     * per-connection, idempotent logic via App\Support\ServiceSchema.
     */
    public function up(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            ServiceSchema::ensureVasSubscriptionHistoryTable($connection);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            Schema::connection($connection)->dropIfExists('vas_subscription_history');
        }
    }
};
