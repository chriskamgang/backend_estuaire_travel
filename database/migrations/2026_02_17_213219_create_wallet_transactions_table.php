<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Type : recharge | debit | transfer | refund | subscription
            $table->enum('type', ['recharge', 'debit', 'transfer', 'refund', 'subscription']);

            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            // Description lisible
            $table->string('label')->nullable();
            $table->text('description')->nullable();

            // Paiement Freemopay
            $table->string('payment_method')->nullable(); // mtn | orange | card
            $table->string('freemopay_reference')->nullable()->unique();
            $table->string('external_id')->nullable()->unique();
            $table->enum('payment_status', ['pending', 'success', 'failed'])->default('pending');

            // Référence vers une réservation (si débit)
            $table->string('bookable_type')->nullable();
            $table->unsignedBigInteger('bookable_id')->nullable();

            // Transfert destinataire
            $table->string('transfer_to_phone')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
