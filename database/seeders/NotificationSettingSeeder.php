<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;

class NotificationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Notifications Push
            [
                'key' => 'push_booking_confirmed',
                'name' => 'Réservation confirmée',
                'description' => 'Notification envoyée lorsque la réservation est confirmée',
                'category' => 'push',
                'enabled' => true,
                'user_can_disable' => true,
            ],
            [
                'key' => 'push_trip_reminder',
                'name' => 'Rappel de voyage',
                'description' => 'Notification envoyée 24h avant le départ',
                'category' => 'push',
                'enabled' => true,
                'user_can_disable' => true,
            ],
            [
                'key' => 'push_driver_nearby',
                'name' => 'Conducteur à proximité',
                'description' => 'Notification lorsque le conducteur est proche',
                'category' => 'push',
                'enabled' => true,
                'user_can_disable' => true,
            ],
            [
                'key' => 'push_trip_started',
                'name' => 'Voyage commencé',
                'description' => 'Notification lorsque le voyage démarre',
                'category' => 'push',
                'enabled' => true,
                'user_can_disable' => false,
            ],

            // Notifications Email
            [
                'key' => 'email_booking_receipt',
                'name' => 'Reçu de réservation',
                'description' => 'Email de confirmation avec le reçu',
                'category' => 'email',
                'enabled' => true,
                'user_can_disable' => false,
            ],
            [
                'key' => 'email_trip_reminder',
                'name' => 'Rappel de voyage par email',
                'description' => 'Email de rappel 24h avant le départ',
                'category' => 'email',
                'enabled' => true,
                'user_can_disable' => true,
            ],
            [
                'key' => 'email_password_reset',
                'name' => 'Réinitialisation mot de passe',
                'description' => 'Email pour réinitialiser le mot de passe',
                'category' => 'email',
                'enabled' => true,
                'user_can_disable' => false,
            ],
            [
                'key' => 'email_weekly_summary',
                'name' => 'Résumé hebdomadaire',
                'description' => 'Email récapitulatif des activités de la semaine',
                'category' => 'email',
                'enabled' => true,
                'user_can_disable' => true,
            ],

            // Notifications SMS
            [
                'key' => 'sms_booking_confirmed',
                'name' => 'Confirmation de réservation par SMS',
                'description' => 'SMS de confirmation de réservation',
                'category' => 'sms',
                'enabled' => false,
                'user_can_disable' => true,
            ],
            [
                'key' => 'sms_trip_reminder',
                'name' => 'Rappel de voyage par SMS',
                'description' => 'SMS de rappel 2h avant le départ',
                'category' => 'sms',
                'enabled' => false,
                'user_can_disable' => true,
            ],
            [
                'key' => 'sms_verification',
                'name' => 'Code de vérification',
                'description' => 'SMS avec le code de vérification du téléphone',
                'category' => 'sms',
                'enabled' => true,
                'user_can_disable' => false,
            ],

            // Promotions
            [
                'key' => 'promo_new_offers',
                'name' => 'Nouvelles offres',
                'description' => 'Notifications des nouvelles promotions',
                'category' => 'promo',
                'enabled' => true,
                'user_can_disable' => true,
            ],
            [
                'key' => 'promo_special_deals',
                'name' => 'Offres spéciales',
                'description' => 'Notifications des offres exceptionnelles',
                'category' => 'promo',
                'enabled' => true,
                'user_can_disable' => true,
            ],
            [
                'key' => 'promo_loyalty_rewards',
                'name' => 'Récompenses fidélité',
                'description' => 'Notifications des récompenses de fidélité disponibles',
                'category' => 'promo',
                'enabled' => true,
                'user_can_disable' => true,
            ],
        ];

        foreach ($settings as $setting) {
            NotificationSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
