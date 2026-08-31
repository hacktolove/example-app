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
     * History is stored per service, alongside that service's profiles, so
     * each service connection gets its own copy of this table.
     */
    public function up(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            if (Schema::connection($connection)->hasTable('vas_subscription_history')) {
                continue;
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
