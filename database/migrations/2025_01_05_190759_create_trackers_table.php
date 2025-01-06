<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * carrier
     * tracking number
     * data
     * current status
     * status date
     *
     */
    public function up(): void
    {
        Schema::create('trackers', function (Blueprint $table) {
            $table->id();
            $table->string('carrier');
            $table->string('tracking_number');
            $table->string('reference_id')->nullable();
            $table->string('reference_name')->nullable();
            $table->json('reference_data')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('status_time')->nullable();
            $table->dateTime('delivery_date')->nullable();
            $table->dateTime('delivered_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackers');
    }
};
