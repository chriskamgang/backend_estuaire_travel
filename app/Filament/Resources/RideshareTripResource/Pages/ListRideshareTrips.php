<?php

namespace App\Filament\Resources\RideshareTripResource\Pages;

use App\Filament\Resources\RideshareTripResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRideshareTrips extends ListRecords
{
    protected static string $resource = RideshareTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
