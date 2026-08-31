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
     * Every VAS service owns its own database, so this table is created once
     * per service connection. Existing tables are left alone: in production
     * these databases are telco-owned and provisioned outside this app.
     */
    public function up(): void
    {
        foreach (ServiceStore::connections() as $connection) {
            if (Schema::connection($connection)->hasTable('profiles')) {
                continue;
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
