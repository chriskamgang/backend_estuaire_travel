<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RideshareTrip extends Model
{
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'from_city',
        'to_city',
        'departure_latitude',
        'departure_longitude',
        'departure_address',
        'arrival_latitude',
        'arrival_longitude',
        'arrival_address',
        'departure_point',
        'arrival_point',
        'date',
        'departure_time',
        'arrival_time',
        'duration',
        'price_per_seat',
        'total_seats',
        'available_seats',
        'stops',
        'preferences',
        'additional_notes',
        'instant',
        'recurring',
        'recurring_days',
        'status',
        'guarantor_name',
        'guarantor_phone',
        'guarantor_notified',
    ];

    protected $casts = [
        'stops' => 'array',
        'preferences' => 'array',
        'recurring_days' => 'array',
        'instant' => 'boolean',
        'recurring' => 'boolean',
        'guarantor_notified' => 'boolean',
        'price_per_seat' => 'decimal:2',
        'departure_latitude' => 'decimal:8',
        'departure_longitude' => 'decimal:8',
        'arrival_latitude' => 'decimal:8',
        'arrival_longitude' => 'decimal:8',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(RideshareBooking::class, 'trip_id');
    }
}
