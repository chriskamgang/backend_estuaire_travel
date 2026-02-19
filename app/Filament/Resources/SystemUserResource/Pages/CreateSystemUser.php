<?php

namespace App\Filament\Resources\SystemUserResource\Pages;

use App\Filament\Resources\SystemUserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemUser extends CreateRecord
{
    protected static string $resource = SystemUserResource::class;
}
