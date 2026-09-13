<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the two tables every VAS service database needs, safely re-runnable
 * per connection.
 *
 * Both the `profiles`/`vas_subscription_history` migrations and
 * `vasws:ensure-tables` call these. The migrator only ever executes a
 * migration file once, so a connection added to config/vasws.php after those
 * files already ran (see ServiceIsolationTest) never gets its tables from
 * `migrate` alone — the command exists to catch it up. Both paths skip a
 * table that already exists, so re-running either is always safe.
 */
final class ServiceSchema
{
    /**
     * @return bool True when the table was created; false when it already existed.
     */
    public static function ensureProfilesTable(string $connection): bool
    {
        if (Schema::connection($connection)->hasTable('profiles')) {
            return false;
        }

        Schema::connection($connection)->create('profiles', function (Blueprint $table) {
            $table->string('msisdn', 16)->primary();
            $table->string('package', 8)->nullable();
            $table->string('language', 8)->nullable();
            $table->string('channel', 8)->nullable();
            $table->smallInteger('status')->nullable();
            $table->date('subs_date')->nullable();
            $table->time('subs_time')->nullable();
            $table->date('last_update_date')->nullable();
            $table->time('last_update_time')->nullable();
            $table->date('last_charge_date')->nullable();
            $table->time('last_charge_time')->nullable();
        });

        return true;
    }

    /**
     * @return bool True when the table was created; false when it already existed.
     */
    public static function ensureVasSubscriptionHistoryTable(string $connection): bool
    {
        if (Schema::connection($connection)->hasTable('vas_subscription_history')) {
            return false;
        }

        Schema::connection($connection)->create('vas_subscription_history', function (Blueprint $table) {
            $table->id();
            $table->string('mdn', 16);
            $table->string('package', 8);
            $table->dateTime('subscribed_at');
            $table->string('subscribed_channel', 8)->nullable();
            $table->dateTime('unsubscribed_at');
            $table->string('unsubscribed_channel', 8)->nullable();

            $table->index('mdn');
            $table->index('package');
        });

        return true;
    }
}
