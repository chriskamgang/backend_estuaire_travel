<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('currency', 10)->default('FCFA');
            $table->boolean('is_active')->default(true);
            $table->boolean('transfer_used')->default(false); // transfert unique
            $table->timestamps();

            $table->unique('user_id'); // un seul wallet par utilisateur
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
