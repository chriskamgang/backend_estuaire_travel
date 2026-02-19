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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->string('group')->default('general'); // general, loyalty, payment, etc.
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            [
                'key' => 'points_per_trip',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Nombre de points gagnés par voyage complété',
                'group' => 'loyalty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'bronze_threshold',
                'value' => '0',
                'type' => 'integer',
                'description' => 'Points requis pour le statut Bronze',
                'group' => 'loyalty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'silver_threshold',
                'value' => '500',
                'type' => 'integer',
                'description' => 'Points requis pour le statut Silver',
                'group' => 'loyalty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'gold_threshold',
                'value' => '1000',
                'type' => 'integer',
                'description' => 'Points requis pour le statut Gold',
                'group' => 'loyalty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'platinum_threshold',
                'value' => '2000',
                'type' => 'integer',
                'description' => 'Points requis pour le statut Platinum',
                'group' => 'loyalty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
