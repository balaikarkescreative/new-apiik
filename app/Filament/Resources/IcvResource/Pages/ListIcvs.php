<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIcvs extends ListRecords
{
    protected static string $resource = IcvResource::class;

    protected static ?string $title = "Kelola Pengajuan ICV";

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['admin'])) {
            // abort(403, 'Akses ditolak. Hanya admin yang dapat mengelola pengajuan ICV.');
            redirect()->to('/');

        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Buat Pengajuan ICV')
            ->icon('heroicon-o-plus')
        ];
    }
}
