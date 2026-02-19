<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'booking_reference',
        'user_id',
        'bus_trip_id',
        'travel_date',
        'seats',
        'number_of_seats',
        'total_price',
        'passenger_name',
        'passenger_phone',
        'passenger_email',
        'payment_method',
        'payment_status',
        'payment_reference',
        'status',
        'used_free_trip',
        'cancellation_reason',
        'cancelled_at',
        'points_awarded_at',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'seats' => 'array',
        'total_price' => 'decimal:2',
        'used_free_trip' => 'boolean',
        'cancelled_at' => 'datetime',
        'points_awarded_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function busTrip(): BelongsTo
    {
        return $this->belongsTo(BusTrip::class);
    }

    /**
     * Confirmer le paiement et ajouter les points de fidélité
     */
    public function confirmPayment(): void
    {
        // Vérifier que la réservation est en attente
        if ($this->status !== 'pending') {
            return;
        }

        // Changer le statut à confirmed
        $this->update([
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);

        // Ajouter les points de fidélité (1 point par réservation)
        if (!$this->used_free_trip) {
            $this->user->addLoyaltyPoints(1);
        }
    }
}
