<?php

namespace App\Filament\Resources\LinkSosialisasiResource\Pages;

use App\Filament\Resources\LinkSosialisasiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLinkSosialisasi extends CreateRecord
{
    protected static string $resource = LinkSosialisasiResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $user = auth()->user();
        if (! $user->hasRole(['admin', 'verifikator1', 'verifikator2']) && $user->linkSosialisasi) {
            \Filament\Notifications\Notification::make()
                ->title('Anda sudah memiliki link sosialisasi')
                ->body('Anda hanya dapat membuat satu link sosialisasi. Silakan edit link yang sudah ada jika ingin mengubah data.')
                ->danger()
                ->send();
            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()->hasRole('user')) {
            // Jika user adalah 'user', set user_id ke ID user yang sedang login
            $data['user_id'] = auth()->id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
