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
        Schema::table('trackers', function (Blueprint $table) {
            $table->string('service_type')->nullable()->after('carrier');
            $table->string('service_code')->nullable()->after('service_type');
            $table->date('ship_date')->nullable()->after('service_code');
            $table->date('expected_delivery_date')->nullable()->after('ship_date');
            $table->string('origin_zip', 10)->nullable()->after('origin');
            $table->string('destination_zip', 10)->nullable()->after('destination');

            $table->index(['user_id', 'status', 'expected_delivery_date'], 'trackers_user_status_expected_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trackers', function (Blueprint $table) {
            $table->dropIndex('trackers_user_status_expected_idx');
            $table->dropColumn([
                'service_type',
                'service_code',
                'ship_date',
                'expected_delivery_date',
                'origin_zip',
                'destination_zip',
            ]);
        });
    }
};
