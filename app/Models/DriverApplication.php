<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverApplication extends Model
{
    protected $fillable = [
        'user_id',
        'id_card_front',
        'id_card_back',
        'driver_license_front',
        'driver_license_back',
        'vehicle_photo',
        'license_number',
        'license_expiry_date',
        'additional_info',
        'status',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a fait la demande
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'admin qui a révisé la demande
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Approuver la demande
     */
    public function approve(int $reviewerId): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
            'rejection_reason' => null,
        ]);

        // Ajouter le rôle 'driver' à l'utilisateur
        $this->user->assignRole('driver');

        // Marquer l'utilisateur comme chauffeur
        $this->user->update([
            'is_driver' => true,
        ]);
    }

    /**
     * Rejeter la demande
     */
    public function reject(int $reviewerId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Scope pour les demandes en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les demandes approuvées
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope pour les demandes rejetées
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Vérifier si tous les documents sont uploadés
     */
    public function hasAllDocuments(): bool
    {
        return !empty($this->id_card_front) &&
               !empty($this->id_card_back) &&
               !empty($this->driver_license_front) &&
               !empty($this->driver_license_back) &&
               !empty($this->vehicle_photo);
    }

    /**
     * Obtenir l'URL complète d'un document
     */
    public function getDocumentUrl(string $document): ?string
    {
        if (empty($this->$document)) {
            return null;
        }

        // Si c'est déjà une URL complète
        if (str_starts_with($this->$document, 'http')) {
            return $this->$document;
        }

        // Sinon, construire l'URL depuis storage
        return asset('storage/' . $this->$document);
    }
}
