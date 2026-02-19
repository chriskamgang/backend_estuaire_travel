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
            $table->decimal('last_latitude', 10, 7)->nullable()->after('avatar');
            $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            $table->timestamp('last_location_update')->nullable()->after('last_longitude');
            $table->boolean('is_online')->default(false)->after('last_location_update');

            // Champs spécifiques chauffeur
            $table->string('driver_license_number')->nullable()->after('is_driver');
            $table->date('driver_license_expiry')->nullable()->after('driver_license_number');
            $table->enum('driver_status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending')->after('driver_license_expiry');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_latitude',
                'last_longitude',
                'last_location_update',
                'is_online',
                'driver_license_number',
                'driver_license_expiry',
                'driver_status',
                'phone_verified_at'
            ]);
        });
    }
};
