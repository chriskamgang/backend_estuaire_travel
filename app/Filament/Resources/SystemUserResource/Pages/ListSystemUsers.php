<?php

namespace App\Filament\Resources\SystemUserResource\Pages;

use App\Filament\Resources\SystemUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemUsers extends ListRecords
{
    protected static string $resource = SystemUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
