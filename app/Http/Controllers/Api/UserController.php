<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load(['bookings', 'vehicles', 'rideshareTrips']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'loyalty_status' => Setting::getUserStatus($user->loyalty_points),
                'stats' => [
                    'total_bookings' => $user->bookings()->count(),
                    'total_vehicles' => $user->vehicles()->count(),
                    'total_rideshares' => $user->rideshareTrips()->count(),
                ],
            ],
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'language' => 'sometimes|string|in:fr,en',
            'theme' => 'sometimes|string|in:light,dark',
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar mis à jour avec succès',
            'data' => [
                'avatar' => $path,
                'avatar_url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Get user bookings
     */
    public function myBookings(Request $request)
    {
        $status = $request->query('status'); // pending, completed, cancelled

        $query = $request->user()->bookings()
            ->with(['busTrip.company', 'busTrip.fromCity', 'busTrip.toCity'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Get user loyalty information
     */
    public function loyaltyInfo(Request $request)
    {
        $user = $request->user();

        $silverThreshold = Setting::get('silver_threshold', 500);
        $goldThreshold = Setting::get('gold_threshold', 1000);
        $platinumThreshold = Setting::get('platinum_threshold', 2000);

        $currentStatus = Setting::getUserStatus($user->loyalty_points);

        // Calculate points needed for next level
        $pointsToNext = null;
        $nextLevel = null;

        if ($user->loyalty_points < $silverThreshold) {
            $pointsToNext = $silverThreshold - $user->loyalty_points;
            $nextLevel = 'Silver';
        } elseif ($user->loyalty_points < $goldThreshold) {
            $pointsToNext = $goldThreshold - $user->loyalty_points;
            $nextLevel = 'Gold';
        } elseif ($user->loyalty_points < $platinumThreshold) {
            $pointsToNext = $platinumThreshold - $user->loyalty_points;
            $nextLevel = 'Platinum';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => $user->loyalty_points,
                'current_status' => $currentStatus,
                'total_trips' => $user->total_trips,
                'free_trips_available' => $user->free_trips_available,
                'points_to_next_level' => $pointsToNext,
                'next_level' => $nextLevel,
                'thresholds' => [
                    'bronze' => 0,
                    'silver' => $silverThreshold,
                    'gold' => $goldThreshold,
                    'platinum' => $platinumThreshold,
                ],
                'points_per_trip' => Setting::get('points_per_trip', 10),
            ],
        ]);
    }
}
