<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users',
            'email' => 'nullable|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'is_driver' => 'boolean',
            'language' => 'string|in:fr,en',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
        ]);

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $avatarPath = $file->storeAs('avatars', $filename, 'public');
        }

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_driver' => $validated['is_driver'] ?? false,
            'language' => $validated['language'] ?? 'fr',
            'avatar' => $avatarPath,
            'phone_verified' => false,
            'email_verified' => false,
            'is_verified' => false,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $validated['phone'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('roles'),
        ]);
    }

    /**
     * Send phone verification code
     */
    public function sendPhoneVerification(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        // TODO: Implement SMS sending logic
        $code = rand(100000, 999999);

        // Store code in cache for 10 minutes
        cache()->put("phone_verification_{$validated['phone']}", $code, 600);

        return response()->json([
            'success' => true,
            'message' => 'Code de vérification envoyé',
            'code' => $code, // Remove this in production
        ]);
    }

    /**
     * Verify phone number
     */
    public function verifyPhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $cachedCode = cache()->get("phone_verification_{$validated['phone']}");

        if (!$cachedCode || $cachedCode != $validated['code']) {
            throw ValidationException::withMessages([
                'code' => ['Le code de vérification est incorrect ou expiré.'],
            ]);
        }

        $user = User::where('phone', $validated['phone'])->first();

        if ($user) {
            $user->update([
                'phone_verified' => true,
                'is_verified' => true,
            ]);

            cache()->forget("phone_verification_{$validated['phone']}");

            return response()->json([
                'success' => true,
                'message' => 'Numéro de téléphone vérifié avec succès',
                'data' => $user,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Utilisateur non trouvé',
        ], 404);
    }

    /**
     * Request password reset
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|exists:users,phone',
        ]);

        // TODO: Implement SMS sending logic
        $code = rand(100000, 999999);

        // Store code in cache for 10 minutes
        cache()->put("password_reset_{$validated['phone']}", $code, 600);

        return response()->json([
            'success' => true,
            'message' => 'Code de réinitialisation envoyé',
            'code' => $code, // Remove this in production
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|exists:users,phone',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $cachedCode = cache()->get("password_reset_{$validated['phone']}");

        if (!$cachedCode || $cachedCode != $validated['code']) {
            throw ValidationException::withMessages([
                'code' => ['Le code de réinitialisation est incorrect ou expiré.'],
            ]);
        }

        $user = User::where('phone', $validated['phone'])->first();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        cache()->forget("password_reset_{$validated['phone']}");

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès',
        ]);
    }
}
