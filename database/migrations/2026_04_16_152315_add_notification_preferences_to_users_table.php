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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('late_shipment_notifications_enabled')->default(false);
            $table->string('late_shipment_notifications_frequency')->default('daily');
            $table->boolean('late_shipment_report_enabled')->default(false);
            $table->string('late_shipment_report_frequency')->default('daily');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'late_shipment_notifications_enabled',
                'late_shipment_notifications_frequency',
                'late_shipment_report_enabled',
                'late_shipment_report_frequency',
            ]);
        });
    }
};
