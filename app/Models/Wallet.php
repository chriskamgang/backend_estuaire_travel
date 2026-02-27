<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'is_active',
        'transfer_used',
    ];

    protected $casts = [
        'balance'       => 'decimal:2',
        'is_active'     => 'boolean',
        'transfer_used' => 'boolean',
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Obtenir ou créer le wallet d'un utilisateur
     */
    public static function getOrCreate(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0.00, 'currency' => 'FCFA']
        );
    }

    /**
     * Créditer le wallet (recharge ou remboursement)
     * Utilise une transaction DB avec verrou pour éviter les race conditions
     */
    public function credit(
        float  $amount,
        string $type,
        string $label,
        array  $meta = []
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $type, $label, $meta) {
            // Verrou en lecture pour éviter les conflits
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $this->balance = $balanceAfter;

            return WalletTransaction::create(array_merge([
                'wallet_id'      => $wallet->id,
                'user_id'        => $wallet->user_id,
                'type'           => $type,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'label'          => $label,
                'payment_status' => 'success',
            ], $meta));
        });
    }

    /**
     * Débiter le wallet (réservation, transfert)
     * Lève une exception si solde insuffisant
     */
    public function debit(
        float  $amount,
        string $type,
        string $label,
        array  $meta = []
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $type, $label, $meta) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new \Exception('Solde insuffisant. Solde disponible : ' . number_format($wallet->balance, 0, ',', ' ') . ' FCFA');
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter  = $balanceBefore - $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $this->balance = $balanceAfter;

            return WalletTransaction::create(array_merge([
                'wallet_id'      => $wallet->id,
                'user_id'        => $wallet->user_id,
                'type'           => $type,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'label'          => $label,
                'payment_status' => 'success',
            ], $meta));
        });
    }

    /**
     * Mettre un montant en séquestre (escrow)
     * Prélève le passager mais l'argent n'est PAS encore versé au chauffeur.
     * Il sera libéré lors du scan QR d'embarquement.
     */
    public function escrow(
        float  $amount,
        string $label,
        array  $meta = []
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $label, $meta) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new \Exception('Solde insuffisant. Solde disponible : ' . number_format($wallet->balance, 0, ',', ' ') . ' FCFA');
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter  = $balanceBefore - $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $this->balance = $balanceAfter;

            return WalletTransaction::create(array_merge([
                'wallet_id'      => $wallet->id,
                'user_id'        => $wallet->user_id,
                'type'           => 'escrow',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'label'          => $label,
                'payment_status' => 'success',
            ], $meta));
        });
    }

    /**
     * Libérer l'escrow vers le wallet du chauffeur
     * Appelé lors du scan QR d'embarquement du passager.
     * Crédite le chauffeur du montant mis en séquestre.
     */
    public function releaseEscrow(
        float  $amount,
        string $label,
        array  $meta = []
    ): WalletTransaction {
        return $this->credit($amount, 'escrow_release', $label, $meta);
    }
}
