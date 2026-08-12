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
        Schema::table('paiement_taxes', function (Blueprint $table) {
            $table->decimal('gps_latitude', 10, 8)->nullable()->after('signature_client');
            $table->decimal('gps_longitude', 11, 8)->nullable()->after('gps_latitude');
            $table->decimal('gps_accuracy', 8, 2)->nullable()->after('gps_longitude');
            $table->decimal('gps_altitude', 8, 2)->nullable()->after('gps_accuracy');
            $table->timestamp('gps_date_capture')->nullable()->after('gps_altitude');
            $table->string('periode', 50)->nullable()->after('gps_date_capture');
            $table->string('device_id', 100)->nullable()->after('periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiement_taxes', function (Blueprint $table) {
            $table->dropColumn([
                'gps_latitude',
                'gps_longitude',
                'gps_accuracy',
                'gps_altitude',
                'gps_date_capture',
                'periode',
                'device_id',
            ]);
        });
    }
};
