<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Étape 1: Ajouter 'Classique' à l'enum temporairement
        DB::statement("ALTER TABLE bus_trips MODIFY COLUMN bus_type ENUM('VIP','Premium','Standard','VIP Couchette','Classe Affaire','Classique') NOT NULL DEFAULT 'Standard'");

        // Étape 2: Convertir tous les 'Standard' en 'Classique'
        DB::table('bus_trips')
            ->where('bus_type', 'Standard')
            ->update(['bus_type' => 'Classique']);

        // Étape 3: Maintenant modifier l'enum pour avoir seulement VIP et Classique
        DB::statement("ALTER TABLE bus_trips MODIFY COLUMN bus_type ENUM('VIP', 'Classique') NOT NULL DEFAULT 'Classique'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer l'ancien enum
        DB::statement("ALTER TABLE bus_trips MODIFY COLUMN bus_type ENUM('VIP','Premium','Standard','VIP Couchette','Classe Affaire') NOT NULL DEFAULT 'Standard'");
    }
};
