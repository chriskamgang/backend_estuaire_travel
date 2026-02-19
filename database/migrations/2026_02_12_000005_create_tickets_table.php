<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_id')->unique();
            $table->string('qr_code_data', 1000);
            $table->string('signature');
            $table->string('seat_number');
            $table->enum('status', ['valid', 'used', 'cancelled', 'expired'])->default('valid');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};