<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rideshare_bookings', function (Blueprint $table) {
            // Montant mis en séquestre (prélevé du passager mais pas encore versé au chauffeur)
            $table->decimal('escrow_amount', 10, 2)->default(0)->after('total_price');
            // Indique si l'escrow a été libéré vers le chauffeur (après scan QR)
            $table->boolean('escrow_released')->default(false)->after('escrow_amount');
            // Date de libération de l'escrow
            $table->timestamp('escrow_released_at')->nullable()->after('escrow_released');
        });
    }

    public function down(): void
    {
        Schema::table('rideshare_bookings', function (Blueprint $table) {
            $table->dropColumn(['escrow_amount', 'escrow_released', 'escrow_released_at']);
        });
    }
};
