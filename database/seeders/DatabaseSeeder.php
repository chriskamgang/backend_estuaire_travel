<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed cities first (required for bus trips)
        $this->call([
            CitySeeder::class,
            CompanySeeder::class,
            BusTripSeeder::class,
        ]);

        $this->command->info('🎉 Toutes les données de test ont été créées avec succès!');
    }
}
