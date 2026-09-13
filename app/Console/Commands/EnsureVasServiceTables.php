<?php

namespace App\Console\Commands;

use App\Support\ServiceSchema;
use App\Support\ServiceStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Catches up any VAS service connection whose tables the migrator can no
 * longer create.
 *
 * `php artisan migrate` only ever runs a given migration file once, ever.
 * A connection added to config/vasws.php after the `profiles` and
 * `vas_subscription_history` migrations already ran (the common case: they
 * ran at initial setup, a service gets added months later) never gets its
 * tables from `migrate` alone, and fails at request time with "relation
 * ... does not exist" instead of at deploy time. Run this after adding a
 * service, or any time as a safe no-op check — it shares the same
 * per-connection, idempotent logic as those migrations.
 */
#[Signature('vasws:ensure-tables')]
#[Description('Create any missing profiles/vas_subscription_history tables on every configured VAS service connection')]
class EnsureVasServiceTables extends Command
{
    public function handle(): int
    {
        foreach (ServiceStore::all() as $service) {
            $created = array_filter([
                ServiceSchema::ensureProfilesTable($service->connection) ? 'profiles' : null,
                ServiceSchema::ensureVasSubscriptionHistoryTable($service->connection) ? 'vas_subscription_history' : null,
            ]);

            if ($created === []) {
                $this->components->twoColumnDetail($service->connection, 'up to date');

                continue;
            }

            $this->components->twoColumnDetail($service->connection, 'created '.implode(', ', $created));
        }

        return self::SUCCESS;
    }
}
