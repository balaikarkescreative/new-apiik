<?php

namespace App\Filament\Resources\VaksinResource\Pages;

use App\Filament\Resources\VaksinResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVaksin extends CreateRecord
{
    protected static string $resource = VaksinResource::class;

    protected static bool $canCreateAnother = false;



}
