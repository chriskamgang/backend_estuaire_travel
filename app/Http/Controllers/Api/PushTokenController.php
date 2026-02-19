<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PushTokenController extends Controller
{
    /**
     * Enregistrer ou mettre à jour un push token
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:ios,android',
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        try {
            // Vérifier si le token existe déjà pour cet utilisateur
            $pushToken = PushToken::where('user_id', $user->id)
                ->where('token', $request->token)
                ->first();

            if ($pushToken) {
                // Mettre à jour le token existant
                $pushToken->update([
                    'device_type' => $request->device_type,
                    'device_id' => $request->device_id,
                    'active' => true,
                    'last_used_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Push token mis à jour avec succès',
                    'push_token' => $pushToken,
                ]);
            }

            // Créer un nouveau token
            $pushToken = PushToken::create([
                'user_id' => $user->id,
                'token' => $request->token,
                'device_type' => $request->device_type,
                'device_id' => $request->device_id,
                'active' => true,
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push token enregistré avec succès',
                'push_token' => $pushToken,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du push token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Désactiver un push token
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        try {
            $pushToken = PushToken::where('user_id', $user->id)
                ->where('token', $request->token)
                ->first();

            if (!$pushToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Push token non trouvé',
                ], 404);
            }

            // Désactiver au lieu de supprimer (pour garder l'historique)
            $pushToken->deactivate();

            return response()->json([
                'success' => true,
                'message' => 'Push token désactivé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation du push token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lister tous les push tokens de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        try {
            $pushTokens = PushToken::where('user_id', $user->id)
                ->active()
                ->get();

            return response()->json([
                'success' => true,
                'push_tokens' => $pushTokens,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des push tokens',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
