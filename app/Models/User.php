<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'avatar',
        'is_driver',
        'is_verified',
        'phone_verified',
        'email_verified',
        'language',
        'theme',
        'preferences',
        'loyalty_points',
        'total_trips',
        'free_trips_available',
        'last_latitude',
        'last_longitude',
        'last_location_update',
        'is_online',
        // Champs chauffeur
        'driver_license_number',
        'driver_license_expiry',
        'driver_status',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'driver_license_expiry' => 'date',
            'password' => 'hashed',
            'is_driver' => 'boolean',
            'is_verified' => 'boolean',
            'phone_verified' => 'boolean',
            'email_verified' => 'boolean',
            'preferences' => 'array',
            'loyalty_points' => 'integer',
            'total_trips' => 'integer',
            'free_trips_available' => 'integer',
        ];
    }

    /**
     * Relations
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function rideshareTrips()
    {
        return $this->hasMany(RideshareTrip::class, 'driver_id');
    }

    /**
     * Ajouter des points de fidélité
     * Système: 8 trajets = 1 voyage gratuit
     */
    public function addLoyaltyPoints(int $points = 1): void
    {
        $this->increment('loyalty_points', $points);
        $this->increment('total_trips', $points);

        // Convertir en voyage gratuit tous les 8 points
        $pointsForFreeTrip = 8;
        if ($this->loyalty_points >= $pointsForFreeTrip) {
            $freeTrips = floor($this->loyalty_points / $pointsForFreeTrip);
            $this->increment('free_trips_available', $freeTrips);
            $this->decrement('loyalty_points', $freeTrips * $pointsForFreeTrip);
        }
    }

    /**
     * Get user loyalty status
     */
    public function getLoyaltyStatusAttribute(): string
    {
        return Setting::getUserStatus($this->loyalty_points);
    }

    /**
     * Utiliser un voyage gratuit
     */
    public function useFreeTrip(): bool
    {
        if ($this->free_trips_available > 0) {
            $this->decrement('free_trips_available');
            return true;
        }
        return false;
    }
}
