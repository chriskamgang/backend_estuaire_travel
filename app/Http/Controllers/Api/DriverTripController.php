<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\RideshareBooking;
use App\Models\RideshareTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DriverTripController extends Controller
{
    /**
     * Proposer un nouveau trajet (Chauffeur)
     */
    public function createTrip(Request $request)
    {
        $driver = $request->user();

        // Vérifier que l'utilisateur est un chauffeur vérifié
        if (!$driver->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être un chauffeur vérifié pour proposer des trajets',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'departure_location' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_datetime' => 'required|string',
            'available_seats' => 'required|integer|min:1|max:50',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ], [
            'vehicle_id.required' => 'Le véhicule est requis',
            'vehicle_id.exists' => 'Véhicule non trouvé',
            'departure_location.required' => 'Le lieu de départ est requis',
            'destination.required' => 'La destination est requise',
            'departure_datetime.required' => 'La date et l\'heure de départ sont requises',
            'available_seats.required' => 'Le nombre de places est requis',
            'available_seats.min' => 'Au moins 1 place doit être disponible',
            'price.required' => 'Le prix est requis',
            'price.min' => 'Le prix doit être supérieur ou égal à 0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier que le véhicule appartient au chauffeur
        $vehicle = Vehicle::where('id', $request->vehicle_id)
            ->where('user_id', $driver->id)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Ce véhicule ne vous appartient pas',
            ], 403);
        }

        // =====================================================
        // ABONNEMENT MENSUEL : 1 000 FCFA / mois
        // =====================================================
        $currentMonth = Carbon::now()->format('Y-m');
        $subscriptionFee = 1000;

        $alreadyPaid = DB::table('driver_subscriptions')
            ->where('driver_id', $driver->id)
            ->where('month', $currentMonth)
            ->exists();

        if (!$alreadyPaid) {
            // Vérifier le solde du wallet
            $wallet = Wallet::getOrCreate($driver->id);

            if ($wallet->balance < $subscriptionFee) {
                return response()->json([
                    'success'      => false,
                    'error_code'   => 'insufficient_balance',
                    'message'      => 'Vous devez payer un abonnement de 1 000 FCFA pour publier un trajet ce mois-ci. Votre solde actuel est de ' . number_format($wallet->balance, 0, ',', ' ') . ' FCFA.',
                    'required'     => $subscriptionFee,
                    'balance'      => (float) $wallet->balance,
                ], 402);
            }

            // Débiter l'abonnement
            try {
                $wallet->debit(
                    $subscriptionFee,
                    'debit',
                    'Abonnement chauffeur ' . Carbon::now()->locale('fr')->isoFormat('MMMM YYYY'),
                    ['month' => $currentMonth]
                );

                // Enregistrer l'abonnement payé
                DB::table('driver_subscriptions')->insert([
                    'driver_id'  => $driver->id,
                    'month'      => $currentMonth,
                    'amount'     => $subscriptionFee,
                    'currency'   => 'FCFA',
                    'paid_at'    => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'payment_failed',
                    'message'    => 'Échec du paiement de l\'abonnement : ' . $e->getMessage(),
                ], 422);
            }
        }
        // =====================================================

        try {
            // Parser le datetime (format: "YYYY-MM-DD HH:MM:00")
            $departureDateTime = \Carbon\Carbon::parse($request->departure_datetime);
            $date = $departureDateTime->format('Y-m-d');
            $departureTime = $departureDateTime->format('H:i:s');

            // Vérifier qu'un trajet identique n'existe pas déjà (même départ, même destination, même date/heure)
            $existingTrip = RideshareTrip::where('driver_id', $driver->id)
                ->where('from_city', $request->departure_location)
                ->where('to_city', $request->destination)
                ->where('date', $date)
                ->where('departure_time', $departureTime)
                ->whereNotIn('status', ['cancelled'])
                ->first();

            if ($existingTrip) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà un trajet similaire programmé à cette date et heure.',
                ], 422);
            }

            // Créer le trajet avec les vrais champs de la table
            $trip = RideshareTrip::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $request->vehicle_id,
                'from_city' => $request->departure_location,
                'to_city' => $request->destination,
                'date' => $date,
                'departure_time' => $departureTime,
                'arrival_time' => $departureTime, // estimé, même valeur par défaut
                'duration' => '0h00',
                'price_per_seat' => $request->price,
                'total_seats' => $vehicle->seats,
                'available_seats' => $request->available_seats,
                'departure_latitude' => 0,
                'departure_longitude' => 0,
                'arrival_latitude' => 0,
                'arrival_longitude' => 0,
                'additional_notes' => $request->description,
                'status' => 'scheduled',
            ]);

            // Charger les relations
            $trip->load(['vehicle', 'driver']);

            return response()->json([
                'success' => true,
                'message' => 'Trajet proposé avec succès',
                'data' => $trip,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du trajet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les trajets proposés par le chauffeur
     */
    public function myTrips(Request $request)
    {
        $driver = $request->user();
        $status = $request->input('status', 'all'); // all, pending, active, completed, cancelled

        $query = RideshareTrip::where('driver_id', $driver->id)
            ->with(['vehicle', 'bookings.user']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $trips = $query->orderBy('date', 'desc')
            ->orderBy('departure_time', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $trips,
        ]);
    }

    /**
     * Mettre à jour un trajet
     */
    public function updateTrip(Request $request, $id)
    {
        $driver = $request->user();

        $trip = RideshareTrip::where('id', $id)
            ->where('driver_id', $driver->id)
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        // On ne peut modifier que les trajets en attente
        if ($trip->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que les trajets en attente',
            ], 422);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'departure_location' => 'sometimes|string|max:255',
            'destination' => 'sometimes|string|max:255',
            'departure_datetime' => 'sometimes|string',
            'available_seats' => 'sometimes|integer|min:1|max:50',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updateData = [];
            if ($request->has('departure_location')) $updateData['from_city'] = $request->departure_location;
            if ($request->has('destination')) $updateData['to_city'] = $request->destination;
            if ($request->has('vehicle_id')) $updateData['vehicle_id'] = $request->vehicle_id;
            if ($request->has('available_seats')) $updateData['available_seats'] = $request->available_seats;
            if ($request->has('price')) $updateData['price_per_seat'] = $request->price;
            if ($request->has('description')) $updateData['additional_notes'] = $request->description;
            if ($request->has('departure_datetime')) {
                $dt = \Carbon\Carbon::parse($request->departure_datetime);
                $updateData['date'] = $dt->format('Y-m-d');
                $updateData['departure_time'] = $dt->format('H:i:s');
            }

            $trip->update($updateData);

            $trip->load(['vehicle', 'bookings']);

            return response()->json([
                'success' => true,
                'message' => 'Trajet mis à jour avec succès',
                'data' => $trip,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annuler un trajet
     */
    public function cancelTrip(Request $request, $id)
    {
        $driver = $request->user();

        $trip = RideshareTrip::where('id', $id)
            ->where('driver_id', $driver->id)
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        if ($trip->status === 'completed' || $trip->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Ce trajet ne peut plus être annulé',
            ], 422);
        }

        try {
            $trip->update(['status' => 'cancelled']);

            // TODO: Notifier les passagers qui ont réservé

            return response()->json([
                'success' => true,
                'message' => 'Trajet annulé avec succès',
                'data' => $trip,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Démarrer un trajet (passer en status "active")
     */
    public function startTrip(Request $request, $id)
    {
        $driver = $request->user();

        $trip = RideshareTrip::where('id', $id)
            ->where('driver_id', $driver->id)
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        if ($trip->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce trajet ne peut pas être démarré',
            ], 422);
        }

        try {
            $trip->update(['status' => 'active']);

            // TODO: Notifier les passagers que le trajet a démarré

            return response()->json([
                'success' => true,
                'message' => 'Trajet démarré avec succès',
                'data' => $trip,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Terminer un trajet (passer en status "completed")
     */
    public function completeTrip(Request $request, $id)
    {
        $driver = $request->user();

        $trip = RideshareTrip::where('id', $id)
            ->where('driver_id', $driver->id)
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        if ($trip->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les trajets actifs peuvent être terminés',
            ], 422);
        }

        try {
            $trip->update(['status' => 'completed']);

            // TODO: Notifier les passagers que le trajet est terminé
            // TODO: Demander aux passagers de noter le trajet

            return response()->json([
                'success' => true,
                'message' => 'Trajet terminé avec succès',
                'data' => $trip,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les demandes de réservation pour les trajets du chauffeur
     */
    public function getBookingRequests(Request $request)
    {
        $driver = $request->user();
        $status = $request->input('status', 'pending'); // pending, confirmed, cancelled

        $bookings = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->join('users', 'rideshare_bookings.user_id', '=', 'users.id')
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_bookings.status', $status)
            ->select(
                'rideshare_bookings.*',
                'users.name as passenger_name',
                'users.phone as passenger_phone',
                'users.avatar as passenger_avatar',
                'rideshare_trips.from_city',
                'rideshare_trips.to_city',
                'rideshare_trips.date',
                'rideshare_trips.departure_time',
                'rideshare_trips.price_per_seat'
            )
            ->orderBy('rideshare_bookings.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Accepter une demande de réservation
     */
    public function acceptBooking(Request $request, $bookingId)
    {
        $driver = $request->user();

        $booking = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->where('rideshare_bookings.id', $bookingId)
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_bookings.status', 'pending')
            ->select('rideshare_bookings.*', 'rideshare_trips.available_seats')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée',
            ], 404);
        }

        // Vérifier s'il reste des places
        if ($booking->available_seats < $booking->seats_requested) {
            return response()->json([
                'success' => false,
                'message' => 'Pas assez de places disponibles',
            ], 422);
        }

        // Vérifier le solde du wallet du passager avant d'accepter
        $totalPrice = $booking->total_price ?? 0;
        if ($totalPrice > 0) {
            $passengerWallet = Wallet::getOrCreate($booking->user_id);
            if ($passengerWallet->balance < $totalPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le passager n\'a pas un solde suffisant dans son portefeuille. Montant requis : ' . number_format($totalPrice, 0, ',', ' ') . ' FCFA.',
                ], 422);
            }
        }

        try {
            // Accepter la réservation
            DB::table('rideshare_bookings')
                ->where('id', $bookingId)
                ->update(['status' => 'confirmed', 'updated_at' => now()]);

            // Diminuer le nombre de places disponibles
            DB::table('rideshare_trips')
                ->where('id', $booking->trip_id)
                ->decrement('available_seats', $booking->seats_requested);

            // Charger le trajet Eloquent (utile pour le débit et les notifications)
            $rideshareTrip = RideshareTrip::with(['driver:id,name,phone'])->find($booking->trip_id);

            // Débiter le wallet du passager
            if ($totalPrice > 0) {
                try {
                    $passengerWallet->debit(
                        $totalPrice,
                        'debit',
                        'Covoiturage ' . ($rideshareTrip->from_city ?? '') . ' → ' . ($rideshareTrip->to_city ?? ''),
                        [
                            'bookable_type' => RideshareBooking::class,
                            'bookable_id'   => $bookingId,
                            'description'   => 'Réservation covoiturage #' . $bookingId,
                        ]
                    );
                } catch (\Exception $e) {
                    // Rollback : remettre la réservation en pending et restaurer les places
                    DB::table('rideshare_bookings')
                        ->where('id', $bookingId)
                        ->update(['status' => 'pending', 'updated_at' => now()]);
                    DB::table('rideshare_trips')
                        ->where('id', $booking->trip_id)
                        ->increment('available_seats', $booking->seats_requested);
                    Log::error('Wallet debit failed on rideshare booking acceptance', [
                        'booking_id' => $bookingId,
                        'user_id'    => $booking->user_id,
                        'error'      => $e->getMessage(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Échec du prélèvement du portefeuille passager : ' . $e->getMessage(),
                    ], 422);
                }
            }

            // Notifier le passager que sa demande est acceptée
            $trip = $rideshareTrip;
            if ($trip) {
                Notification::create([
                    'user_id' => $booking->user_id,
                    'title'   => '✅ Réservation confirmée !',
                    'message' => "Votre réservation pour le trajet {$trip->from_city} → {$trip->to_city} le {$trip->date} a été acceptée par le chauffeur.",
                    'type'    => 'rideshare_booking_confirmed',
                    'data'    => json_encode([
                        'booking_id' => $bookingId,
                        'trip_id'    => $trip->id,
                    ]),
                    'read'    => false,
                ]);

                // Envoyer une notification WhatsApp au passager
                $passenger = User::find($booking->user_id);
                if ($passenger && !empty($passenger->phone)) {
                    try {
                        $whatsapp = new WhatsAppService();
                        $date = \Carbon\Carbon::parse($trip->date)->locale('fr')->isoFormat('dddd D MMMM YYYY');
                        $whatsapp->sendRideshareBookingConfirmation($passenger->phone, [
                            'passenger_name' => $passenger->name,
                            'from'           => $trip->from_city,
                            'to'             => $trip->to_city,
                            'date'           => $date,
                            'time'           => substr($trip->departure_time ?? '', 0, 5),
                            'seats'          => $booking->seats_requested,
                            'price'          => number_format($booking->total_price, 0, ',', ' '),
                            'pickup'         => $booking->pickup_location ?? $trip->from_city,
                            'driver_name'    => $trip->driver?->name ?? 'Votre conducteur',
                            'driver_phone'   => $trip->driver?->phone ?? '–',
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('WhatsApp booking notification failed: ' . $e->getMessage());
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Demande acceptée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Scanner le QR code d'un passager pour valider son embarquement
     */
    public function checkInBooking(Request $request)
    {
        $driver = $request->user();

        $validator = Validator::make($request->all(), [
            'qr_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données QR manquantes',
            ], 422);
        }

        // Format attendu : "ESTUAIRE_BOOKING:{id}"
        $qrData = $request->input('qr_data');
        if (!str_starts_with($qrData, 'ESTUAIRE_BOOKING:')) {
            return response()->json([
                'success' => false,
                'message' => 'QR code invalide',
            ], 422);
        }

        $bookingId = (int) str_replace('ESTUAIRE_BOOKING:', '', $qrData);

        // Trouver la réservation confirmée appartenant à un trajet de ce chauffeur
        $booking = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->join('users', 'rideshare_bookings.user_id', '=', 'users.id')
            ->where('rideshare_bookings.id', $bookingId)
            ->where('rideshare_trips.driver_id', $driver->id)
            ->whereIn('rideshare_bookings.status', ['confirmed'])
            ->select(
                'rideshare_bookings.*',
                'users.name as passenger_name',
                'users.phone as passenger_phone',
                'rideshare_trips.from_city',
                'rideshare_trips.to_city',
                'rideshare_trips.date',
                'rideshare_trips.departure_time'
            )
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation introuvable, déjà embarquée ou ne vous appartient pas.',
            ], 404);
        }

        // Marquer comme embarqué
        DB::table('rideshare_bookings')
            ->where('id', $bookingId)
            ->update(['status' => 'boarded', 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Passager embarqué avec succès',
            'data' => [
                'booking_id'     => $bookingId,
                'passenger_name' => $booking->passenger_name,
                'passenger_phone'=> $booking->passenger_phone,
                'seats'          => $booking->seats_requested,
                'from'           => $booking->from_city,
                'to'             => $booking->to_city,
                'date'           => $booking->date,
            ],
        ]);
    }

    /**
     * Refuser une demande de réservation
     */
    public function rejectBooking(Request $request, $bookingId)
    {
        $driver = $request->user();

        $booking = DB::table('rideshare_bookings')
            ->join('rideshare_trips', 'rideshare_bookings.trip_id', '=', 'rideshare_trips.id')
            ->where('rideshare_bookings.id', $bookingId)
            ->where('rideshare_trips.driver_id', $driver->id)
            ->where('rideshare_bookings.status', 'pending')
            ->select('rideshare_bookings.*')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée',
            ], 404);
        }

        try {
            DB::table('rideshare_bookings')
                ->where('id', $bookingId)
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            // Notifier le passager que sa demande est refusée
            $trip = RideshareTrip::find($booking->trip_id);
            if ($trip) {
                Notification::create([
                    'user_id' => $booking->user_id,
                    'title'   => '❌ Réservation refusée',
                    'message' => "Votre demande de réservation pour le trajet {$trip->from_city} → {$trip->to_city} le {$trip->date} a été refusée par le chauffeur.",
                    'type'    => 'rideshare_booking_rejected',
                    'data'    => json_encode([
                        'booking_id' => $bookingId,
                        'trip_id'    => $trip->id,
                    ]),
                    'read'    => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Demande refusée',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }
}
