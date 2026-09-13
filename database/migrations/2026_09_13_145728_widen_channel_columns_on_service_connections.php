<?php

use App\Support\ServiceStore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `channel` now records the originating interface by name (e.g. "Online
     * Ads", "API", "CCS") rather than a short fixed code, so the varchar(8)
     * columns are widened to fit. Existing connections only: a connection
     * added after this file has already run gets its tables from
     * `ServiceSchema` directly (see App\Support\ServiceSchema), which already
     * creates them at the new width — see `vasws:ensure-tables`.
     */
    public function up(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            Schema::connection($connection)->table('profiles', function (Blueprint $table) {
                $table->string('channel', 20)->nullable()->change();
            });

            Schema::connection($connection)->table('vas_subscription_history', function (Blueprint $table) {
                $table->string('subscribed_channel', 20)->nullable()->change();
                $table->string('unsubscribed_channel', 20)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            Schema::connection($connection)->table('profiles', function (Blueprint $table) {
                $table->string('channel', 8)->nullable()->change();
            });

            Schema::connection($connection)->table('vas_subscription_history', function (Blueprint $table) {
                $table->string('subscribed_channel', 8)->nullable()->change();
                $table->string('unsubscribed_channel', 8)->nullable()->change();
            });
        }
    }
};
