<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'brand',
        'model',
        'year',
        'color',
        'license_plate',
        'seats',
        'vehicle_type',
        'photo',
        'has_ac',
        'is_active',
    ];

    protected $casts = [
        'has_ac' => 'boolean',
        'is_active' => 'boolean',
        'seats' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur (chauffeur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
