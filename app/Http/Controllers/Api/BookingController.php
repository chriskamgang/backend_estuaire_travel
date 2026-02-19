<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BusTrip;
use App\Models\Wallet;
use App\Services\ExpoPushNotificationService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Get user bookings
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

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
     * Create new booking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_trip_id' => 'required|exists:bus_trips,id',
            'travel_date' => 'required|date|after_or_equal:today',
            'seats' => 'nullable|array', // Optionnel pour les bus VIP
            'number_of_seats' => 'required|integer|min:1',
            'passenger_name' => 'required|string|max:255',
            'passenger_phone' => 'required|string|max:20',
            'passenger_email' => 'nullable|email|max:255',
            'payment_method' => 'required|in:wallet,Orange Money,MTN Mobile Money,Carte bancaire',
            'use_free_trip' => 'boolean',
        ]);

        $busTrip = BusTrip::findOrFail($validated['bus_trip_id']);

        // Check available seats
        $bookedSeats = $busTrip->bookings()
            ->where('travel_date', $validated['travel_date'])
            ->where('status', '!=', 'cancelled')
            ->sum('number_of_seats');

        $availableSeats = $busTrip->total_seats - $bookedSeats;

        if ($availableSeats < $validated['number_of_seats']) {
            return response()->json([
                'success' => false,
                'message' => 'Pas assez de sièges disponibles',
                'available_seats' => $availableSeats,
            ], 400);
        }

        $user = $request->user();
        $usedFreeTrip = false;
        $totalPrice = $busTrip->price * $validated['number_of_seats'];

        // Check if user wants to use free trip
        if ($validated['use_free_trip'] ?? false) {
            if ($user->free_trips_available > 0 && $validated['number_of_seats'] == 1) {
                $usedFreeTrip = true;
                $user->useFreeTrip();
                $totalPrice = 0;
            }
        }

        // Pour les bus Classique, générer des numéros de sièges automatiques
        // Pour les bus VIP, les sièges seront fournis par le frontend
        $seats = $validated['seats'] ?? array_map(fn($i) => $i, range(1, $validated['number_of_seats']));

        // Débiter le wallet si le voyage n'est pas gratuit
        if (!$usedFreeTrip && $totalPrice > 0) {
            $wallet = Wallet::getOrCreate($user->id);
            if ($wallet->balance < $totalPrice) {
                // Envoyer une notification WhatsApp de solde insuffisant
                try {
                    $whatsapp = new WhatsAppService();
                    $whatsapp->sendInsufficientBalance($user->phone, [
                        'passenger_name' => $user->name,
                        'from'           => $busTrip->fromCity->name,
                        'to'             => $busTrip->toCity->name,
                        'required'       => number_format($totalPrice, 0, ',', ' '),
                        'balance'        => number_format($wallet->balance, 0, ',', ' '),
                        'missing'        => number_format($totalPrice - $wallet->balance, 0, ',', ' '),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('WhatsApp insufficient balance notification failed: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant. Solde disponible : ' . number_format($wallet->balance, 0, ',', ' ') . ' FCFA. Montant requis : ' . number_format($totalPrice, 0, ',', ' ') . ' FCFA.',
                    'wallet_balance' => (float) $wallet->balance,
                    'required_amount' => $totalPrice,
                ], 422);
            }
        }

        // Pour wallet et free_trip : paiement immédiat → statut confirmed d'emblée
        $isImmediatePay = $usedFreeTrip || $validated['payment_method'] === 'wallet';

        // Create booking
        $booking = Booking::create([
            'booking_reference' => 'BK-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'bus_trip_id' => $validated['bus_trip_id'],
            'travel_date' => $validated['travel_date'],
            'seats' => $seats,
            'number_of_seats' => $validated['number_of_seats'],
            'total_price' => $totalPrice,
            'passenger_name' => $validated['passenger_name'],
            'passenger_phone' => $validated['passenger_phone'],
            'passenger_email' => $validated['passenger_email'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => $isImmediatePay ? 'completed' : 'pending',
            'status' => $isImmediatePay ? 'confirmed' : 'pending',
            'used_free_trip' => $usedFreeTrip,
        ]);

        $booking->load(['busTrip.company', 'busTrip.fromCity', 'busTrip.toCity']);

        // Débiter le wallet après création réussie de la réservation
        if (!$usedFreeTrip && $totalPrice > 0) {
            try {
                $wallet->debit(
                    $totalPrice,
                    'debit',
                    'Réservation bus ' . $busTrip->fromCity->name . ' → ' . $busTrip->toCity->name,
                    [
                        'bookable_type' => Booking::class,
                        'bookable_id'   => $booking->id,
                        'description'   => 'Réservation #' . $booking->booking_reference,
                    ]
                );
            } catch (\Exception $e) {
                // Si le débit échoue (race condition), annuler la réservation
                $booking->update(['status' => 'cancelled', 'cancellation_reason' => 'Échec du paiement wallet']);
                Log::error('Wallet debit failed after booking creation', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        // NOTE: Les points de fidélité seront ajoutés uniquement après confirmation du paiement
        // Pas de points pour les réservations en attente (pending)

        // Envoyer une notification push de confirmation
        $pushService = new ExpoPushNotificationService();
        $pushService->createAndSendNotification(
            $user->id,
            'booking_confirmed',
            '✅ Réservation confirmée',
            "Votre réservation pour {$busTrip->fromCity->name} → {$busTrip->toCity->name} a été créée avec succès. Référence: {$booking->booking_reference}",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
            ]
        );

        // Envoyer une notification WhatsApp au passager
        try {
            $whatsapp = new WhatsAppService();
            $date = Carbon::parse($booking->travel_date)->locale('fr')->isoFormat('dddd D MMMM YYYY');
            $seats = is_array($booking->seats)
                ? implode(', ', $booking->seats)
                : $booking->number_of_seats . ' siège(s)';
            $whatsapp->sendBusBookingConfirmation($booking->passenger_phone, [
                'passenger_name' => $booking->passenger_name,
                'from'           => $busTrip->fromCity->name,
                'to'             => $busTrip->toCity->name,
                'date'           => $date,
                'time'           => substr($busTrip->departure_time ?? '', 0, 5),
                'seats'          => $seats,
                'price'          => number_format($booking->total_price, 0, ',', ' '),
                'company'        => $busTrip->company->name ?? 'Estuaire Travel',
                'reference'      => $booking->booking_reference,
            ]);
        } catch (\Exception $e) {
            Log::warning('WhatsApp bus booking notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Réservation créée avec succès',
            'data' => $booking,
            'loyalty' => [
                'points' => $user->loyalty_points,
                'total_trips' => $user->total_trips,
                'free_trips_available' => $user->free_trips_available,
            ],
        ], 201);
    }

    /**
     * Get booking details
     */
    public function show(string $id)
    {
        $booking = Booking::with(['busTrip.company', 'busTrip.fromCity', 'busTrip.toCity'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    /**
     * Cancel booking
     */
    public function cancel(string $id, Request $request)
    {
        $booking = Booking::where('user_id', auth()->id())
            ->findOrFail($id);

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation est déjà annulée',
            ], 400);
        }

        if ($booking->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler une réservation terminée',
            ], 400);
        }

        // Check if cancellation is allowed (e.g., 24 hours before departure)
        $travelDateTime = Carbon::parse($booking->travel_date . ' ' . $booking->busTrip->departure_time);
        $hoursUntilTravel = now()->diffInHours($travelDateTime, false);

        if ($hoursUntilTravel < 24) {
            return response()->json([
                'success' => false,
                'message' => 'Annulation impossible moins de 24h avant le départ',
            ], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancelled_at' => now(),
        ]);

        // Restore free trip if it was used
        if ($booking->used_free_trip) {
            $booking->user->increment('free_trips_available');
        }

        // Envoyer une notification push d'annulation
        $pushService = new ExpoPushNotificationService();
        $pushService->createAndSendNotification(
            $booking->user_id,
            'booking_cancelled',
            '❌ Réservation annulée',
            "Votre réservation {$booking->booking_reference} pour {$booking->busTrip->fromCity->name} → {$booking->busTrip->toCity->name} a été annulée.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'cancellation_reason' => $validated['cancellation_reason'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Réservation annulée avec succès',
            'data' => $booking->fresh(),
        ]);
    }
}
