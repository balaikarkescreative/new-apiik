<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListIzins extends ListRecords
{
    protected static string $resource = IzinResource::class;

    protected static ?string $title = 'Kelola Pengajuan Izin';

    protected static ?string $slug = 'izin';

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (! auth()->user()->hasRole(['admin'])) {
            abort(403, 'Akses ditolak. Hanya admin yang dapat mengelola pengajuan izin.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('unduh-draft-pks')
                ->label('Unduh Draft PKS')
                ->url(Storage::url('izin-documents/Draft Perjanjian Kerjasama (PKS).doc'))
                ->icon('heroicon-o-arrow-down-tray')
                ->link()
                ->color('primary'),

            Actions\CreateAction::make('Buat Pengajuan Izin')
                ->icon('heroicon-o-plus')
                ->label('Buat Pengajuan Izin'),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        if (! auth()->user()->hasRole('admin')) {
            redirect()->route('filament.admin.resources.izin.daftar-pengajuan');
        }

    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()->hasRole("admin");
    // }
}
