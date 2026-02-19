<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service d'intégration Freemopay v2
 * Gère les paiements Mobile Money (MTN/Orange) via l'API Freemopay
 */
class FreemopayService
{
    protected string $baseUrl;
    protected string $appKey;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl   = config('services.freemopay.base_url', 'https://api-v2.freemopay.com');
        $this->appKey    = config('services.freemopay.app_key', '');
        $this->secretKey = config('services.freemopay.secret_key', '');
    }

    /**
     * Obtenir un token JWT (expire en 3600s)
     * Utilise Basic Auth : username=appKey, password=secretKey
     */
    public function getToken(): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withBasicAuth($this->appKey, $this->secretKey)
                ->post("{$this->baseUrl}/api/v2/payment/token");

            if ($response->successful()) {
                return $response->json('token') ?? $response->json('access_token');
            }

            Log::warning('Freemopay: Impossible d\'obtenir un token', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Freemopay: Exception getToken', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Initialiser un paiement Mobile Money (dépôt depuis le client)
     *
     * @param string $phone      Numéro du payeur (ex: 237695509408)
     * @param float  $amount     Montant en FCFA
     * @param string $externalId Notre identifiant interne (UUID)
     * @param string $callback   URL de webhook pour la notification
     *
     * @return array ['success' => bool, 'reference' => string|null, 'message' => string]
     */
    public function initiatePayment(
        string $phone,
        float  $amount,
        string $externalId,
        string $callback
    ): array {
        try {
            $response = Http::timeout(30)
                ->withBasicAuth($this->appKey, $this->secretKey)
                ->post("{$this->baseUrl}/api/v2/payment", [
                    'payer'      => $this->normalizePhone($phone),
                    'amount'     => (string) (int) $amount,
                    'externalId' => $externalId,
                    'callbackUrl' => $callback,
                ]);

            $data = $response->json();

            Log::info('Freemopay: Paiement initié', [
                'externalId' => $externalId,
                'status'     => $response->status(),
                'response'   => $data,
            ]);

            if ($response->successful() && isset($data['reference'])) {
                return [
                    'success'   => true,
                    'reference' => $data['reference'],
                    'status'    => $data['status'] ?? 'PENDING',
                    'message'   => $data['message'] ?? 'Paiement initié',
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors de l\'initiation du paiement',
            ];

        } catch (\Exception $e) {
            Log::error('Freemopay: Exception initiatePayment', [
                'externalId' => $externalId,
                'error'      => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Impossible de contacter le service de paiement',
            ];
        }
    }

    /**
     * Vérifier le statut d'un paiement
     *
     * @param string $reference UUID Freemopay
     * @return array ['status' => 'SUCCESS'|'FAILED'|'PENDING', ...]
     */
    public function getPaymentStatus(string $reference): array
    {
        try {
            $response = Http::timeout(15)
                ->withBasicAuth($this->appKey, $this->secretKey)
                ->get("{$this->baseUrl}/api/v2/payment/{$reference}");

            if ($response->successful()) {
                return $response->json();
            }

            return ['status' => 'UNKNOWN', 'message' => 'Impossible de récupérer le statut'];

        } catch (\Exception $e) {
            Log::error('Freemopay: Exception getPaymentStatus', ['error' => $e->getMessage()]);
            return ['status' => 'UNKNOWN', 'message' => $e->getMessage()];
        }
    }

    /**
     * Effectuer un retrait (withdraw) vers un numéro
     * Utilisé pour les transferts wallet → numéro externe
     */
    public function withdraw(
        string $receiver,
        float  $amount,
        string $externalId,
        string $callback
    ): array {
        try {
            $response = Http::timeout(30)
                ->withBasicAuth($this->appKey, $this->secretKey)
                ->post("{$this->baseUrl}/api/v2/payment/direct-withdraw", [
                    'receiver'   => $this->normalizePhone($receiver),
                    'amount'     => (string) (int) $amount,
                    'externalId' => $externalId,
                    'callback'   => $callback,
                ]);

            $data = $response->json();

            Log::info('Freemopay: Withdraw initié', [
                'receiver'   => $receiver,
                'externalId' => $externalId,
                'response'   => $data,
            ]);

            if ($response->successful() && isset($data['reference'])) {
                return [
                    'success'   => true,
                    'reference' => $data['reference'],
                    'status'    => $data['status'] ?? 'CREATED',
                    'message'   => $data['message'] ?? 'Transfert initié',
                ];
            }

            // Le message peut être un objet {"en": "...", "fr": "..."}
            $msg = $data['message'] ?? 'Erreur lors du transfert';
            if (is_array($msg)) {
                $msg = $msg['fr'] ?? $msg['en'] ?? 'Erreur lors du transfert';
            }

            return [
                'success' => false,
                'message' => $msg,
            ];

        } catch (\Exception $e) {
            Log::error('Freemopay: Exception withdraw', [
                'receiver' => $receiver,
                'error'    => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Impossible de contacter le service de paiement',
            ];
        }
    }

    /**
     * Normaliser le numéro de téléphone (format international sans +)
     * Ex: 659339778 → 237659339778
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-().+]+/', '', $phone);

        // Déjà au format international complet (commence par 237)
        if (str_starts_with($phone, '237')) {
            return $phone;
        }

        // Format local camerounais : 6XXXXXXXX ou 7XXXXXXXX (9 chiffres)
        if (preg_match('/^[67]\d{8}$/', $phone)) {
            return '237' . $phone;
        }

        // Format avec 0 devant : 06XXXXXXXX ou 07XXXXXXXX
        if (str_starts_with($phone, '0') && preg_match('/^0[67]\d{8}$/', $phone)) {
            return '237' . substr($phone, 1);
        }

        return $phone;
    }
}
