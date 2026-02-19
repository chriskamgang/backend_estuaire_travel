<?php

namespace App\Filament\Resources\BusTripResource\Pages;

use App\Filament\Resources\BusTripResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusTrips extends ListRecords
{
    protected static string $resource = BusTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
