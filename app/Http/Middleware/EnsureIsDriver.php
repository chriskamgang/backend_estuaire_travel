<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsDriver
{
    /**
     * Vérifie que l'utilisateur authentifié est un chauffeur approuvé.
     * Utilise le champ is_driver du modèle User (plus fiable que Spatie guard).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        if (!$user->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux chauffeurs.',
            ], 403);
        }

        return $next($request);
    }
}
