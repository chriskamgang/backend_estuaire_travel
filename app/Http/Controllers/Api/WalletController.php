<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\FreemopayService;
use App\Services\ExpoPushNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    /**
     * GET /wallet
     * Récupérer le solde et les infos du wallet de l'utilisateur connecté
     */
    public function show(Request $request)
    {
        $wallet = Wallet::getOrCreate($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'balance'        => (float) $wallet->balance,
                'currency'       => $wallet->currency,
                'is_active'      => $wallet->is_active,
                'transfer_used'  => $wallet->transfer_used,
            ],
        ]);
    }

    /**
     * GET /wallet/transactions
     * Historique des transactions paginé
     */
    public function transactions(Request $request)
    {
        $wallet = Wallet::getOrCreate($request->user()->id);

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    /**
     * POST /wallet/recharge
     * Initier une recharge via Freemopay
     *
     * Body: { amount: 5000, payment_method: 'mtn'|'orange'|'card', phone?: '...' }
     */
    public function recharge(Request $request)
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:100|max:1000000',
            'payment_method' => 'required|in:mtn,orange,card',
            'phone'          => 'nullable|string|max:20',
        ]);

        $user   = $request->user();
        $wallet = Wallet::getOrCreate($user->id);

        // Utiliser le numéro fourni ou celui du profil
        $phone = $validated['phone'] ?? $user->phone;
        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Numéro de téléphone requis pour la recharge Mobile Money.',
            ], 422);
        }

        $amount     = (float) $validated['amount'];
        $externalId = 'RECHARGE-' . $user->id . '-' . Str::uuid();
        $callback   = config('app.url') . '/api/webhook/freemopay';

        // Créer la transaction en PENDING avant d'appeler l'API
        $transaction = WalletTransaction::create([
            'wallet_id'      => $wallet->id,
            'user_id'        => $user->id,
            'type'           => 'recharge',
            'amount'         => $amount,
            'balance_before' => (float) $wallet->balance,
            'balance_after'  => (float) $wallet->balance, // sera mis à jour après confirmation
            'label'          => 'Recharge ' . strtoupper($validated['payment_method']),
            'payment_method' => $validated['payment_method'],
            'external_id'    => $externalId,
            'payment_status' => 'pending',
        ]);

        // Appeler Freemopay
        $freemopay = new FreemopayService();
        $result    = $freemopay->initiatePayment($phone, $amount, $externalId, $callback);

        if (!$result['success']) {
            // Marquer la transaction comme échouée
            $transaction->update(['payment_status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        // Sauvegarder la référence Freemopay
        $transaction->update([
            'freemopay_reference' => $result['reference'],
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Recharge initiée. Validez le paiement sur votre téléphone.',
            'reference' => $result['reference'],
            'data'      => [
                'transaction_id' => $transaction->id,
                'amount'         => $amount,
                'status'         => 'pending',
            ],
        ]);
    }

    /**
     * POST /wallet/transfer
     * Transfert unique depuis le wallet vers un numéro Mobile Money
     *
     * Body: { phone: '...', amount: 5000, note?: '...' }
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'phone'  => 'required|string|max:20',
            'amount' => 'required|numeric|min:100',
            'note'   => 'nullable|string|max:200',
        ]);

        $user   = $request->user();
        $wallet = Wallet::getOrCreate($user->id);

        $amount = (float) $validated['amount'];

        // Vérifier le solde
        if ($wallet->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Solde insuffisant. Solde disponible : ' . number_format($wallet->balance, 0, ',', ' ') . ' FCFA',
            ], 422);
        }

        $externalId = 'TRANSFER-' . $user->id . '-' . Str::uuid();
        $callback   = config('app.url') . '/api/webhook/freemopay';

        // Débiter le wallet immédiatement
        try {
            $transaction = $wallet->debit($amount, 'transfer', 'Transfert vers ' . $validated['phone'], [
                'transfer_to_phone' => $validated['phone'],
                'description'       => $validated['note'] ?? null,
                'external_id'       => $externalId,
                'payment_method'    => 'mtn', // Freemopay gère MTN/Orange automatiquement
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // Lancer le withdraw via Freemopay
        $freemopay = new FreemopayService();
        $result    = $freemopay->withdraw($validated['phone'], $amount, $externalId, $callback);

        if (!$result['success']) {
            // Rembourser si le withdraw échoue
            $wallet->credit($amount, 'refund', 'Remboursement transfert échoué', [
                'description' => 'Remboursement automatique suite à échec du transfert vers ' . $validated['phone'],
            ]);
            $transaction->update(['payment_status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        $transaction->update([
            'freemopay_reference' => $result['reference'],
            'payment_status'      => 'success',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transfert effectué avec succès vers ' . $validated['phone'],
            'data'    => [
                'transaction_id' => $transaction->id,
                'amount'         => $amount,
                'to'             => $validated['phone'],
                'new_balance'    => (float) $wallet->balance,
            ],
        ]);
    }

    /**
     * POST /wallet/recharge/check-status
     * Vérifier manuellement le statut d'une recharge en attente
     * (utilisé pour le polling quand le webhook ne peut pas être reçu en local)
     *
     * Body: { transaction_id: 12 }
     */
    public function checkRecharge(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|integer',
        ]);

        $user        = $request->user();
        $wallet      = Wallet::getOrCreate($user->id);
        $transaction = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('id', $validated['transaction_id'])
            ->where('type', 'recharge')
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction introuvable'], 404);
        }

        // Déjà traitée
        if ($transaction->payment_status === 'success') {
            return response()->json([
                'success'     => true,
                'status'      => 'success',
                'new_balance' => (float) $wallet->balance,
                'message'     => 'Recharge déjà créditée',
            ]);
        }

        if ($transaction->payment_status === 'failed') {
            return response()->json([
                'success' => false,
                'status'  => 'failed',
                'message' => 'La recharge a échoué',
            ]);
        }

        // Interroger Freemopay pour le statut réel
        if (empty($transaction->freemopay_reference)) {
            return response()->json([
                'success' => false,
                'status'  => 'pending',
                'message' => 'En attente de confirmation',
            ]);
        }

        $freemopay     = new FreemopayService();
        $freemopayData = $freemopay->getPaymentStatus($transaction->freemopay_reference);
        $freemopayStatus = $freemopayData['status'] ?? 'CREATED';

        if ($freemopayStatus === 'SUCCESS') {
            // Re-vérifier le statut avec un verrou pour éviter la race condition
            // entre le webhook et le polling (les deux peuvent arriver en même temps)
            $transaction->refresh();
            if ($transaction->payment_status === 'success') {
                return response()->json([
                    'success'     => true,
                    'status'      => 'success',
                    'new_balance' => (float) $wallet->fresh()->balance,
                    'message'     => 'Recharge déjà créditée',
                ]);
            }

            // Marquer immédiatement comme success avant de créditer (idempotence)
            $updated = $transaction->newQuery()
                ->where('id', $transaction->id)
                ->where('payment_status', 'pending')
                ->update(['payment_status' => 'success']);

            // Si 0 lignes mises à jour → une autre requête l'a déjà fait (webhook concurrent)
            if ($updated === 0) {
                $transaction->refresh();
                return response()->json([
                    'success'     => true,
                    'status'      => 'success',
                    'new_balance' => (float) $wallet->fresh()->balance,
                    'message'     => 'Recharge déjà créditée',
                ]);
            }

            // Créditer le wallet (une seule fois grâce au verrou ci-dessus)
            // Note: ne pas re-mettre freemopay_reference ici (contrainte UNIQUE déjà sur la transaction pending)
            $amount = (float) $transaction->amount;
            $wallet->credit(
                $amount,
                'recharge',
                $transaction->label ?? 'Recharge confirmée',
                [
                    'payment_method' => $transaction->payment_method,
                    'external_id'    => $transaction->external_id . '-confirmed',
                ]
            );
            $transaction->update([
                'balance_after' => (float) $wallet->balance,
            ]);

            // Notification push
            try {
                $pushService = new ExpoPushNotificationService();
                $pushService->createAndSendNotification(
                    $user->id,
                    'wallet_recharge',
                    '💳 Recharge réussie !',
                    'Votre portefeuille a été rechargé de ' . number_format($amount, 0, ',', ' ') . ' FCFA.',
                    ['new_balance' => (float) $wallet->balance]
                );
            } catch (\Exception $e) {
                Log::warning('Push notification checkRecharge failed: ' . $e->getMessage());
            }

            // WhatsApp
            try {
                if (!empty($user->phone)) {
                    $paymentMethodLabel = match ($transaction->payment_method) {
                        'mtn'    => 'MTN Mobile Money',
                        'orange' => 'Orange Money',
                        'card'   => 'Carte bancaire',
                        default  => strtoupper($transaction->payment_method ?? 'Mobile Money'),
                    };
                    $whatsapp = new WhatsAppService();
                    $whatsapp->sendWalletRecharge($user->phone, [
                        'user_name'      => $user->name,
                        'amount'         => number_format($amount, 0, ',', ' '),
                        'payment_method' => $paymentMethodLabel,
                        'new_balance'    => number_format((float) $wallet->balance, 0, ',', ' '),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('WhatsApp checkRecharge notification failed: ' . $e->getMessage());
            }

            return response()->json([
                'success'     => true,
                'status'      => 'success',
                'new_balance' => (float) $wallet->balance,
                'message'     => 'Recharge créditée avec succès !',
            ]);
        }

        if (in_array($freemopayStatus, ['FAILED', 'REJECTED', 'CANCELLED'])) {
            $transaction->update(['payment_status' => 'failed']);
            return response()->json([
                'success' => false,
                'status'  => 'failed',
                'message' => 'Paiement refusé ou annulé',
            ]);
        }

        // Si la transaction est en attente depuis plus de 10 minutes → expirée
        if ($transaction->created_at->lt(now()->subMinutes(10))) {
            $transaction->update(['payment_status' => 'failed']);
            return response()->json([
                'success' => false,
                'status'  => 'failed',
                'message' => 'Délai de confirmation expiré. Veuillez réessayer.',
            ]);
        }

        // Encore en attente
        return response()->json([
            'success' => true,
            'status'  => 'pending',
            'message' => 'En attente de confirmation Mobile Money',
        ]);
    }
}
