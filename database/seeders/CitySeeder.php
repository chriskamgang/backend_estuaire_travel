<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    /**
     * Les 80 villes du Cameroun par région
     */
    public function run(): void
    {
        $cities = [
            // CENTRE (11 villes)
            ['name' => 'Yaoundé', 'region' => 'Centre', 'is_main_city' => true, 'latitude' => 3.8480, 'longitude' => 11.5021],
            ['name' => 'Obala', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 4.1708, 'longitude' => 11.5375],
            ['name' => 'Mbalmayo', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 3.5167, 'longitude' => 11.5000],
            ['name' => 'Bafia', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 4.7500, 'longitude' => 11.2333],
            ['name' => 'Nanga Eboko', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 4.6833, 'longitude' => 12.3833],
            ['name' => 'Eseka', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 3.6500, 'longitude' => 10.7667],
            ['name' => 'Mfou', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 3.7167, 'longitude' => 11.6833],
            ['name' => 'Akonolinga', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 3.7667, 'longitude' => 12.2500],
            ['name' => 'Ntui', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 4.5167, 'longitude' => 11.6167],
            ['name' => 'Ayos', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 3.9000, 'longitude' => 12.5333],
            ['name' => 'Saa', 'region' => 'Centre', 'is_main_city' => false, 'latitude' => 4.3667, 'longitude' => 11.4500],

            // LITTORAL (9 villes)
            ['name' => 'Douala', 'region' => 'Littoral', 'is_main_city' => true, 'latitude' => 4.0511, 'longitude' => 9.7679],
            ['name' => 'Édéa', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 3.8000, 'longitude' => 10.1333],
            ['name' => 'Nkongsamba', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 4.9547, 'longitude' => 9.9397],
            ['name' => 'Dibombari', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 4.0833, 'longitude' => 9.7000],
            ['name' => 'Loum', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 4.7167, 'longitude' => 9.7333],
            ['name' => 'Manjo', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 4.8667, 'longitude' => 9.6333],
            ['name' => 'Penja', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 4.6333, 'longitude' => 9.7000],
            ['name' => 'Mbanga', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 4.5000, 'longitude' => 9.5667],
            ['name' => 'Yabassi', 'region' => 'Littoral', 'is_main_city' => false, 'latitude' => 4.4667, 'longitude' => 9.9667],

            // OUEST (13 villes)
            ['name' => 'Bafoussam', 'region' => 'Ouest', 'is_main_city' => true, 'latitude' => 5.4781, 'longitude' => 10.4178],
            ['name' => 'Dschang', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.4500, 'longitude' => 10.0667],
            ['name' => 'Bafang', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.1594, 'longitude' => 10.1781],
            ['name' => 'Mbouda', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.6333, 'longitude' => 10.2500],
            ['name' => 'Foumban', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.7333, 'longitude' => 10.9000],
            ['name' => 'Bangangté', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.1500, 'longitude' => 10.5167],
            ['name' => 'Bandjoun', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.3667, 'longitude' => 10.4167],
            ['name' => 'Foumbot', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.5167, 'longitude' => 10.6333],
            ['name' => 'Baham', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.3167, 'longitude' => 10.3667],
            ['name' => 'Bafou', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.4833, 'longitude' => 10.3667],
            ['name' => 'Batcham', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.3333, 'longitude' => 10.3833],
            ['name' => 'Penka Michel', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.4333, 'longitude' => 10.2833],
            ['name' => 'Tonga', 'region' => 'Ouest', 'is_main_city' => false, 'latitude' => 5.4167, 'longitude' => 10.1500],

            // SUD-OUEST (7 villes)
            ['name' => 'Buea', 'region' => 'Sud-Ouest', 'is_main_city' => true, 'latitude' => 4.1564, 'longitude' => 9.2325],
            ['name' => 'Limbe', 'region' => 'Sud-Ouest', 'is_main_city' => false, 'latitude' => 4.0167, 'longitude' => 9.2000],
            ['name' => 'Kumba', 'region' => 'Sud-Ouest', 'is_main_city' => false, 'latitude' => 4.6333, 'longitude' => 9.4500],
            ['name' => 'Tiko', 'region' => 'Sud-Ouest', 'is_main_city' => false, 'latitude' => 4.0833, 'longitude' => 9.3667],
            ['name' => 'Muyuka', 'region' => 'Sud-Ouest', 'is_main_city' => false, 'latitude' => 4.3000, 'longitude' => 9.4000],
            ['name' => 'Mamfe', 'region' => 'Sud-Ouest', 'is_main_city' => false, 'latitude' => 5.7667, 'longitude' => 9.3000],
            ['name' => 'Nguti', 'region' => 'Sud-Ouest', 'is_main_city' => false, 'latitude' => 5.0000, 'longitude' => 9.4667],

            // NORD-OUEST (7 villes)
            ['name' => 'Bamenda', 'region' => 'Nord-Ouest', 'is_main_city' => true, 'latitude' => 5.9631, 'longitude' => 10.1591],
            ['name' => 'Kumbo', 'region' => 'Nord-Ouest', 'is_main_city' => false, 'latitude' => 6.2000, 'longitude' => 10.6667],
            ['name' => 'Wum', 'region' => 'Nord-Ouest', 'is_main_city' => false, 'latitude' => 6.3833, 'longitude' => 10.0667],
            ['name' => 'Fundong', 'region' => 'Nord-Ouest', 'is_main_city' => false, 'latitude' => 6.1833, 'longitude' => 10.2833],
            ['name' => 'Mbengwi', 'region' => 'Nord-Ouest', 'is_main_city' => false, 'latitude' => 6.0167, 'longitude' => 10.0000],
            ['name' => 'Nkambe', 'region' => 'Nord-Ouest', 'is_main_city' => false, 'latitude' => 6.6000, 'longitude' => 10.6667],
            ['name' => 'Bali', 'region' => 'Nord-Ouest', 'is_main_city' => false, 'latitude' => 5.8667, 'longitude' => 10.0500],

            // SUD (7 villes)
            ['name' => 'Ebolowa', 'region' => 'Sud', 'is_main_city' => true, 'latitude' => 2.9000, 'longitude' => 11.1500],
            ['name' => 'Kribi', 'region' => 'Sud', 'is_main_city' => false, 'latitude' => 2.9333, 'longitude' => 9.9167],
            ['name' => 'Sangmélima', 'region' => 'Sud', 'is_main_city' => false, 'latitude' => 2.9333, 'longitude' => 11.9833],
            ['name' => 'Campo', 'region' => 'Sud', 'is_main_city' => false, 'latitude' => 2.3667, 'longitude' => 9.8167],
            ['name' => 'Ambam', 'region' => 'Sud', 'is_main_city' => false, 'latitude' => 2.3833, 'longitude' => 11.2667],
            ['name' => 'Lolodorf', 'region' => 'Sud', 'is_main_city' => false, 'latitude' => 3.2333, 'longitude' => 10.7333],
            ['name' => 'Akom II', 'region' => 'Sud', 'is_main_city' => false, 'latitude' => 3.2667, 'longitude' => 10.5667],

            // EST (8 villes)
            ['name' => 'Bertoua', 'region' => 'Est', 'is_main_city' => true, 'latitude' => 4.5833, 'longitude' => 13.6833],
            ['name' => 'Batouri', 'region' => 'Est', 'is_main_city' => false, 'latitude' => 4.4333, 'longitude' => 14.3667],
            ['name' => 'Abong Mbang', 'region' => 'Est', 'is_main_city' => false, 'latitude' => 3.9833, 'longitude' => 13.1833],
            ['name' => 'Yokadouma', 'region' => 'Est', 'is_main_city' => false, 'latitude' => 3.5167, 'longitude' => 15.0500],
            ['name' => 'Lomié', 'region' => 'Est', 'is_main_city' => false, 'latitude' => 3.1667, 'longitude' => 13.6167],
            ['name' => 'Ngoura', 'region' => 'Est', 'is_main_city' => false, 'latitude' => 4.1500, 'longitude' => 14.1667],
            ['name' => 'Doumé', 'region' => 'Est', 'is_main_city' => false, 'latitude' => 4.2333, 'longitude' => 13.3833],
            ['name' => 'Belabo', 'region' => 'Est', 'is_main_city' => false, 'latitude' => 4.9333, 'longitude' => 13.3000],

            // ADAMAOUA (5 villes)
            ['name' => 'Ngaoundéré', 'region' => 'Adamaoua', 'is_main_city' => true, 'latitude' => 7.3167, 'longitude' => 13.5833],
            ['name' => 'Tibati', 'region' => 'Adamaoua', 'is_main_city' => false, 'latitude' => 6.4667, 'longitude' => 12.6167],
            ['name' => 'Banyo', 'region' => 'Adamaoua', 'is_main_city' => false, 'latitude' => 6.7500, 'longitude' => 11.8167],
            ['name' => 'Tignère', 'region' => 'Adamaoua', 'is_main_city' => false, 'latitude' => 7.3667, 'longitude' => 12.6500],
            ['name' => 'Meiganga', 'region' => 'Adamaoua', 'is_main_city' => false, 'latitude' => 6.5167, 'longitude' => 14.3000],

            // NORD (6 villes)
            ['name' => 'Garoua', 'region' => 'Nord', 'is_main_city' => true, 'latitude' => 9.3000, 'longitude' => 13.4000],
            ['name' => 'Guider', 'region' => 'Nord', 'is_main_city' => false, 'latitude' => 9.9333, 'longitude' => 13.9500],
            ['name' => 'Figuil', 'region' => 'Nord', 'is_main_city' => false, 'latitude' => 9.7667, 'longitude' => 13.9667],
            ['name' => 'Poli', 'region' => 'Nord', 'is_main_city' => false, 'latitude' => 8.7333, 'longitude' => 13.2167],
            ['name' => 'Tcholliré', 'region' => 'Nord', 'is_main_city' => false, 'latitude' => 8.3833, 'longitude' => 14.1667],
            ['name' => 'Rey Bouba', 'region' => 'Nord', 'is_main_city' => false, 'latitude' => 8.6667, 'longitude' => 14.1833],

            // EXTRÊME-NORD (7 villes)
            ['name' => 'Maroua', 'region' => 'Extrême-Nord', 'is_main_city' => true, 'latitude' => 10.5964, 'longitude' => 14.3308],
            ['name' => 'Kousseri', 'region' => 'Extrême-Nord', 'is_main_city' => false, 'latitude' => 12.0833, 'longitude' => 15.0333],
            ['name' => 'Mokolo', 'region' => 'Extrême-Nord', 'is_main_city' => false, 'latitude' => 10.7333, 'longitude' => 13.8000],
            ['name' => 'Yagoua', 'region' => 'Extrême-Nord', 'is_main_city' => false, 'latitude' => 10.3333, 'longitude' => 15.2333],
            ['name' => 'Kaélé', 'region' => 'Extrême-Nord', 'is_main_city' => false, 'latitude' => 10.0833, 'longitude' => 14.4500],
            ['name' => 'Mora', 'region' => 'Extrême-Nord', 'is_main_city' => false, 'latitude' => 11.0500, 'longitude' => 14.1500],
            ['name' => 'Bogo', 'region' => 'Extrême-Nord', 'is_main_city' => false, 'latitude' => 10.7333, 'longitude' => 14.6000],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }

        $this->command->info('✅ 80 villes du Cameroun créées avec succès!');
    }
}
