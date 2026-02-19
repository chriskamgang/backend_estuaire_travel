<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Les principales compagnies de bus au Cameroun
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Touristique Express',
                'logo' => null,
                'rating' => 4.5,
                'total_reviews' => 1250,
                'phone' => '+237 233 42 89 10',
                'email' => 'contact@touristiqueexpress.cm',
                'address' => 'Boulevard de la Liberté, Douala, Cameroun',
            ],
            [
                'name' => 'Guaranti Express',
                'logo' => null,
                'rating' => 4.3,
                'total_reviews' => 980,
                'phone' => '+237 233 42 15 20',
                'email' => 'info@garantiexpress.cm',
                'address' => 'Avenue Kennedy, Yaoundé, Cameroun',
            ],
            [
                'name' => 'Général Express',
                'logo' => null,
                'rating' => 4.2,
                'total_reviews' => 850,
                'phone' => '+237 233 42 78 45',
                'email' => 'contact@generalexpress.cm',
                'address' => 'Rue Joffre, Douala, Cameroun',
            ],
            [
                'name' => 'Central Voyages',
                'logo' => null,
                'rating' => 4.0,
                'total_reviews' => 720,
                'phone' => '+237 222 22 33 44',
                'email' => 'info@centralvoyages.cm',
                'address' => 'Marché Central, Yaoundé, Cameroun',
            ],
            [
                'name' => 'Amour Mezam',
                'logo' => null,
                'rating' => 4.4,
                'total_reviews' => 650,
                'phone' => '+237 233 36 25 10',
                'email' => 'contact@amourmezam.cm',
                'address' => 'Commercial Avenue, Bamenda, Cameroun',
            ],
            [
                'name' => 'Binam Voyages',
                'logo' => null,
                'rating' => 4.1,
                'total_reviews' => 540,
                'phone' => '+237 233 44 55 66',
                'email' => 'info@binamvoyages.cm',
                'address' => 'Gare Routière, Bafoussam, Cameroun',
            ],
            [
                'name' => 'Alliance Voyages',
                'logo' => null,
                'rating' => 3.9,
                'total_reviews' => 420,
                'phone' => '+237 222 33 44 55',
                'email' => 'contact@alliancevoyages.cm',
                'address' => 'Rond-Point Nlongkak, Yaoundé, Cameroun',
            ],
            [
                'name' => 'Musango Express',
                'logo' => null,
                'rating' => 4.3,
                'total_reviews' => 780,
                'phone' => '+237 233 55 66 77',
                'email' => 'info@musangoexpress.cm',
                'address' => 'Bonanjo, Douala, Cameroun',
            ],
            [
                'name' => 'Finexs Express',
                'logo' => null,
                'rating' => 4.0,
                'total_reviews' => 380,
                'phone' => '+237 233 66 77 88',
                'email' => 'contact@finexsexpress.cm',
                'address' => 'Gare de Mvan, Yaoundé, Cameroun',
            ],
            [
                'name' => 'Garanti Voyages',
                'logo' => null,
                'rating' => 4.2,
                'total_reviews' => 590,
                'phone' => '+237 233 77 88 99',
                'email' => 'info@garantivoyages.cm',
                'address' => 'Carrefour Ekounou, Yaoundé, Cameroun',
            ],
        ];

        foreach ($companies as $company) {
            \App\Models\Company::create($company);
        }

        $this->command->info('✅ 10 compagnies de bus créées avec succès!');
    }
}
