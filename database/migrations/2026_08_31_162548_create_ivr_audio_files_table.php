<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IVR prompts are this application's own asset, not telco subscriber
     * state, so they live on the app's database rather than in any of the
     * telco-owned service databases (see docs/adr/0001 and 0003).
     */
    public function up(): void
    {
        Schema::create('ivr_audio_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('service_id');
            $table->string('original_name');
            $table->string('filename');
            $table->unsignedInteger('position');
            $table->unsignedInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ivr_audio_files');
    }
};
