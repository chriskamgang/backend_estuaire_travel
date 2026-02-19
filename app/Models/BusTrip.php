<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusTrip extends Model
{
    protected $fillable = [
        'company_id',
        'from_city_id',
        'to_city_id',
        'departure_time',
        'arrival_time',
        'duration',
        'price',
        'total_seats',
        'bus_type',
        'amenities',
        'stops',
        'recurring',
        'recurring_days',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'recurring' => 'boolean',
        'amenities' => 'array',
        'stops' => 'array',
        'recurring_days' => 'array',
    ];

    /**
     * Relations
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fromCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'from_city_id');
    }

    public function toCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'to_city_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
