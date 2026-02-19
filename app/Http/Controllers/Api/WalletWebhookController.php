<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\ExpoPushNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Freemopay
 * Reçoit les callbacks SUCCESS/FAILED après paiement Mobile Money
 */
class WalletWebhookController extends Controller
{
    /**
     * POST /webhook/freemopay
     * Callback envoyé par Freemopay après validation du paiement
     *
     * Body:
     * {
     *   "status": "SUCCESS"|"FAILED",
     *   "reference": "uuid-freemopay",
     *   "amount": 5000,
     *   "transactionType": "DEPOSIT",
     *   "externalId": "RECHARGE-userId-uuid",
     *   "message": "..."
     * }
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        Log::info('Freemopay Webhook reçu', $data);

        $reference  = $data['reference']  ?? null;
        $externalId = $data['externalId'] ?? null;
        $status     = $data['status']     ?? null;
        $amount     = (float) ($data['amount'] ?? 0);

        if (!$reference || !$status) {
            return response()->json(['message' => 'Données invalides'], 400);
        }

        // Trouver la transaction par référence Freemopay ou externalId
        $transaction = WalletTransaction::where('freemopay_reference', $reference)
            ->orWhere('external_id', $externalId)
            ->first();

        if (!$transaction) {
            Log::warning('Freemopay Webhook: transaction introuvable', compact('reference', 'externalId'));
            return response()->json(['message' => 'Transaction introuvable'], 404);
        }

        // Éviter le double traitement
        if ($transaction->payment_status !== 'pending') {
            Log::info('Freemopay Webhook: transaction déjà traitée', ['id' => $transaction->id]);
            return response()->json(['message' => 'Déjà traité'], 200);
        }

        if ($status === 'SUCCESS') {
            $this->handleSuccess($transaction, $amount);
        } else {
            $this->handleFailure($transaction, $data['message'] ?? 'Paiement échoué');
        }

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Traiter un paiement réussi (recharge uniquement)
     * Les débits (transfer/debit) sont traités immédiatement à l'initiation
     */
    protected function handleSuccess(WalletTransaction $transaction, float $amount): void
    {
        // Ne traiter que les recharges en attente
        if ($transaction->type !== 'recharge') {
            $transaction->update(['payment_status' => 'success']);
            return;
        }

        $wallet = Wallet::find($transaction->wallet_id);
        if (!$wallet) return;

        // Verrou atomique : marquer comme success AVANT de créditer
        // Évite la race condition avec checkRecharge (polling frontend)
        $updated = $transaction->newQuery()
            ->where('id', $transaction->id)
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'success']);

        if ($updated === 0) {
            // Déjà traité par checkRecharge ou un autre appel webhook concurrent
            Log::info('Freemopay Webhook: handleSuccess ignoré — déjà traité', ['id' => $transaction->id]);
            return;
        }

        // Créditer le wallet (une seule fois)
        // Note: ne pas re-mettre freemopay_reference dans la nouvelle transaction
        // (contrainte UNIQUE déjà occupée par la transaction pending originale)
        $wallet->credit(
            $amount > 0 ? $amount : (float) $transaction->amount,
            'recharge',
            $transaction->label ?? 'Recharge confirmée',
            [
                'payment_method' => $transaction->payment_method,
                'external_id'    => $transaction->external_id . '-confirmed',
            ]
        );

        // Mettre à jour le balance_after (payment_status déjà mis à 'success' ci-dessus)
        $transaction->update([
            'balance_after' => (float) $wallet->balance,
        ]);

        Log::info('Freemopay: Recharge créditée', [
            'user_id' => $wallet->user_id,
            'amount'  => $amount,
            'balance' => $wallet->balance,
        ]);

        // Notification push
        try {
            $pushService = new ExpoPushNotificationService();
            $pushService->createAndSendNotification(
                $wallet->user_id,
                'wallet_recharge',
                '💳 Recharge réussie !',
                'Votre portefeuille a été rechargé de ' . number_format($amount, 0, ',', ' ') . ' FCFA.',
                ['new_balance' => (float) $wallet->balance]
            );
        } catch (\Exception $e) {
            Log::warning('Push notification recharge failed: ' . $e->getMessage());
        }

        // Notification WhatsApp de confirmation de recharge
        try {
            $user = User::find($wallet->user_id);
            if ($user && !empty($user->phone)) {
                $creditedAmount = $amount > 0 ? $amount : (float) $transaction->amount;
                $paymentMethodLabel = match ($transaction->payment_method) {
                    'mtn'    => 'MTN Mobile Money',
                    'orange' => 'Orange Money',
                    'card'   => 'Carte bancaire',
                    default  => strtoupper($transaction->payment_method ?? 'Mobile Money'),
                };
                $whatsapp = new WhatsAppService();
                $whatsapp->sendWalletRecharge($user->phone, [
                    'user_name'      => $user->name,
                    'amount'         => number_format($creditedAmount, 0, ',', ' '),
                    'payment_method' => $paymentMethodLabel,
                    'new_balance'    => number_format((float) $wallet->balance, 0, ',', ' '),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('WhatsApp recharge notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Traiter un paiement échoué
     */
    protected function handleFailure(WalletTransaction $transaction, string $message): void
    {
        $transaction->update([
            'payment_status' => 'failed',
            'description'    => $message,
        ]);

        Log::warning('Freemopay: Paiement échoué', [
            'transaction_id' => $transaction->id,
            'message'        => $message,
        ]);

        // Notification push
        try {
            $pushService = new ExpoPushNotificationService();
            $pushService->createAndSendNotification(
                $transaction->user_id,
                'wallet_recharge_failed',
                '❌ Recharge échouée',
                'La recharge de ' . number_format($transaction->amount, 0, ',', ' ') . ' FCFA a échoué. ' . $message,
                []
            );
        } catch (\Exception $e) {
            Log::warning('Push notification recharge failure failed: ' . $e->getMessage());
        }
    }
}
