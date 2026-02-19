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
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique(); // Expo Push Token (ex: ExponentPushToken[xxxxx])
            $table->string('device_type')->nullable(); // ios, android
            $table->string('device_id')->nullable(); // Identifiant unique de l'appareil
            $table->boolean('active')->default(true); // Permet de désactiver sans supprimer
            $table->timestamp('last_used_at')->nullable(); // Dernière utilisation
            $table->timestamps();

            // Index pour rechercher rapidement les tokens d'un utilisateur
            $table->index(['user_id', 'active']);
            // Index unique pour éviter les doublons token+user
            $table->unique(['user_id', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};
