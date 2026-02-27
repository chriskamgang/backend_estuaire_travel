<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $senderId;

    public function __construct()
    {
        $this->baseUrl = config('services.nexah.base_url');
        $this->username = config('services.nexah.username');
        $this->password = config('services.nexah.password');
        $this->senderId = config('services.nexah.sender_id');
    }

    /**
     * Envoyer un SMS via l'API Nexah
     *
     * @param string $phoneNumber Numéro de téléphone au format international (ex: 237670000000)
     * @param string $message Contenu du message
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Nettoyer le numéro de téléphone
            $phoneNumber = $this->cleanPhoneNumber($phoneNumber);

            $response = Http::timeout(30)->asForm()->post("{$this->baseUrl}/sendsms", [
                'user' => $this->username,
                'password' => $this->password,
                'senderid' => $this->senderId,
                'sms' => $message,
                'mobiles' => $phoneNumber,
            ]);

            if ($response->successful()) {
                Log::info('SMS envoyé avec succès', [
                    'phone' => $phoneNumber,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS envoyé avec succès',
                    'data' => $response->json()
                ];
            }

            Log::error('Échec envoi SMS', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Échec de l\'envoi du SMS',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de l\'envoi du SMS',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un code OTP par SMS
     *
     * @param string $phoneNumber
     * @param string $otp
     * @return array
     */
    public function sendOtp(string $phoneNumber, string $otp): array
    {
        // Découper le code pour éviter la détection automatique OTP (ex: 123456 → 123 456)
        $formattedOtp = substr($otp, 0, 3) . ' ' . substr($otp, 3, 3);
        $message = "Estuaire Travel: Votre code de verification est {$formattedOtp}. Valable 10 minutes.";
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Vérifier le solde de crédits SMS
     *
     * @return array
     */
    public function checkCredits(): array
    {
        try {
            $response = Http::timeout(30)->asForm()->post("{$this->baseUrl}/smscredit", [
                'user' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'credits' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de récupérer le solde'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification des crédits SMS', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Nettoyer et formater le numéro de téléphone
     *
     * @param string $phoneNumber
     * @return string
     */
    private function cleanPhoneNumber(string $phoneNumber): string
    {
        // Retirer tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Si le numéro commence par 0, le remplacer par 237 (indicatif Cameroun)
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '237' . substr($cleaned, 1);
        }

        // Si le numéro ne commence pas par 237, l'ajouter
        if (!str_starts_with($cleaned, '237')) {
            $cleaned = '237' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Générer un code OTP aléatoire
     *
     * @param int $length
     * @return string
     */
    public static function generateOtp(int $length = 6): string
    {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= random_int(0, 9);
        }
        return $otp;
    }
}
