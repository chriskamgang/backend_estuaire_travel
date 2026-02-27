<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service WhatsApp via UltraMsg API
 * Envoie des messages WhatsApp aux clients après une action (réservation, etc.)
 */
class WhatsAppService
{
    protected string $instanceId;
    protected string $token;
    protected string $baseUrl;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled    = (bool) Setting::get('whatsapp_enabled', false);
        $this->instanceId = Setting::get('whatsapp_instance_id', '');
        $this->token      = Setting::get('whatsapp_token', '');
        $this->baseUrl    = "https://api.ultramsg.com/{$this->instanceId}";
    }

    /**
     * Vérifier si le service est activé et configuré
     */
    public function isAvailable(): bool
    {
        return $this->enabled
            && !empty($this->instanceId)
            && !empty($this->token);
    }

    /**
     * Envoyer un message WhatsApp
     *
     * @param string $phone  Numéro de téléphone (ex: +24106XXXXXXX)
     * @param string $message Message à envoyer
     * @return bool
     */
    public function sendMessage(string $phone, string $message): bool
    {
        if (!$this->isAvailable()) {
            Log::info('WhatsApp: Service désactivé ou non configuré, message non envoyé.', [
                'phone'   => $phone,
                'message' => substr($message, 0, 100),
            ]);
            return false;
        }

        // Normaliser le numéro de téléphone
        $phone = $this->normalizePhone($phone);

        if (empty($phone)) {
            Log::warning('WhatsApp: Numéro de téléphone invalide.', ['phone' => $phone]);
            return false;
        }

        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/messages/chat", [
                'token'   => $this->token,
                'to'      => $phone,
                'body'    => $message,
                'priority' => 10,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['sent']) && $data['sent'] === 'true') {
                    Log::info('WhatsApp: Message envoyé avec succès.', [
                        'phone'   => $phone,
                        'id'      => $data['id'] ?? null,
                    ]);
                    return true;
                }
            }

            Log::warning('WhatsApp: Échec envoi message.', [
                'phone'    => $phone,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp: Exception lors de l\'envoi.', [
                'phone'   => $phone,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envoyer la confirmation de réservation covoiturage au passager
     */
    public function sendRideshareBookingConfirmation(
        string $passengerPhone,
        array  $data
    ): bool {
        $template = Setting::get(
            'whatsapp_booking_message',
            "✅ Réservation confirmée !\n\nBonjour {{passenger_name}},\n\nVotre trajet {{from}} → {{to}} est confirmé pour le {{date}} à {{time}}.\nPlaces : {{seats}} | Total : {{price}} FCFA\nConducteur : {{driver_name}} ({{driver_phone}})"
        );

        $message = $this->replacePlaceholders($template, $data);

        return $this->sendMessage($passengerPhone, $message);
    }

    /**
     * Envoyer la confirmation de réservation bus au passager
     * (sans info chauffeur — réservation immédiate côté compagnie)
     */
    public function sendBusBookingConfirmation(
        string $passengerPhone,
        array  $data
    ): bool {
        $template = Setting::get(
            'whatsapp_bus_booking_message',
            "🎟️ Réservation bus confirmée !\n\nBonjour {{passenger_name}},\n\nVotre réservation est enregistrée.\n\n🚌 {{from}} → {{to}}\n📅 {{date}} à {{time}}\n🪑 {{seats}} siège(s)\n💰 {{price}} FCFA\n🏢 {{company}}\n🔖 Réf : {{reference}}\n\nPrésentez cette référence au guichet.\n\nEstuaire Travel 🌿"
        );

        $message = $this->replacePlaceholders($template, $data);

        return $this->sendMessage($passengerPhone, $message);
    }

    /**
     * Envoyer une notification de recharge de wallet réussie
     *
     * @param string $phone  Numéro du client
     * @param array  $data   [user_name, amount, new_balance, payment_method]
     */
    public function sendWalletRecharge(string $phone, array $data): bool
    {
        $template = Setting::get(
            'whatsapp_wallet_recharge_message',
            "💰 *Recharge réussie !*\n\nBonjour {{user_name}},\n\nVotre portefeuille Estuaire Travel a été rechargé avec succès.\n\n✅ Montant crédité : *{{amount}} FCFA*\n📱 Via : {{payment_method}}\n💼 Nouveau solde : *{{new_balance}} FCFA*\n\nVous pouvez maintenant réserver vos trajets.\n\n_Estuaire Travel_ 🌿"
        );

        $message = $this->replacePlaceholders($template, $data);
        return $this->sendMessage($phone, $message);
    }

    /**
     * Envoyer une alerte de solde insuffisant lors d'une tentative de réservation
     *
     * @param string $phone  Numéro du client
     * @param array  $data   [passenger_name, from, to, required, balance, missing]
     */
    public function sendInsufficientBalance(string $phone, array $data): bool
    {
        $template = Setting::get(
            'whatsapp_insufficient_balance_message',
            "⚠️ *Solde insuffisant*\n\nBonjour {{passenger_name}},\n\nVotre tentative de réservation *{{from}} → {{to}}* n'a pas pu aboutir.\n\n💳 Solde actuel : *{{balance}} FCFA*\n🎟️ Montant requis : *{{required}} FCFA*\n📉 Il vous manque : *{{missing}} FCFA*\n\nRechargez votre portefeuille depuis l'application pour finaliser votre réservation.\n\n_Estuaire Travel_ 🌿"
        );

        $message = $this->replacePlaceholders($template, $data);
        return $this->sendMessage($phone, $message);
    }

    /**
     * Notifier le garant d'un chauffeur lors de la création d'un trajet
     *
     * @param string $guarantorPhone  Numéro WhatsApp du garant
     * @param array  $data            [guarantor_name, driver_name, from, to, date, time, seats, price, driver_phone]
     */
    public function sendGuarantorNotification(string $guarantorPhone, array $data): bool
    {
        $template = Setting::get(
            'whatsapp_guarantor_message',
            "🚗 *Estuaire Travel – Notification Garant*\n\nBonjour {{guarantor_name}},\n\nVous êtes le garant de *{{driver_name}}* sur l'application Estuaire Travel.\n\nVotre contact a créé un nouveau trajet de covoiturage :\n\n📍 Trajet : *{{from}} → {{to}}*\n📅 Date : {{date}} à {{time}}\n💺 Places : {{seats}} | 💰 Prix : {{price}} FCFA/place\n📞 Téléphone chauffeur : {{driver_phone}}\n\nEn tant que garant, votre rôle est de confirmer l'identité et la fiabilité du chauffeur pour ses passagers.\n\n_Estuaire Travel_ 🌿"
        );

        $message = $this->replacePlaceholders($template, $data);
        return $this->sendMessage($guarantorPhone, $message);
    }

    /**
     * Notifier le passager que son paiement est en séquestre (escrow)
     * et sera libéré au chauffeur après l'embarquement
     *
     * @param string $passengerPhone
     * @param array  $data  [passenger_name, from, to, date, time, amount]
     */
    public function sendEscrowNotification(string $passengerPhone, array $data): bool
    {
        $template = Setting::get(
            'whatsapp_escrow_message',
            "🔒 *Paiement sécurisé – Estuaire Travel*\n\nBonjour {{passenger_name}},\n\nVotre paiement de *{{amount}} FCFA* pour le trajet *{{from}} → {{to}}* du {{date}} à {{time}} a été prélevé et mis en *séquestre sécurisé*.\n\n✅ Votre argent est protégé.\n💡 Il sera versé au chauffeur uniquement après votre *embarquement confirmé par scan QR*.\n\nSi vous ne montez pas dans le véhicule, contactez le support pour un remboursement.\n\n_Estuaire Travel_ 🌿"
        );

        $message = $this->replacePlaceholders($template, $data);
        return $this->sendMessage($passengerPhone, $message);
    }

    /**
     * Tester la connexion UltraMsg avec les credentials actuels
     * Envoie un message de test au numéro donné
     */
    public function sendTestMessage(string $phone): array
    {
        if (empty($this->instanceId) || empty($this->token)) {
            return [
                'success' => false,
                'message' => 'Instance ID ou Token manquant',
            ];
        }

        $success = $this->sendMessage($phone, "🌿 *Test Estuaire Travel*\n\nConnexion WhatsApp opérationnelle ✅\n\nCe message confirme que vos notifications WhatsApp sont correctement configurées.");

        return [
            'success' => $success,
            'message' => $success ? 'Message de test envoyé avec succès' : 'Échec de l\'envoi — vérifiez vos credentials',
        ];
    }

    /**
     * Normaliser un numéro de téléphone
     * Ajoute le préfixe Gabon (+241) si absent
     */
    protected function normalizePhone(string $phone): string
    {
        // Supprimer espaces, tirets, parenthèses
        $phone = preg_replace('/[\s\-().]+/', '', $phone);

        // Si commence par 0, remplacer par indicatif Gabon
        if (str_starts_with($phone, '0')) {
            $phone = '+241' . substr($phone, 1);
        }

        // Si commence par 6 ou 7 (numéros gabonais sans préfixe)
        if (preg_match('/^[67]\d{7}$/', $phone)) {
            $phone = '+241' . $phone;
        }

        // Ajouter + si commence par 241
        if (str_starts_with($phone, '241')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Remplacer les variables dans le template de message
     */
    protected function replacePlaceholders(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value ?? '–', $template);
        }
        return $template;
    }
}
