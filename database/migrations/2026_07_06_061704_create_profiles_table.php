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
        if (Schema::connection('profiles')->hasTable('profiles')) {
            return;
        }

        Schema::connection('profiles')->create('profiles', function (Blueprint $table) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('profiles')->dropIfExists('profiles');
    }
};
