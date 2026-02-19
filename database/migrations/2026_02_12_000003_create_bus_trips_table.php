<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_city_id')->constrained('cities');
            $table->foreignId('to_city_id')->constrained('cities');
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->string('duration', 10)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('total_seats');
            $table->enum('bus_type', ['VIP', 'Premium', 'Standard', 'VIP Couchette', 'Classe Affaire']);
            $table->json('amenities')->nullable();
            $table->json('stops')->nullable();
            $table->boolean('recurring')->default(false);
            $table->json('recurring_days')->nullable();
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['from_city_id', 'to_city_id']);
            $table->index('departure_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_trips');
    }
};