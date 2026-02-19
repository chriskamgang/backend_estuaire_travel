<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusTripSeeder extends Seeder
{
    /**
     * Trajets de bus entre les principales villes du Cameroun
     */
    public function run(): void
    {
        $trips = [
            // Yaoundé -> Douala (VIP et Standard)
            [
                'company_id' => 1, // Touristique Express
                'from_city_id' => 1, // Yaoundé
                'to_city_id' => 12, // Douala
                'departure_time' => '06:00:00',
                'arrival_time' => '09:30:00',
                'duration' => '3h30',
                'price' => 5000,
                'total_seats' => 40,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi', 'Charging ports', 'Snacks']),
                'stops' => json_encode(['Edéa']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
                'status' => 'active',
            ],
            [
                'company_id' => 2, // Guaranti Express
                'from_city_id' => 1, // Yaoundé
                'to_city_id' => 12, // Douala
                'departure_time' => '22:00:00',
                'arrival_time' => '05:00:00',
                'duration' => '7h',
                'price' => 3500,
                'total_seats' => 50,
                'bus_type' => 'Classique',
                'amenities' => json_encode(['AC']),
                'stops' => json_encode(['Edéa', 'Dibombari']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
                'status' => 'active',
            ],

            // Douala -> Yaoundé
            [
                'company_id' => 1, // Touristique Express
                'from_city_id' => 12, // Douala
                'to_city_id' => 1, // Yaoundé
                'departure_time' => '14:00:00',
                'arrival_time' => '17:30:00',
                'duration' => '3h30',
                'price' => 5000,
                'total_seats' => 40,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi', 'Charging ports', 'Snacks']),
                'stops' => json_encode(['Edéa']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
                'status' => 'active',
            ],

            // Yaoundé -> Bafoussam
            [
                'company_id' => 3, // Général Express
                'from_city_id' => 1, // Yaoundé
                'to_city_id' => 21, // Bafoussam
                'departure_time' => '07:00:00',
                'arrival_time' => '12:00:00',
                'duration' => '5h',
                'price' => 6000,
                'total_seats' => 35,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi', 'Reclining seats', 'Entertainment']),
                'stops' => json_encode(['Bafia', 'Bangangté']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'wednesday', 'friday', 'sunday']),
                'status' => 'active',
            ],

            // Douala -> Bamenda
            [
                'company_id' => 5, // Amour Mezam
                'from_city_id' => 12, // Douala
                'to_city_id' => 41, // Bamenda
                'departure_time' => '08:00:00',
                'arrival_time' => '14:00:00',
                'duration' => '6h',
                'price' => 7000,
                'total_seats' => 40,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi', 'Charging ports']),
                'stops' => json_encode(['Nkongsamba', 'Bafoussam']),
                'recurring' => true,
                'recurring_days' => json_encode(['tuesday', 'thursday', 'saturday']),
                'status' => 'active',
            ],

            // Yaoundé -> Ngaoundéré
            [
                'company_id' => 4, // Central Voyages
                'from_city_id' => 1, // Yaoundé
                'to_city_id' => 63, // Ngaoundéré
                'departure_time' => '18:00:00',
                'arrival_time' => '06:00:00',
                'duration' => '12h',
                'price' => 10000,
                'total_seats' => 45,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'Beds', 'WiFi', 'Meals included']),
                'stops' => json_encode(['Bafia', 'Nanga Eboko', 'Bertoua']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'wednesday', 'friday']),
                'status' => 'active',
            ],

            // Yaoundé -> Buea
            [
                'company_id' => 6, // Binam Voyages
                'from_city_id' => 1, // Yaoundé
                'to_city_id' => 34, // Buea
                'departure_time' => '09:00:00',
                'arrival_time' => '14:00:00',
                'duration' => '5h',
                'price' => 6500,
                'total_seats' => 38,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi', 'Refreshments']),
                'stops' => json_encode(['Edéa', 'Douala']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'status' => 'active',
            ],

            // Douala -> Kribi
            [
                'company_id' => 8, // Musango Express
                'from_city_id' => 12, // Douala
                'to_city_id' => 49, // Kribi
                'departure_time' => '10:00:00',
                'arrival_time' => '14:00:00',
                'duration' => '4h',
                'price' => 5500,
                'total_seats' => 40,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi', 'Snacks']),
                'stops' => json_encode(['Edéa', 'Lolodorf']),
                'recurring' => true,
                'recurring_days' => json_encode(['friday', 'saturday', 'sunday']),
                'status' => 'active',
            ],

            // Yaoundé -> Bertoua
            [
                'company_id' => 7, // Alliance Voyages
                'from_city_id' => 1, // Yaoundé
                'to_city_id' => 55, // Bertoua
                'departure_time' => '07:30:00',
                'arrival_time' => '13:30:00',
                'duration' => '6h',
                'price' => 7500,
                'total_seats' => 42,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi', 'Reclining seats']),
                'stops' => json_encode(['Ayos', 'Abong Mbang']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'wednesday', 'friday']),
                'status' => 'active',
            ],

            // Douala -> Garoua
            [
                'company_id' => 9, // Finexs Express
                'from_city_id' => 12, // Douala
                'to_city_id' => 68, // Garoua
                'departure_time' => '16:00:00',
                'arrival_time' => '08:00:00',
                'duration' => '16h',
                'price' => 12000,
                'total_seats' => 40,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'Beds', 'WiFi', 'Meals']),
                'stops' => json_encode(['Bafoussam', 'Ngaoundéré']),
                'recurring' => true,
                'recurring_days' => json_encode(['tuesday', 'thursday', 'saturday']),
                'status' => 'active',
            ],

            // Bafoussam -> Bamenda
            [
                'company_id' => 5, // Amour Mezam
                'from_city_id' => 21, // Bafoussam
                'to_city_id' => 41, // Bamenda
                'departure_time' => '11:00:00',
                'arrival_time' => '13:30:00',
                'duration' => '2h30',
                'price' => 3000,
                'total_seats' => 35,
                'bus_type' => 'Classique',
                'amenities' => json_encode(['AC']),
                'stops' => json_encode(['Mbouda']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
                'status' => 'active',
            ],

            // Yaoundé -> Ebolowa
            [
                'company_id' => 10, // Garanti Voyages
                'from_city_id' => 1, // Yaoundé
                'to_city_id' => 48, // Ebolowa
                'departure_time' => '08:00:00',
                'arrival_time' => '11:00:00',
                'duration' => '3h',
                'price' => 4000,
                'total_seats' => 40,
                'bus_type' => 'VIP',
                'amenities' => json_encode(['AC', 'WiFi']),
                'stops' => json_encode(['Mbalmayo', 'Sangmélima']),
                'recurring' => true,
                'recurring_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'status' => 'active',
            ],
        ];

        foreach ($trips as $trip) {
            \App\Models\BusTrip::create($trip);
        }

        $this->command->info('✅ 12 trajets de bus créés avec succès!');
    }
}
