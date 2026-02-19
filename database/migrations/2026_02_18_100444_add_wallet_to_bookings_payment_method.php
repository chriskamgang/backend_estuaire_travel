<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modifier l'ENUM pour ajouter 'wallet' et 'paypal' comme méthodes de paiement
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_method ENUM(
            'Orange Money',
            'MTN Mobile Money',
            'Carte bancaire',
            'wallet',
            'paypal'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_method ENUM(
            'Orange Money',
            'MTN Mobile Money',
            'Carte bancaire'
        ) NOT NULL");
    }
};
