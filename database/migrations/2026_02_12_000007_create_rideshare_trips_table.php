<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rideshare_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('from_city');
            $table->string('to_city');

            // GPS OBLIGATOIRE - Point de départ
            $table->decimal('departure_latitude', 10, 8);
            $table->decimal('departure_longitude', 11, 8);
            $table->string('departure_address')->nullable();

            // GPS OBLIGATOIRE - Point d'arrivée
            $table->decimal('arrival_latitude', 10, 8);
            $table->decimal('arrival_longitude', 11, 8);
            $table->string('arrival_address')->nullable();

            $table->string('departure_point')->nullable();
            $table->string('arrival_point')->nullable();

            $table->date('date');
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->string('duration', 10);

            $table->decimal('price_per_seat', 10, 2);
            $table->integer('total_seats');
            $table->integer('available_seats');

            $table->json('stops')->nullable();
            $table->json('preferences')->nullable();
            $table->text('additional_notes')->nullable();
            $table->boolean('instant')->default(false);
            $table->boolean('recurring')->default(false);
            $table->json('recurring_days')->nullable();

            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');

            $table->timestamps();

            // Index pour recherche GPS nearby
            $table->index(['departure_latitude', 'departure_longitude'], 'idx_departure_location');
            $table->index('date');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rideshare_trips');
    }
};