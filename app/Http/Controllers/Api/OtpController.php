<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OtpController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Envoyer un code OTP au numéro de téléphone de l'utilisateur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:9|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Rechercher l'utilisateur par numéro de téléphone
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte associé à ce numéro de téléphone'
                ], 404);
            }

            // Si le téléphone est déjà vérifié
            if ($user->phone_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de téléphone est déjà vérifié'
                ], 400);
            }

            // Générer un code OTP
            $otp = SmsService::generateOtp(6);

            // Sauvegarder le code OTP et sa date d'expiration (10 minutes)
            $user->otp_code = $otp;
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->save();

            // Envoyer le SMS
            $result = $this->smsService->sendOtp($user->phone, $otp);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Code OTP envoyé avec succès',
                    'expires_in_minutes' => 10
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du SMS',
                'error' => $result['message']
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier le code OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:9|max:15',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Rechercher l'utilisateur
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte associé à ce numéro de téléphone'
                ], 404);
            }

            // Vérifier si le téléphone est déjà vérifié
            if ($user->phone_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de téléphone est déjà vérifié'
                ], 400);
            }

            // Vérifier si un code OTP existe
            if (!$user->otp_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun code OTP trouvé. Veuillez en demander un nouveau.'
                ], 400);
            }

            // Vérifier si le code OTP a expiré
            if (Carbon::now()->isAfter($user->otp_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le code OTP a expiré. Veuillez en demander un nouveau.'
                ], 400);
            }

            // Vérifier si le code OTP correspond
            if ($user->otp_code !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code OTP incorrect'
                ], 400);
            }

            // Marquer le téléphone comme vérifié
            $user->phone_verified = true;
            $user->phone_verified_at = Carbon::now();
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Numéro de téléphone vérifié avec succès',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'phone_verified' => $user->phone_verified,
                    'phone_verified_at' => $user->phone_verified_at
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renvoyer un code OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendOtp(Request $request)
    {
        // Utiliser la même logique que sendOtp
        return $this->sendOtp($request);
    }
}
