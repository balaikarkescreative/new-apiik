<?php

namespace App\Filament\Resources\PaktaIntegritasResource\Pages;

use App\Filament\Resources\PaktaIntegritasResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaktaIntegritas extends CreateRecord
{
    protected static string $resource = PaktaIntegritasResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $user = auth()->user();
        if (!$user->hasRole(['admin', 'verifikator1', 'verifikator2']) && $user->paktaIntegritas) {
            \Filament\Notifications\Notification::make()
                ->title('Anda sudah memiliki pakta integritas')
                ->body('Anda hanya dapat membuat satu pakta integritas. Silakan edit pakta integritas yang sudah ada jika ingin mengubah data.')
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

    protected function afterCreate(): void
    {
        $this->renameUploadedFile();
    }

    private function renameUploadedFile(): void
    {
        $record = $this->record;
        
        if ($record->file) {
            $user = $record->user;
            $userName = $user ? $user->name : 'Unknown';
            
            // Clean user name for filename
            $cleanUserName = preg_replace('/[^a-zA-Z0-9\s]/', '', $userName);
            $cleanUserName = preg_replace('/\s+/', ' ', trim($cleanUserName));
            
            // Get current date in ddmmyyyy format
            $date = now()->format('dmY');
            
            // Get file extension
            $currentFilePath = $record->file;
            $extension = pathinfo($currentFilePath, PATHINFO_EXTENSION);
            
            // Create new filename
            $newFileName = "Pakta integritas {$cleanUserName} ({$date}).{$extension}";
            $newFilePath = 'pakta-integritas/' . $newFileName;
            
            // Check if filename needs to be changed
            if ($currentFilePath !== $newFilePath) {
                $oldFullPath = storage_path('app/public/' . $currentFilePath);
                $newFullPath = storage_path('app/public/' . $newFilePath);
                
                if (file_exists($oldFullPath)) {
                    // Ensure directory exists
                    $directory = dirname($newFullPath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    // Rename file
                    if (rename($oldFullPath, $newFullPath)) {
                        // Update database record
                        $record->update(['file' => $newFilePath]);
                    }
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
