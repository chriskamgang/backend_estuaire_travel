<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter les settings WhatsApp (UltraMsg) dans la table settings
        $settings = [
            [
                'key'         => 'whatsapp_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'description' => 'Activer/désactiver les notifications WhatsApp',
                'group'       => 'whatsapp',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'whatsapp_instance_id',
                'value'       => '',
                'type'        => 'string',
                'description' => 'Instance ID UltraMsg (ex: instance12345)',
                'group'       => 'whatsapp',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'whatsapp_token',
                'value'       => '',
                'type'        => 'string',
                'description' => 'Token d\'authentification UltraMsg',
                'group'       => 'whatsapp',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'whatsapp_booking_message',
                'value'       => "✅ *Réservation confirmée !*\n\nBonjour {{passenger_name}},\n\nVotre réservation sur le trajet *{{from}} → {{to}}* est confirmée.\n\n📅 Date : {{date}}\n⏰ Départ : {{time}}\n🪑 Places : {{seats}}\n💰 Total : {{price}} FCFA\n📍 Point de prise en charge : {{pickup}}\n\n🚗 Conducteur : {{driver_name}}\n📞 Téléphone : {{driver_phone}}\n\nBon voyage avec Estuaire Travel ! 🌿",
                'type'        => 'string',
                'description' => 'Message WhatsApp envoyé au passager lors de la confirmation de réservation covoiturage. Variables : {{passenger_name}}, {{from}}, {{to}}, {{date}}, {{time}}, {{seats}}, {{price}}, {{pickup}}, {{driver_name}}, {{driver_phone}}',
                'group'       => 'whatsapp',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'whatsapp_bus_booking_message',
                'value'       => "🎟️ *Réservation bus confirmée !*\n\nBonjour {{passenger_name}},\n\nVotre réservation est enregistrée avec succès.\n\n🚌 Trajet : *{{from}} → {{to}}*\n📅 Date : {{date}}\n⏰ Départ : {{time}}\n🪑 Siège(s) : {{seats}}\n💰 Total : {{price}} FCFA\n🏢 Compagnie : {{company}}\n🔖 Référence : {{reference}}\n\nPrésentez cette référence au guichet pour récupérer votre billet.\n\nBon voyage avec Estuaire Travel ! 🌿",
                'type'        => 'string',
                'description' => 'Message WhatsApp envoyé au passager lors de la réservation d\'un bus. Variables : {{passenger_name}}, {{from}}, {{to}}, {{date}}, {{time}}, {{seats}}, {{price}}, {{company}}, {{reference}}',
                'group'       => 'whatsapp',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'whatsapp_enabled',
            'whatsapp_instance_id',
            'whatsapp_token',
            'whatsapp_booking_message',
            'whatsapp_bus_booking_message',
        ])->delete();
    }
};
