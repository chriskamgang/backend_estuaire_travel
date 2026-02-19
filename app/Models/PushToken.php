<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_id',
        'active',
        'last_used_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer le token comme utilisé
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Désactiver le token
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Réactiver le token
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Scope pour les tokens actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Obtenir tous les tokens actifs d'un utilisateur
     */
    public static function getActiveTokensForUser(int $userId): array
    {
        return self::where('user_id', $userId)
            ->active()
            ->pluck('token')
            ->toArray();
    }
}
