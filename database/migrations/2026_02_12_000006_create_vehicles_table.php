<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('brand'); // Marque (Toyota, Mercedes, etc.)
            $table->string('model');
            $table->string('year', 4);
            $table->string('color');
            $table->string('license_plate')->unique();
            $table->integer('seats'); // Nombre de places passagers
            $table->string('vehicle_type'); // sedan, suv, van, minibus, bus
            $table->string('photo')->nullable(); // Photo du véhicule
            $table->boolean('has_ac')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};