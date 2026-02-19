<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_trip_id')->constrained()->cascadeOnDelete();
            $table->string('booking_reference')->unique();
            $table->date('travel_date');
            $table->json('seats'); // array of seat numbers
            $table->integer('number_of_seats');
            $table->decimal('total_price', 10, 2);
            $table->string('passenger_name');
            $table->string('passenger_phone', 20);
            $table->string('passenger_email')->nullable();
            $table->enum('payment_method', ['Orange Money', 'MTN Mobile Money', 'Carte bancaire']);
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->boolean('used_free_trip')->default(false);
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('booking_reference');
            $table->index('user_id');
            $table->index('travel_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};