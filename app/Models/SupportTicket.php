<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_number',
        'subject',
        'message',
        'category',
        'priority',
        'status',
        'admin_response',
        'responded_at',
        'resolved_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot method pour générer le numéro de ticket
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'ST-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Marquer le ticket comme résolu
     */
    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Scope pour les tickets ouverts
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
