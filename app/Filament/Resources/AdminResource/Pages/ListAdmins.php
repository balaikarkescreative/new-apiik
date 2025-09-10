<?php

// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Resources/AdminResource/Pages/ListAdmins.php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdmins extends ListRecords
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Admin Baru'),
        ];
    }

    public function getTitle(): string
    {
        return 'Kelola Administrator';
    }
}
