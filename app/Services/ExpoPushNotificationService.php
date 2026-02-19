<?php

namespace App\Services;

use App\Models\PushToken;
use App\Models\Notification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private Client $client;
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Envoyer une notification push à un utilisateur
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        // Récupérer tous les tokens actifs de l'utilisateur
        $tokens = PushToken::getActiveTokensForUser($userId);

        if (empty($tokens)) {
            Log::info("Aucun token push actif pour l'utilisateur {$userId}");
            return [
                'success' => false,
                'message' => 'Aucun token push actif',
                'sent_count' => 0,
            ];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Envoyer une notification push à plusieurs tokens
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        // Construire les messages pour Expo
        $messages = [];
        foreach ($tokens as $token) {
            // Vérifier que le token a le bon format Expo
            if (!$this->isValidExpoToken($token)) {
                Log::warning("Token Expo invalide: {$token}");
                continue;
            }

            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'priority' => 'high',
                'channelId' => 'default',
            ];
        }

        if (empty($messages)) {
            return [
                'success' => false,
                'message' => 'Aucun token valide',
                'sent_count' => 0,
            ];
        }

        // Envoyer les notifications par batch (max 100 par requête)
        $results = [];
        $batches = array_chunk($messages, 100);

        foreach ($batches as $batch) {
            try {
                $response = $this->client->post(self::EXPO_PUSH_URL, [
                    'json' => $batch,
                ]);

                $result = json_decode($response->getBody()->getContents(), true);
                $results[] = $result;

                Log::info('Notifications push envoyées avec succès', [
                    'count' => count($batch),
                    'response' => $result,
                ]);
            } catch (GuzzleException $e) {
                Log::error('Erreur lors de l\'envoi des notifications push', [
                    'error' => $e->getMessage(),
                    'batch_size' => count($batch),
                ]);

                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage(),
                    'sent_count' => 0,
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Notifications envoyées avec succès',
            'sent_count' => count($messages),
            'results' => $results,
        ];
    }

    /**
     * Créer une notification dans la DB et l'envoyer via push
     *
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $data
     * @return array
     */
    public function createAndSendNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): array {
        // 1. Créer la notification dans la DB
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read' => false,
        ]);

        // 2. Envoyer la notification push
        $pushResult = $this->sendToUser(
            $userId,
            $title,
            $message,
            array_merge($data, ['notification_id' => $notification->id])
        );

        return [
            'success' => true,
            'notification' => $notification,
            'push_result' => $pushResult,
        ];
    }

    /**
     * Vérifier si un token a le format Expo valide
     *
     * @param string $token
     * @return bool
     */
    private function isValidExpoToken(string $token): bool
    {
        // Les tokens Expo commencent par "ExponentPushToken[" ou "ExpoPushToken["
        return str_starts_with($token, 'ExponentPushToken[') ||
               str_starts_with($token, 'ExpoPushToken[');
    }

    /**
     * Envoyer une notification push de test
     *
     * @param string $token
     * @return array
     */
    public function sendTestNotification(string $token): array
    {
        return $this->sendToTokens(
            [$token],
            '🧪 Test de notification',
            'Ceci est une notification de test depuis Estuaire Travel!',
            ['type' => 'test', 'timestamp' => now()->toIso8601String()]
        );
    }
}
