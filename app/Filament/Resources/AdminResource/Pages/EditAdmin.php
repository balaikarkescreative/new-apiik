<?php

// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Resources/AdminResource/Pages/EditAdmin.php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasRole('admin') && $this->record->id !== auth()->id()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['date_updated'] = now();

        if (! empty($data['simply_user_id'])) {
            if (empty($data['email'])) {
                $data['email'] = null;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data admin berhasil diperbarui';
    }

    protected function beforeDelete(): void
    {
        if ($this->record->id === auth()->id()) {
            Notification::make()
                ->title('Tidak dapat menghapus akun sendiri')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
