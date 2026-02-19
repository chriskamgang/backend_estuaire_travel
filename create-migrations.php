<?php
/**
 * Script pour créer toutes les migrations d'Estuaire Travel
 * Exécuter: php create-migrations.php
 */

// Migration bus_trips
file_put_contents('database/migrations/2026_02_12_175430_create_bus_trips_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_city_id')->constrained('cities');
            $table->foreignId('to_city_id')->constrained('cities');
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->string('duration', 10)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('total_seats');
            $table->enum('bus_type', ['VIP', 'Premium', 'Standard', 'VIP Couchette', 'Classe Affaire']);
            $table->json('amenities')->nullable();
            $table->json('stops')->nullable();
            $table->boolean('recurring')->default(false);
            $table->json('recurring_days')->nullable();
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['from_city_id', 'to_city_id']);
            $table->index('departure_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_trips');
    }
};
PHP
);

// Migration bookings
file_put_contents('database/migrations/2026_02_12_175431_create_bookings_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_trip_id')->constrained()->cascadeOnDelete();
            $table->string('booking_reference')->unique();
            $table->date('travel_date');
            $table->json('seats'); // array of seat numbers
            $table->integer('number_of_seats');
            $table->decimal('total_price', 10, 2);
            $table->string('passenger_name');
            $table->string('passenger_phone', 20);
            $table->string('passenger_email')->nullable();
            $table->enum('payment_method', ['Orange Money', 'MTN Mobile Money', 'Carte bancaire']);
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->boolean('used_free_trip')->default(false);
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('booking_reference');
            $table->index('user_id');
            $table->index('travel_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
PHP
);

// Migration tickets
file_put_contents('database/migrations/2026_02_12_175431_create_tickets_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_id')->unique();
            $table->string('qr_code_data', 1000);
            $table->string('signature');
            $table->string('seat_number');
            $table->enum('status', ['valid', 'used', 'cancelled', 'expired'])->default('valid');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
PHP
);

// Migration vehicles
file_put_contents('database/migrations/2026_02_12_175431_create_vehicles_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('make'); // Marque (Toyota, Mercedes, etc.)
            $table->string('model');
            $table->string('year', 4);
            $table->string('color');
            $table->string('license_plate')->unique();
            $table->integer('total_seats');
            $table->json('photos')->nullable();
            $table->boolean('has_ac')->default(true);
            $table->enum('status', ['active', 'inactive', 'pending_verification'])->default('pending_verification');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
PHP
);

// Migration rideshare_trips
file_put_contents('database/migrations/2026_02_12_175432_create_rideshare_trips_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rideshare_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('from_city');
            $table->string('to_city');

            // GPS OBLIGATOIRE - Point de départ
            $table->decimal('departure_latitude', 10, 8);
            $table->decimal('departure_longitude', 11, 8);
            $table->string('departure_address')->nullable();

            // GPS OBLIGATOIRE - Point d'arrivée
            $table->decimal('arrival_latitude', 10, 8);
            $table->decimal('arrival_longitude', 11, 8);
            $table->string('arrival_address')->nullable();

            $table->string('departure_point')->nullable();
            $table->string('arrival_point')->nullable();

            $table->date('date');
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->string('duration', 10);

            $table->decimal('price_per_seat', 10, 2);
            $table->integer('total_seats');
            $table->integer('available_seats');

            $table->json('stops')->nullable();
            $table->json('preferences')->nullable();
            $table->text('additional_notes')->nullable();
            $table->boolean('instant')->default(false);
            $table->boolean('recurring')->default(false);
            $table->json('recurring_days')->nullable();

            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');

            $table->timestamps();

            // Index pour recherche GPS nearby
            $table->index(['departure_latitude', 'departure_longitude'], 'idx_departure_location');
            $table->index('date');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rideshare_trips');
    }
};
PHP
);

echo "✅ Toutes les migrations ont été créées avec succès!\n";
echo "Exécutez maintenant: php artisan migrate\n";
