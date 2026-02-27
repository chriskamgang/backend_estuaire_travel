<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rideshare_trips', function (Blueprint $table) {
            // Nom du garant du chauffeur
            $table->string('guarantor_name')->nullable()->after('additional_notes');
            // Numéro WhatsApp du garant
            $table->string('guarantor_phone')->nullable()->after('guarantor_name');
            // Indique si le garant a été notifié par WhatsApp
            $table->boolean('guarantor_notified')->default(false)->after('guarantor_phone');
        });
    }

    public function down(): void
    {
        Schema::table('rideshare_trips', function (Blueprint $table) {
            $table->dropColumn(['guarantor_name', 'guarantor_phone', 'guarantor_notified']);
        });
    }
};
