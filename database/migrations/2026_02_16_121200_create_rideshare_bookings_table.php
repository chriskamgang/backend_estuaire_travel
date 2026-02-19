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
        Schema::create('rideshare_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('rideshare_trips')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('seats_requested')->default(1);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('pickup_location')->nullable();
            $table->text('dropoff_location')->nullable();
            $table->text('special_requests')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('trip_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rideshare_bookings');
    }
};
