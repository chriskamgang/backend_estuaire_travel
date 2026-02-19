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
        Schema::create('driver_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->string('month', 7);        // Format: "2026-02" (année-mois)
            $table->decimal('amount', 10, 2)->default(1000.00);
            $table->string('currency', 10)->default('FCFA');
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            // Un chauffeur ne peut payer qu'une seule fois par mois
            $table->unique(['driver_id', 'month']);
            $table->index(['driver_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_subscriptions');
    }
};
