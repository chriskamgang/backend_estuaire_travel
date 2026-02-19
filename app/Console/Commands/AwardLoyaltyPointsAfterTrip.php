<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\ExpoPushNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AwardLoyaltyPointsAfterTrip extends Command
{
    protected $signature = 'bookings:award-loyalty-points';
    protected $description = 'Accorde 1 point de fidélité aux utilisateurs 2h après le départ de leur bus';

    public function handle(): void
    {
        $now = Carbon::now()->setTimezone(config('app.timezone'));

        // Cherche les réservations confirmées dont le bus est parti il y a >= 2h
        // et dont les points n'ont pas encore été accordés (status = confirmed, pas completed)
        $bookings = Booking::with(['user', 'busTrip.fromCity', 'busTrip.toCity'])
            ->where('status', 'confirmed')
            ->whereNull('points_awarded_at')
            ->get()
            ->filter(function ($booking) use ($now) {
                if (!$booking->busTrip) return false;

                // Construire datetime de départ
                $departureTime = $booking->busTrip->departure_time; // ex: "22:00:00"
                $travelDate    = $booking->travel_date;             // ex: "2026-02-18"

                $departure = Carbon::parse("{$travelDate} {$departureTime}", config('app.timezone'));

                // Le bus doit être parti depuis au moins 2h
                return $now->diffInHours($departure, false) <= -2;
            });

        $count = 0;

        foreach ($bookings as $booking) {
            try {
                // Marquer comme completed
                $booking->update([
                    'status'            => 'completed',
                    'points_awarded_at' => now(),
                ]);

                // Accorder le point si ce n'est pas un voyage gratuit
                if (!$booking->used_free_trip) {
                    $booking->user->addLoyaltyPoints(1);

                    // Notification push
                    try {
                        $pushService = new ExpoPushNotificationService();
                        $points  = $booking->user->loyalty_points;
                        $needed  = 8 - ($points % 8 === 0 && $points > 0 ? 8 : $points % 8);
                        $from    = $booking->busTrip->fromCity->name ?? '';
                        $to      = $booking->busTrip->toCity->name ?? '';

                        $pushService->createAndSendNotification(
                            $booking->user_id,
                            'loyalty_point_earned',
                            '⭐ +1 EstuairePoint gagné !',
                            "Merci pour votre voyage {$from} → {$to}. Vous avez maintenant {$booking->user->loyalty_points} point(s). Encore {$needed} pour un voyage gratuit !",
                            ['booking_id' => $booking->id]
                        );
                    } catch (\Exception $e) {
                        Log::warning('Push notification for loyalty point failed: ' . $e->getMessage());
                    }
                }

                $count++;
                Log::info("Loyalty point awarded", [
                    'booking_id' => $booking->id,
                    'user_id'    => $booking->user_id,
                    'used_free_trip' => $booking->used_free_trip,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to award loyalty point for booking {$booking->id}: " . $e->getMessage());
            }
        }

        $this->info("{$count} point(s) de fidélité accordé(s).");
    }
}
