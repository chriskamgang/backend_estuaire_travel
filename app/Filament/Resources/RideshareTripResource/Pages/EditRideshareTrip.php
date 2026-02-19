<?php

namespace App\Filament\Resources\RideshareTripResource\Pages;

use App\Filament\Resources\RideshareTripResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRideshareTrip extends EditRecord
{
    protected static string $resource = RideshareTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
