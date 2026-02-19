<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\ExpoPushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    /**
     * POST /wallet/paypal/create-order
     * Crée une commande PayPal et retourne l'URL d'approbation
     *
     * Body: { amount: 5000 }
     * Note: amount en FCFA, converti en USD (1 USD ≈ 600 FCFA)
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500|max:1000000',
        ]);

        $user   = $request->user();
        $wallet = Wallet::getOrCreate($user->id);

        $amountFCFA = (float) $validated['amount'];
        // Conversion FCFA → USD (taux approximatif)
        $amountUSD  = round($amountFCFA / 600, 2);
        if ($amountUSD < 1) {
            $amountUSD = 1.00; // minimum PayPal
        }

        $externalId = 'PAYPAL-' . $user->id . '-' . Str::uuid();

        // Créer la transaction en attente
        $transaction = WalletTransaction::create([
            'wallet_id'      => $wallet->id,
            'user_id'        => $user->id,
            'type'           => 'recharge',
            'amount'         => $amountFCFA,
            'balance_before' => (float) $wallet->balance,
            'balance_after'  => (float) $wallet->balance,
            'label'          => 'Recharge PayPal',
            'payment_method' => 'paypal',
            'external_id'    => $externalId,
            'payment_status' => 'pending',
        ]);

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $externalId,
                        'description'  => 'Recharge portefeuille Estuaire Travel - ' . number_format($amountFCFA, 0, ',', ' ') . ' FCFA',
                        'amount'       => [
                            'currency_code' => 'USD',
                            'value'         => number_format($amountUSD, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url'   => config('app.url') . '/api/wallet/paypal/success?transaction_id=' . $transaction->id . '&ngrok-skip-browser-warning=true',
                    'cancel_url'   => config('app.url') . '/api/wallet/paypal/cancel?transaction_id=' . $transaction->id . '&ngrok-skip-browser-warning=true',
                    'brand_name'   => 'Estuaire Travel',
                    'user_action'  => 'PAY_NOW',
                    'landing_page' => 'BILLING',        // Afficher le formulaire carte en premier (pas la page de connexion)
                    'shipping_preference' => 'NO_SHIPPING', // Pas besoin d'adresse de livraison
                ],
            ];

            $response = $provider->createOrder($orderData);

            if (isset($response['id']) && $response['status'] === 'CREATED') {
                // Sauvegarder l'order ID PayPal dans la transaction
                $transaction->update([
                    'freemopay_reference' => $response['id'], // réutilise le champ pour l'order ID
                ]);

                // Récupérer l'URL d'approbation
                $approvalUrl = collect($response['links'])
                    ->firstWhere('rel', 'approve')['href'] ?? null;

                return response()->json([
                    'success'        => true,
                    'message'        => 'Commande PayPal créée',
                    'order_id'       => $response['id'],
                    'approval_url'   => $approvalUrl,
                    'amount_fcfa'    => $amountFCFA,
                    'amount_usd'     => $amountUSD,
                    'transaction_id' => $transaction->id,
                ]);
            }

            $transaction->update(['payment_status' => 'failed']);
            Log::error('PayPal createOrder failed', ['response' => $response]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de créer la commande PayPal. Veuillez réessayer.',
            ], 422);

        } catch (\Exception $e) {
            $transaction->update(['payment_status' => 'failed']);
            Log::error('PayPal exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur PayPal : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /wallet/paypal/success
     * Callback PayPal après paiement approuvé (retour WebView)
     * Capture le paiement et crédite le wallet
     */
    public function captureOrder(Request $request)
    {
        $transactionId = $request->query('transaction_id');
        $orderId       = $request->query('token'); // PayPal envoie token=ORDER_ID

        if (!$transactionId || !$orderId) {
            return response()->json(['success' => false, 'message' => 'Paramètres manquants'], 400);
        }

        $transaction = WalletTransaction::find($transactionId);

        if (!$transaction || $transaction->payment_status !== 'pending') {
            return $this->htmlResponse('Erreur', 'Transaction introuvable ou déjà traitée.', false);
        }

        $user   = \App\Models\User::find($transaction->user_id);
        $wallet = Wallet::getOrCreate($transaction->user_id);

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $response = $provider->capturePaymentOrder($orderId);

            if (isset($response['status']) && $response['status'] === 'COMPLETED') {
                $amountFCFA = (float) $transaction->amount;

                // Créditer le wallet
                $wallet->credit(
                    $amountFCFA,
                    'recharge',
                    'Recharge PayPal',
                    [
                        'payment_method' => 'paypal',
                        'paypal_order_id' => $orderId,
                        'external_id'    => $transaction->external_id . '-confirmed',
                    ]
                );

                $transaction->update([
                    'payment_status' => 'success',
                    'balance_after'  => (float) $wallet->balance,
                ]);

                // Notification push
                try {
                    $pushService = new ExpoPushNotificationService();
                    $pushService->createAndSendNotification(
                        $transaction->user_id,
                        'wallet_recharge',
                        '💳 Recharge réussie !',
                        'Votre portefeuille a été rechargé de ' . number_format($amountFCFA, 0, ',', ' ') . ' FCFA via PayPal.',
                        ['new_balance' => (float) $wallet->balance]
                    );
                } catch (\Exception $e) {
                    Log::warning('Push notification PayPal failed: ' . $e->getMessage());
                }

                Log::info('PayPal payment captured', [
                    'user_id'     => $transaction->user_id,
                    'amount_fcfa' => $amountFCFA,
                    'order_id'    => $orderId,
                    'new_balance' => $wallet->balance,
                ]);

                // Ouvrir l'app via deep link (page HTML intermédiaire requise sur iOS)
                // En dev Expo Go : exp://IP:8081/--/paypal/success  |  En prod : estuairetravel://paypal/success
                $deepLinkBase = env('PAYPAL_DEEP_LINK_BASE', 'estuairetravel://');
                $deepLink = $deepLinkBase . '/paypal/success?transaction_id=' . $transaction->id . '&new_balance=' . (float) $wallet->balance;
                return $this->deepLinkResponse($deepLink, true, number_format($amountFCFA, 0, ',', ' ') . ' FCFA rechargés !');
            }

            $transaction->update(['payment_status' => 'failed']);
            $deepLinkBase = env('PAYPAL_DEEP_LINK_BASE', 'estuairetravel://');
            $deepLink = $deepLinkBase . '/paypal/cancel?transaction_id=' . $transactionId . '&reason=failed';
            return $this->deepLinkResponse($deepLink, false, 'Le paiement n\'a pas pu être confirmé.');

        } catch (\Exception $e) {
            Log::error('PayPal capture exception: ' . $e->getMessage());
            $deepLinkBase = env('PAYPAL_DEEP_LINK_BASE', 'estuairetravel://');
            $deepLink = $deepLinkBase . '/paypal/cancel?transaction_id=' . ($transactionId ?? '0') . '&reason=error';
            return $this->deepLinkResponse($deepLink, false, 'Une erreur est survenue.');
        }
    }

    /**
     * GET /wallet/paypal/cancel
     * Callback si l'utilisateur annule le paiement PayPal
     */
    public function cancelOrder(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        if ($transactionId) {
            WalletTransaction::where('id', $transactionId)
                ->where('payment_status', 'pending')
                ->update(['payment_status' => 'failed']);
        }

        $deepLinkBase = env('PAYPAL_DEEP_LINK_BASE', 'estuairetravel://');
        $deepLink = $deepLinkBase . '/paypal/cancel?transaction_id=' . ($transactionId ?? '') . '&reason=cancelled';
        return $this->deepLinkResponse($deepLink, false, 'Paiement annulé.');
    }

    /**
     * POST /wallet/paypal/check-status
     * Vérifier le statut d'une transaction PayPal (polling depuis l'app)
     *
     * Body: { transaction_id: 12 }
     */
    public function checkStatus(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|integer',
        ]);

        $user        = $request->user();
        $wallet      = Wallet::getOrCreate($user->id);
        $transaction = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('id', $validated['transaction_id'])
            ->where('payment_method', 'paypal')
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction introuvable'], 404);
        }

        if ($transaction->payment_status === 'success') {
            return response()->json([
                'success'     => true,
                'status'      => 'success',
                'new_balance' => (float) $wallet->balance,
                'message'     => 'Recharge PayPal créditée !',
            ]);
        }

        if ($transaction->payment_status === 'failed') {
            return response()->json([
                'success' => false,
                'status'  => 'failed',
                'message' => 'Paiement annulé ou échoué',
            ]);
        }

        return response()->json([
            'success' => true,
            'status'  => 'pending',
            'message' => 'En attente de confirmation PayPal',
        ]);
    }

    /**
     * Page HTML intermédiaire qui ouvre l'app via deep link
     * Nécessaire sur iOS : Safari bloque les redirects 302 vers des custom schemes
     */
    private function deepLinkResponse(string $deepLink, bool $success, string $message): \Illuminate\Http\Response
    {
        $color = $success ? '#10B981' : '#EF4444';
        $icon  = $success ? '✅' : '❌';
        $title = $success ? 'Paiement réussi !' : 'Paiement annulé';
        $safeDeepLink = htmlspecialchars($deepLink, ENT_QUOTES);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
    .card { background: white; border-radius: 16px; padding: 40px 32px; text-align: center; max-width: 360px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
    .icon { font-size: 64px; margin-bottom: 16px; }
    h1 { color: {$color}; font-size: 22px; margin: 0 0 12px; }
    p { color: #666; font-size: 16px; line-height: 1.5; margin: 0 0 24px; }
    .btn { background: {$color}; color: white; border: none; border-radius: 12px; padding: 14px 32px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; text-decoration: none; display: block; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">{$icon}</div>
    <h1>{$title}</h1>
    <p>{$message}</p>
    <a class="btn" href="{$safeDeepLink}">Retourner à l'application</a>
  </div>
  <script>
    // Ouvrir le deep link automatiquement
    window.location.href = '{$safeDeepLink}';
    // Fallback : si l'app ne s'ouvre pas, afficher le bouton
  </script>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /**
     * Page HTML de retour pour la WebView (success/cancel) — conservé pour compatibilité
     */
    private function htmlResponse(string $title, string $message, bool $success, int $transactionId = 0, float $balance = 0): \Illuminate\Http\Response
    {
        $color  = $success ? '#10B981' : '#EF4444';
        $icon   = $success ? '✅' : '❌';
        $js     = $success
            ? "window.ReactNativeWebView?.postMessage(JSON.stringify({success:true,transaction_id:{$transactionId},new_balance:{$balance}}));"
            : "window.ReactNativeWebView?.postMessage(JSON.stringify({success:false}));";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
    .card { background: white; border-radius: 16px; padding: 40px 32px; text-align: center; max-width: 360px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
    .icon { font-size: 64px; margin-bottom: 16px; }
    h1 { color: {$color}; font-size: 22px; margin: 0 0 12px; }
    p { color: #666; font-size: 16px; line-height: 1.5; margin: 0 0 24px; }
    .close-btn { background: {$color}; color: white; border: none; border-radius: 12px; padding: 14px 32px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">{$icon}</div>
    <h1>{$title}</h1>
    <p>{$message}</p>
    <button class="close-btn" onclick="closeWebView()">Fermer</button>
  </div>
  <script>
    function closeWebView() { {$js} }
    // Auto-close après 3 secondes
    setTimeout(closeWebView, 3000);
  </script>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
