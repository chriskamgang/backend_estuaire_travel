<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\RideshareBooking;
use App\Models\RideshareTrip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PassengerRideshareController extends Controller
{
    /**
     * Réserver un trajet covoiturage
     * POST /rideshare/book
     */
    public function book(Request $request)
    {
        $passenger = $request->user();

        $validator = Validator::make($request->all(), [
            'trip_id'          => 'required|exists:rideshare_trips,id',
            'seats_requested'  => 'required|integer|min:1|max:10',
            'pickup_location'  => 'nullable|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
            'special_requests' => 'nullable|string|max:500',
        ], [
            'trip_id.required'         => 'Le trajet est requis',
            'trip_id.exists'           => 'Trajet introuvable',
            'seats_requested.required' => 'Le nombre de places est requis',
            'seats_requested.min'      => 'Au moins 1 place requise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $trip = RideshareTrip::findOrFail($request->trip_id);

        // Ne pas réserver son propre trajet
        if ($trip->driver_id === $passenger->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas réserver votre propre trajet',
            ], 422);
        }

        // Vérifier le statut du trajet
        if (!in_array($trip->status, ['scheduled', 'active'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce trajet n\'est plus disponible',
            ], 422);
        }

        // Vérifier les places disponibles
        if ($trip->available_seats < $request->seats_requested) {
            return response()->json([
                'success' => false,
                'message' => "Il ne reste que {$trip->available_seats} place(s) disponible(s)",
            ], 422);
        }

        // Vérifier que l'utilisateur n'a pas déjà réservé ce trajet
        $existing = RideshareBooking::where('trip_id', $trip->id)
            ->where('user_id', $passenger->id)
            ->whereNotIn('status', ['cancelled'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une réservation pour ce trajet',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $totalPrice = $trip->price_per_seat * $request->seats_requested;

            $booking = RideshareBooking::create([
                'trip_id'          => $trip->id,
                'user_id'          => $passenger->id,
                'seats_requested'  => $request->seats_requested,
                'total_price'      => $totalPrice,
                'status'           => 'pending', // En attente de confirmation du chauffeur
                'pickup_location'  => $request->pickup_location,
                'dropoff_location' => $request->dropoff_location,
                'special_requests' => $request->special_requests,
            ]);

            // Notifier le chauffeur d'une nouvelle demande
            Notification::create([
                'user_id' => $trip->driver_id,
                'title'   => 'Nouvelle demande de réservation',
                'message' => "{$passenger->name} souhaite réserver {$request->seats_requested} place(s) sur votre trajet {$trip->from_city} → {$trip->to_city}",
                'type'    => 'rideshare_booking_request',
                'data'    => json_encode([
                    'booking_id' => $booking->id,
                    'trip_id'    => $trip->id,
                    'passenger'  => $passenger->name,
                ]),
                'read'    => false,
            ]);

            DB::commit();

            $booking->load(['trip.driver:id,name,avatar', 'trip.vehicle:id,brand,model,color']);

            return response()->json([
                'success' => true,
                'message' => 'Demande de réservation envoyée au chauffeur',
                'data'    => $this->formatBooking($booking),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réservation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mes réservations covoiturage (passager)
     * GET /rideshare/my-bookings
     */
    public function myBookings(Request $request)
    {
        $passenger = $request->user();
        $status    = $request->input('status', 'all');
        $upcoming  = $request->boolean('upcoming', false);

        $query = RideshareBooking::with([
            'trip.driver:id,name,avatar,phone',
            'trip.vehicle:id,brand,model,color',
        ])->where('user_id', $passenger->id);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($upcoming) {
            $today = Carbon::today()->format('Y-m-d');
            $now   = Carbon::now()->format('H:i:s');
            $query->whereHas('trip', function ($q) use ($today, $now) {
                $q->where('date', '>', $today)
                  ->orWhere(function ($q2) use ($today, $now) {
                      $q2->where('date', $today)->where('departure_time', '>=', $now);
                  });
            });
        }

        $bookings = $query->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $bookings->through(fn($b) => $this->formatBooking($b)),
        ]);
    }

    /**
     * Détails d'une réservation
     * GET /rideshare/my-bookings/{id}
     */
    public function showBooking(Request $request, $id)
    {
        $booking = RideshareBooking::with([
            'trip.driver:id,name,avatar,phone',
            'trip.vehicle:id,brand,model,color,seats',
        ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->formatBooking($booking, true),
        ]);
    }

    /**
     * Annuler une réservation
     * POST /rideshare/my-bookings/{id}/cancel
     */
    public function cancelBooking(Request $request, $id)
    {
        $passenger = $request->user();

        $booking = RideshareBooking::where('user_id', $passenger->id)
            ->findOrFail($id);

        if (in_array($booking->status, ['cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation est déjà annulée',
            ], 422);
        }

        if ($booking->status === 'confirmed') {
            // Si confirmée, remettre les places
            RideshareTrip::where('id', $booking->trip_id)
                ->increment('available_seats', $booking->seats_requested);
        }

        try {
            $booking->update(['status' => 'cancelled']);

            // Notifier le chauffeur de l'annulation
            $trip = $booking->trip;
            if ($trip) {
                Notification::create([
                    'user_id' => $trip->driver_id,
                    'title'   => 'Réservation annulée',
                    'message' => "{$passenger->name} a annulé sa réservation sur votre trajet {$trip->from_city} → {$trip->to_city}",
                    'type'    => 'rideshare_booking_cancelled',
                    'data'    => json_encode([
                        'booking_id' => $booking->id,
                        'trip_id'    => $trip->id,
                    ]),
                    'read'    => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Réservations à venir (prochains trajets)
     * GET /rideshare/upcoming
     */
    public function upcoming(Request $request)
    {
        $passenger = $request->user();
        $today     = Carbon::today()->format('Y-m-d');
        $now       = Carbon::now()->format('H:i:s');

        $bookings = RideshareBooking::with([
            'trip.driver:id,name,avatar,phone',
            'trip.vehicle:id,brand,model,color',
        ])
            ->where('user_id', $passenger->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('trip', function ($q) use ($today, $now) {
                $q->where('date', '>', $today)
                  ->orWhere(function ($q2) use ($today, $now) {
                      $q2->where('date', $today)->where('departure_time', '>=', $now);
                  });
            })
            ->orderByRaw('(SELECT date FROM rideshare_trips WHERE rideshare_trips.id = rideshare_bookings.trip_id) ASC')
            ->limit(10)
            ->get()
            ->map(fn($b) => $this->formatBooking($b));

        return response()->json([
            'success' => true,
            'count'   => $bookings->count(),
            'data'    => $bookings,
        ]);
    }

    /**
     * Formater une réservation pour la réponse API
     */
    private function formatBooking(RideshareBooking $booking, bool $detailed = false): array
    {
        $trip = $booking->trip;

        $data = [
            'id'              => $booking->id,
            'status'          => $booking->status,
            'seats_requested' => $booking->seats_requested,
            'total_price'     => (float) $booking->total_price,
            'pickup_location' => $booking->pickup_location,
            'created_at'      => $booking->created_at?->format('Y-m-d H:i'),
            'trip'            => $trip ? [
                'id'             => $trip->id,
                'from_city'      => $trip->from_city,
                'to_city'        => $trip->to_city,
                'date'           => $trip->date,
                'departure_time' => substr($trip->departure_time ?? '', 0, 5),
                'price_per_seat' => (float) $trip->price_per_seat,
                'status'         => $trip->status,
                'driver'         => $trip->driver ? [
                    'id'     => $trip->driver->id,
                    'name'   => $trip->driver->name,
                    'avatar' => $trip->driver->avatar,
                    'phone'  => $trip->driver->phone,
                ] : null,
                'vehicle' => $trip->vehicle ? [
                    'label' => trim(($trip->vehicle->brand ?? '') . ' ' . ($trip->vehicle->model ?? '')),
                    'color' => $trip->vehicle->color ?? null,
                ] : null,
            ] : null,
        ];

        if ($detailed) {
            $data['dropoff_location'] = $booking->dropoff_location;
            $data['special_requests'] = $booking->special_requests;
        }

        return $data;
    }
}
