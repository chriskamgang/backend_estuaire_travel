<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * Mettre à jour le profil de l'utilisateur
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255|unique:users,email,' . $request->user()->id,
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $request->user()->id,
            'file' => 'sometimes|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
        ]);

        $user = $request->user();

        // Mise à jour des champs texte
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        // Gestion de l'upload de photo
        if ($request->hasFile('file')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Sauvegarder la nouvelle photo
            $file = $request->file('file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $avatarPath = $file->storeAs('avatars', $filename, 'public');
            $user->avatar = $avatarPath;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect',
            ], 422);
        }

        // Mettre à jour le mot de passe
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès',
        ]);
    }

    /**
     * Mettre à jour les préférences de langue
     */
    public function updateLanguage(Request $request)
    {
        $validated = $request->validate([
            'language' => 'required|string|in:fr,en',
        ]);

        $user = $request->user();
        $user->language = $validated['language'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Langue mise à jour avec succès',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Mettre à jour les préférences de notifications
     */
    public function updateNotificationPreferences(Request $request)
    {
        $validated = $request->validate([
            'push_notifications' => 'sometimes|boolean',
            'email_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
            'promo_notifications' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        // Récupérer les préférences existantes ou créer un tableau vide
        $preferences = $user->preferences ?? [];

        // Mettre à jour les préférences
        foreach ($validated as $key => $value) {
            $preferences[$key] = $value;
        }

        $user->preferences = $preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Préférences de notification mises à jour',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Supprimer le compte utilisateur
     */
    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Vérifier le mot de passe
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect',
            ], 422);
        }

        // Supprimer l'avatar si existe
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Révoquer tous les tokens
        $user->tokens()->delete();

        // Supprimer l'utilisateur
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé avec succès',
        ]);
    }
}
