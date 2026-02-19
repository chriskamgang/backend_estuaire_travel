<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RideshareTrip;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RideshareTripController extends Controller
{
    /**
     * Rechercher un trajet covoiturage (côté client)
     * Paramètres: from_city, to_city, date, passengers
     */
    public function search(Request $request)
    {
        $request->validate([
            'from_city'  => 'required|string|max:255',
            'to_city'    => 'required|string|max:255',
            'date'       => 'required|string',
            'passengers' => 'sometimes|integer|min:1|max:20',
        ]);

        $date       = Carbon::parse($request->date)->format('Y-m-d');
        $passengers = $request->input('passengers', 1);
        $today      = Carbon::today()->format('Y-m-d');
        $now        = Carbon::now()->format('H:i:s');

        $query = RideshareTrip::with(['driver:id,name,avatar,phone', 'vehicle:id,brand,model,year,color,license_plate,vehicle_type,has_ac,photo'])
            ->where('from_city', 'like', '%' . $request->from_city . '%')
            ->where('to_city', 'like', '%' . $request->to_city . '%')
            ->where('date', $date)
            ->where('available_seats', '>=', $passengers)
            ->whereIn('status', ['scheduled', 'active']);

        // Si la date cherchée est aujourd'hui, exclure les trajets dont l'heure est déjà passée
        if ($date === $today) {
            $query->where('departure_time', '>=', $now);
        }

        $trips = $query->orderBy('departure_time', 'asc')
            ->get()
            ->map(fn($t) => $this->formatTrip($t));

        return response()->json([
            'success' => true,
            'count'   => $trips->count(),
            'data'    => $trips,
            'search'  => [
                'from_city'  => $request->from_city,
                'to_city'    => $request->to_city,
                'date'       => $date,
                'passengers' => $passengers,
            ],
        ]);
    }

    /**
     * Trajets disponibles aujourd'hui
     */
    public function today(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $now   = Carbon::now()->format('H:i:s');

        $trips = RideshareTrip::with(['driver:id,name,avatar', 'vehicle:id,brand,model,year,color,license_plate,vehicle_type,has_ac'])
            ->where('date', $today)
            ->where('departure_time', '>=', $now)
            ->where('available_seats', '>=', 1)
            ->whereIn('status', ['scheduled', 'active'])
            ->orderBy('departure_time', 'asc')
            ->limit(20)
            ->get()
            ->map(fn($t) => $this->formatTrip($t));

        return response()->json([
            'success' => true,
            'date'    => $today,
            'count'   => $trips->count(),
            'data'    => $trips,
        ]);
    }

    /**
     * Trajets populaires (les plus réservés)
     */
    public function popular(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');

        $trips = RideshareTrip::with(['driver:id,name,avatar', 'vehicle:id,brand,model,year,color,license_plate,vehicle_type,has_ac'])
            ->withCount('bookings')
            ->where('date', '>=', $today)
            ->where('available_seats', '>=', 1)
            ->whereIn('status', ['scheduled', 'active'])
            ->orderByDesc('bookings_count')
            ->orderBy('date', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($t) => $this->formatTrip($t));

        return response()->json([
            'success' => true,
            'data'    => $trips,
        ]);
    }

    /**
     * Trajets proches de moi (par ville la plus proche)
     * Paramètres: latitude, longitude
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat   = (float) $request->latitude;
        $lng   = (float) $request->longitude;
        $today = Carbon::today()->format('Y-m-d');
        $now   = Carbon::now()->format('H:i:s');

        // Calcul de distance Haversine en SQL
        $trips = RideshareTrip::with(['driver:id,name,avatar', 'vehicle:id,brand,model,year,color,license_plate,vehicle_type,has_ac'])
            ->selectRaw("
                rideshare_trips.*,
                (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(departure_latitude))
                        * cos(radians(departure_longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(departure_latitude))
                    )
                ) AS distance_km
            ", [$lat, $lng, $lat])
            ->where(function ($q) use ($today, $now) {
                // Trajets futurs (date > aujourd'hui) OU (date = aujourd'hui ET heure pas encore passée)
                $q->where('date', '>', $today)
                  ->orWhere(function ($q2) use ($today, $now) {
                      $q2->where('date', $today)
                         ->where('departure_time', '>=', $now);
                  });
            })
            ->where('available_seats', '>=', 1)
            ->whereIn('status', ['scheduled', 'active'])
            ->where('departure_latitude', '!=', 0)
            ->having('distance_km', '<=', 50) // dans un rayon de 50 km
            ->orderBy('distance_km', 'asc')
            ->orderBy('date', 'asc')
            ->orderBy('departure_time', 'asc')
            ->limit(20)
            ->get()
            ->map(fn($t) => $this->formatTrip($t, true));

        return response()->json([
            'success' => true,
            'count'   => $trips->count(),
            'data'    => $trips,
        ]);
    }

    /**
     * Détails d'un trajet
     */
    public function show(string $id)
    {
        $trip = RideshareTrip::with([
            'driver:id,name,avatar,phone',
            'vehicle:id,brand,model,year,color,license_plate,vehicle_type,has_ac,photo,seats',
            'bookings' => fn($q) => $q->where('status', 'confirmed')->select('id', 'trip_id', 'seats_requested'),
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->formatTrip($trip, true),
        ]);
    }

    /**
     * Formater un trajet pour la réponse API
     */
    private function formatTrip(RideshareTrip $trip, bool $detailed = false): array
    {
        $data = [
            'id'               => $trip->id,
            'from_city'        => $trip->from_city,
            'to_city'          => $trip->to_city,
            'date'             => $trip->date,
            'departure_time'   => substr($trip->departure_time ?? '', 0, 5),
            'arrival_time'     => substr($trip->arrival_time ?? '', 0, 5),
            'duration'         => $trip->duration,
            'price_per_seat'   => (float) $trip->price_per_seat,
            'available_seats'  => $trip->available_seats,
            'total_seats'      => $trip->total_seats,
            'status'           => $trip->status,
            'additional_notes' => $trip->additional_notes,
            'driver'           => $trip->driver ? [
                'id'     => $trip->driver->id,
                'name'   => $trip->driver->name,
                'avatar' => $trip->driver->avatar,
                'phone'  => $detailed ? $trip->driver->phone : null,
            ] : null,
            'vehicle' => $trip->vehicle ? [
                'id'            => $trip->vehicle->id,
                'label'         => trim(($trip->vehicle->brand ?? '') . ' ' . ($trip->vehicle->model ?? '')),
                'brand'         => $trip->vehicle->brand ?? null,
                'model'         => $trip->vehicle->model ?? null,
                'year'          => $trip->vehicle->year ?? null,
                'color'         => $trip->vehicle->color ?? null,
                'license_plate' => $trip->vehicle->license_plate ?? null,
                'vehicle_type'  => $trip->vehicle->vehicle_type ?? null,
                'has_ac'        => $trip->vehicle->has_ac ?? false,
                'photo'         => $trip->vehicle->photo ?? null,
            ] : null,
        ];

        if ($detailed) {
            $data['departure_address']  = $trip->departure_address;
            $data['arrival_address']    = $trip->arrival_address;
            $data['departure_latitude'] = $trip->departure_latitude;
            $data['departure_longitude']= $trip->departure_longitude;
            $data['arrival_latitude']   = $trip->arrival_latitude;
            $data['arrival_longitude']  = $trip->arrival_longitude;
            $data['preferences']        = $trip->preferences;
            $data['stops']              = $trip->stops;
        }

        if (isset($trip->distance_km)) {
            $data['distance_km'] = round($trip->distance_km, 1);
        }

        if (isset($trip->bookings_count)) {
            $data['bookings_count'] = $trip->bookings_count;
        }

        return $data;
    }
}
