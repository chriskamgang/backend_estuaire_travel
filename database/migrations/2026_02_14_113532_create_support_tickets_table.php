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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ticket_number')->unique(); // ST-XXXXXXXX
            $table->string('subject');
            $table->text('message');
            $table->string('category'); // technique, reservation, paiement, autre
            $table->string('priority')->default('medium'); // low, medium, high
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->text('admin_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Index pour les recherches rapides
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
