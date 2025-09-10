<?php

namespace App\Filament\Resources\KotakabResource\Pages;

use App\Filament\Resources\KotakabResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKotakab extends CreateRecord
{
    protected static string $resource = KotakabResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Tambah Kota/Kabupaten';
}
