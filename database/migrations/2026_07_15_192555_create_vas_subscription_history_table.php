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
        if (Schema::connection('profiles')->hasTable('vas_subscription_history')) {
            return;
        }

        Schema::connection('profiles')->create('vas_subscription_history', function (Blueprint $table) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('profiles')->dropIfExists('vas_subscription_history');
    }
};
