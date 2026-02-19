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
        Schema::create('driver_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Documents d'identité
            $table->string('id_card_front')->nullable(); // Carte d'identité recto
            $table->string('id_card_back')->nullable();  // Carte d'identité verso

            // Permis de conduire
            $table->string('driver_license_front')->nullable(); // Permis recto
            $table->string('driver_license_back')->nullable();  // Permis verso

            // Informations supplémentaires
            $table->string('license_number')->nullable(); // Numéro du permis
            $table->date('license_expiry_date')->nullable(); // Date d'expiration
            $table->text('additional_info')->nullable(); // Infos complémentaires

            // Statut de la demande
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable(); // Raison du rejet
            $table->timestamp('submitted_at')->nullable(); // Date de soumission
            $table->timestamp('reviewed_at')->nullable(); // Date de révision
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // Admin qui a révisé

            $table->timestamps();

            // Index
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_applications');
    }
};
