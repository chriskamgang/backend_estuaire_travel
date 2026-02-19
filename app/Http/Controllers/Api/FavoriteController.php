<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\BusTrip;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Récupérer tous les favoris de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with('favoritable')
            ->latest()
            ->get();

        // Charger les relations selon le type après récupération
        $favorites->each(function ($favorite) {
            if ($favorite->favoritable_type === 'App\\Models\\BusTrip' && $favorite->favoritable) {
                $favorite->favoritable->load(['company', 'fromCity', 'toCity']);
            }
        });

        // Formatter les favoris pour l'API
        $formattedFavorites = $favorites->map(function ($favorite) {
            return [
                'id' => $favorite->id,
                'type' => $favorite->favoritable_type,
                'item' => $favorite->favoritable,
                'created_at' => $favorite->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'favorites' => $formattedFavorites,
        ]);
    }

    /**
     * Ajouter un favori
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'favoritable_type' => 'required|string|in:App\\Models\\BusTrip,App\\Models\\Company',
            'favoritable_id' => 'required|integer',
        ]);

        // Vérifier si le favori existe déjà
        $existingFavorite = Favorite::where('user_id', $user->id)
            ->where('favoritable_type', $validated['favoritable_type'])
            ->where('favoritable_id', $validated['favoritable_id'])
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élément est déjà dans vos favoris',
            ], 400);
        }

        // Créer le favori
        $favorite = Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => $validated['favoritable_type'],
            'favoritable_id' => $validated['favoritable_id'],
        ]);

        $favorite->load('favoritable');

        return response()->json([
            'success' => true,
            'message' => 'Ajouté aux favoris',
            'favorite' => [
                'id' => $favorite->id,
                'type' => $favorite->favoritable_type,
                'item' => $favorite->favoritable,
                'created_at' => $favorite->created_at,
            ],
        ], 201);
    }

    /**
     * Retirer un favori
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Favori non trouvé',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Retiré des favoris',
        ]);
    }

    /**
     * Vérifier si un élément est dans les favoris
     */
    public function check(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'favoritable_type' => 'required|string',
            'favoritable_id' => 'required|integer',
        ]);

        $favorite = Favorite::where('user_id', $user->id)
            ->where('favoritable_type', $validated['favoritable_type'])
            ->where('favoritable_id', $validated['favoritable_id'])
            ->first();

        return response()->json([
            'success' => true,
            'is_favorite' => $favorite !== null,
            'favorite_id' => $favorite?->id,
        ]);
    }
}
