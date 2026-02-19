<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RideshareTrip;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverDashboardController extends Controller
{
    /**
     * Obtenir les statistiques du dashboard chauffeur
     */
    public function getStats(Request $request)
    {
        $driver = $request->user();

        // Statistiques des trajets
        $totalTrips = RideshareTrip::where('driver_id', $driver->id)->count();
        $activeTrips = RideshareTrip::where('driver_id', $driver->id)
            ->where('status', 'in_progress')
            ->count();
        $completedTrips = RideshareTrip::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->count();

        // Revenus (somme des prix des trajets complétés * places réservées)
        $totalRevenue = DB::table('rideshare_trips')
            ->join('rideshare_bookings', 'rideshare_trips.id', '=', 'rideshare_bookings.trip_id')
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_trips.status', 'completed')
            ->where('rideshare_bookings.status', 'confirmed')
            ->sum('rideshare_bookings.total_price') ?: 0;

        // Revenus du mois en cours
        $monthlyRevenue = DB::table('rideshare_trips')
            ->join('rideshare_bookings', 'rideshare_trips.id', '=', 'rideshare_bookings.trip_id')
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_trips.status', 'completed')
            ->where('rideshare_bookings.status', 'confirmed')
            ->whereMonth('rideshare_trips.created_at', now()->month)
            ->whereYear('rideshare_trips.created_at', now()->year)
            ->sum('rideshare_bookings.total_price') ?: 0;

        // Note moyenne (si vous avez un système d'évaluation)
        // Afficher seulement si le chauffeur a des trajets complétés
        $averageRating = $completedTrips > 0 ? 4.8 : 0; // TODO: Implémenter le vrai système de notation

        // Nombre de passagers transportés
        $totalPassengers = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_bookings.status', 'confirmed')
            ->sum('rideshare_bookings.seats_requested') ?: 0;

        // Taux d'acceptation
        $totalRequests = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->where('rideshare_trips.driver_id', $driver->id)
            ->count();
        $acceptedRequests = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_bookings.status', 'confirmed')
            ->count();
        $acceptanceRate = $totalRequests > 0 ? round(($acceptedRequests / $totalRequests) * 100) : 0;

        // Statistiques par période (7 derniers jours)
        $weeklyStats = DB::table('rideshare_trips')
            ->leftJoin('rideshare_bookings', function($join) {
                $join->on('rideshare_trips.id', '=', 'rideshare_bookings.trip_id')
                     ->where('rideshare_bookings.status', '=', 'confirmed');
            })
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_trips.created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(rideshare_trips.created_at) as date, COUNT(DISTINCT rideshare_trips.id) as trips, COALESCE(SUM(rideshare_bookings.total_price), 0) as revenue')
            ->groupByRaw('DATE(rideshare_trips.created_at)')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'driver' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'email' => $driver->email,
                    'avatar' => $driver->avatar ? asset('storage/' . $driver->avatar) : null,
                ],
                'stats' => [
                    'total_trips' => $totalTrips,
                    'active_trips' => $activeTrips,
                    'completed_trips' => $completedTrips,
                    'total_revenue' => $totalRevenue,
                    'monthly_revenue' => $monthlyRevenue,
                    'average_rating' => $averageRating,
                    'total_passengers' => $totalPassengers,
                    'acceptance_rate' => $acceptanceRate,
                ],
                'weekly_stats' => $weeklyStats,
            ],
        ]);
    }

    /**
     * Obtenir les trajets actifs du chauffeur
     */
    public function getActiveTrips(Request $request)
    {
        $driver = $request->user();

        $trips = RideshareTrip::where('driver_id', $driver->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->with(['vehicle', 'bookings'])
            ->orderBy('date', 'asc')
            ->orderBy('departure_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trips,
        ]);
    }

    /**
     * Obtenir l'historique des trajets
     */
    public function getTripHistory(Request $request)
    {
        $driver = $request->user();
        $status = $request->input('status', 'all'); // all, completed, cancelled

        $query = RideshareTrip::where('driver_id', $driver->id)
            ->with(['vehicle', 'bookings']);

        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['completed', 'cancelled']);
        }

        $trips = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $trips,
        ]);
    }

    /**
     * Obtenir les demandes de réservation en attente
     */
    public function getPendingBookings(Request $request)
    {
        $driver = $request->user();

        $bookings = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->join('users', 'rideshare_bookings.user_id', '=', 'users.id')
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_bookings.status', 'pending')
            ->select(
                'rideshare_bookings.*',
                'users.name as passenger_name',
                'users.phone as passenger_phone',
                'users.avatar as passenger_avatar',
                'rideshare_trips.from_city as departure_location',
                'rideshare_trips.to_city as destination',
                'rideshare_trips.date',
                'rideshare_trips.departure_time'
            )
            ->orderBy('rideshare_bookings.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Mettre à jour la position du chauffeur
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $driver = $request->user();

        // Mettre à jour la position dans le profil utilisateur
        // ou créer une table séparée pour les positions en temps réel
        $driver->update([
            'last_latitude' => $request->latitude,
            'last_longitude' => $request->longitude,
            'last_location_update' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Position mise à jour',
        ]);
    }

    /**
     * Activer/désactiver le mode "En ligne"
     */
    public function toggleOnlineStatus(Request $request)
    {
        $driver = $request->user();
        $isOnline = $request->input('is_online', true);

        $driver->update([
            'is_online' => $isOnline,
        ]);

        return response()->json([
            'success' => true,
            'message' => $isOnline ? 'Vous êtes maintenant en ligne' : 'Vous êtes maintenant hors ligne',
            'is_online' => $isOnline,
        ]);
    }
}
