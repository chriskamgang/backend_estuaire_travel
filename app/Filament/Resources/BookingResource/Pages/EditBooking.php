<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Hook appelé après la sauvegarde
     * Ajoute les points de fidélité si le statut passe à 'confirmed'
     */
    protected function afterSave(): void
    {
        $booking = $this->record;

        // Si le statut est maintenant 'confirmed' et que le paiement n'a pas encore été confirmé
        if ($booking->status === 'confirmed' && $booking->wasChanged('status')) {
            // Vérifier si c'était 'pending' avant
            if ($booking->getOriginal('status') === 'pending') {
                $booking->confirmPayment();
            }
        }
    }
}
