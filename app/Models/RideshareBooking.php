<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RideshareBooking extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'seats_requested',
        'total_price',
        'escrow_amount',
        'escrow_released',
        'escrow_released_at',
        'status',
        'pickup_location',
        'dropoff_location',
        'special_requests',
    ];

    protected $casts = [
        'total_price'        => 'decimal:2',
        'escrow_amount'      => 'decimal:2',
        'escrow_released'    => 'boolean',
        'escrow_released_at' => 'datetime',
        'seats_requested'    => 'integer',
    ];

    // Relations
    public function trip()
    {
        return $this->belongsTo(RideshareTrip::class, 'trip_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
